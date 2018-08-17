<?php
namespace Data2Html\Model\Join;

use Data2Html\DebugException;
use Data2Html\Config;
use Data2Html\Handler;
use Data2Html\Model\Set;
use Data2Html\Data\Lot;

class LinkUp
{
    use \Data2Html\Debug;
 
    protected $originSet = null;
    
    protected $tableSources = array();
    protected $links = array();
    protected $linkDone = false;
    protected $items = array();
    protected $refItems = array();

    
    public function __construct($set)
    {
        $this->originSet = $set;
        $this->addTable(null, $set);
        $this->add('main', $set->getItems());
        
        // Check default sort
        $dafaultSort = $this->originSet->getSort();
        if ($dafaultSort) {
            if (substr($dafaultSort, 0, 1) === '!') {
                $dafaultSort = substr($dafaultSort, 1);
            }
            $sortBy = Lot::getItem([$dafaultSort, 'sortBy'], $this->items['main']);
            if (!$sortBy) {
                throw new DebugException(
                    "Default sort '{$dafaultSort}' not found or don't have sortBy .",
                    $this->items['main']
                );
            }
        }
    }

    public function __debugInfo()
    {
        return [
            'getFrom()' => $this->getFrom(),
            'getKeys()' => $this->getKeys(),
            'refItems' => $this->refItems,
            'items' => $this->items,
        ];
    }
           
    public function getItems($groupName = null) {
        if (!$this->linkDone) {
            $this->linkKeys();
        } 
        return Lot::getItem(($groupName ? $groupName : 'main'), $this->items);
    }

    public function getFrom() {
        if (!$this->linkDone) {
            $this->linkKeys();
        } 
        return $this->tableSources;
    }
    
    public function getKeys() {
        if (!$this->linkDone) {
            $this->linkKeys();
        } 
        return $this->tableSources['T0']['keys'];
    }
    
    public function add($groupName, $fromItems)
    {
        if ($this->linkDone) {
            throw new DebugException(
                "Link is done, It is not possible to add more sets.",
                $this->items[$groupName]
            );
        }
        $tableAlias = $this->links['T0']['alias'];
        $baseItems = $this->links['T0']['base'];
        
        $items = [];
        $this->items[$groupName] = &$items;
        foreach ($fromItems as $key => $item) {
            $refItem = $this->getRefByItem($groupName, $tableAlias, $item);
            // Prepare a real item to added
            if (!$refItem) {
                $item['tableAlias'] = $tableAlias;
            } else {
                // If is previous added as virtual remove it to add in real position.
                if(array_key_exists('virtual', $items[$refItem])) {
                    $item = $items[$refItem];
                    unset($item['virtual']);
                    unset($this->refItems[$groupName][$tableAlias][$this->getRefBase($item)]);
                    unset($items[$refItem]);
                }
            }
            $this->linkItem($groupName, $key, $item);
        }
    }
    
    protected function linkKeys()
    {
        $groupName = 'main';
        foreach ($this->tableSources as &$fromTable) {
            $tableAlias = $fromTable['alias'];
            foreach ($fromTable['keys'] as $baseName => &$v) {
                $finalBase = $this->getRef($groupName, $tableAlias, $baseName);
                if (!$finalBase) {
                    $finalBase = $this->linkVirtualItemByBaseName($groupName, $tableAlias, $baseName);
                }    
                $refDb = Lot::getItem([$groupName, $finalBase, 'final-db'], $this->items);
                if (!$refDb) {
                    throw new DebugException(
                        "Key base \"{$baseName}\" of \"{$tableAlias}\" without 'final-db'.",
                        $fromTable
                    );
                }
                $v['final-db'] = $refDb;
            }
            unset($v);
        }
        unset($fromTable);
        $this->linkDone = true;
    }
    
    protected function getRefByItem($groupName, $tableAlias, $item) {
        return $this->getRef($groupName, $tableAlias, $this->getRefBase($item));
    }
    
    protected function getRef($groupName, $tableAlias, $baseName) {
        return Lot::getItem([$groupName, $tableAlias, $baseName], $this->refItems);
    }

    protected function getRefBase($item) {
        $baseName = null;
        if (array_key_exists('base', $item)) {
            $baseName = $item['base'];
        } elseif (array_key_exists('db', $item)) {
            $baseName = $item['db'];
        } elseif (array_key_exists('value', $item)) {
            $baseName = 'v[' . $item['value'] . ']';
        }
        return $baseName;
    }
    
    protected function linkVirtualItemByBaseName($groupName, $fromAlias, $baseName)
    {
        $linkId = Lot::getItem([$fromAlias, 'from'], $this->tableSources);
        $lkItem = $this->getLinkItemById($linkId, $baseName);
        return $this->linkVirtualItem($groupName, $fromAlias, $baseName, $lkItem);
    }
    
    protected function linkVirtualItem($groupName, $tableAlias, $base, $item) {
        $item['tableAlias'] = $tableAlias;
        $newRef = $tableAlias . '_' . $base;
        if (array_key_exists($newRef, $this->items[$groupName])) {
            return $newRef;
        } elseif ($tableAlias === 'T0') {
            // remove prefix T0_ for virtual items on T0 if is new.
            if (!array_key_exists($base, $this->items[$groupName])) {
                $newRef = $base; 
            }
        }
        $item['virtual'] = true;
        return $this->linkItem($groupName, $newRef, $item);
    }
        
    protected function linkItem($groupName, $newRef, $item) {
        $tableAlias = $item['tableAlias'];
        
        //Check if item already exist
        $finalBase = $this->getRefByItem($groupName, $tableAlias, $item);
        if ($finalBase) {
            return $finalBase;
        }
        
        // Add new item
        $this->items[$groupName][$newRef] = &$item;
        $this->refItems[$groupName][$tableAlias][$this->getRefBase($item)] = $newRef;
        
        $tableAliasValue = $tableAlias;
        $tableAliasSortBy = $tableAlias;
        $linkedTo = $this->parseLinkedTo(Lot::getItem('base', $item), $tableAlias);
        if (count($linkedTo) > 0) {
            foreach ($linkedTo as $v) {
                $lkItem = $this->getLinkItemByLinkedTo($tableAlias, $v);
                $lkAlias = $lkItem['tableAlias'];
                if (count($linkedTo) === 1) {
                    if(!array_key_exists('value-patterns', $item)) { // Merge fields
                        if (!array_key_exists('value', $item)) {
                            $tableAliasValue = $lkAlias;
                        }
                        if (!array_key_exists('sortBy', $item)) {
                            $tableAliasSortBy = $lkAlias;
                        }
                        unset($item['base']);
                        $item = array_replace_recursive([], $lkItem, $item);
                        $item['tableAlias'] = $lkAlias;
                    } else { // linked with a virtual item
                        $this->linkVirtualItem($groupName, $lkAlias, $v['base'], $lkItem);
                    }
                } else {
                    $this->linkVirtualItem($groupName, $lkAlias, $v['base'], $lkItem);
                }
            }
            if (Config::debug()) {
                $item['debug-linkedTo'] = $linkedTo;
            }
        }
        
        // Default attributes
        if (array_key_exists('base', $item) &&
            !array_key_exists('title', $item)
        ) {
            $item['title'] = $item['base'];
        }
        if (array_key_exists('db', $item) &&
            !array_key_exists('title', $item)
        ) {
            $item['title'] = $item['db'];
        }
        if (!array_key_exists('description', $item) &&
            array_key_exists('title', $item)
        ) {
            $item['description'] = $item['title'];
        }
        
        if (array_key_exists('db', $item)) {
            $tableAlias = $item['tableAlias'];
            $db = $item['db'];
            if (preg_match('/^\w+$/', $db)) { // is a name
                $item['final-db'] = $tableAlias . '.' . $db;
            } else {
                $item['final-db'] = preg_replace_callback(
                    '/(\b[a-z]\w*\b\s*(?![\(]))/i', // TODO: funtionName + space + ( eg: '1000 + id + sin (e)'
                    function ($matches) use ($tableAlias) {
                        return $tableAlias . '.' . $matches[0];
                    },
                    $db
                );
                if (!$item['final-db']) { // remove if is ''
                    unset($item['final-db']);
                }
            }
        }
        
        if (array_key_exists('value', $item)) {
            // Parse patterns as: $${name} | $${link[name]}
            $matches = null;
            preg_match_all(Set::GetPatternValueTemplate(), $item['value'], $matches);
            $tItems = [];
            if (count($matches[0]) > 0) {
                if (!array_key_exists('type', $item)) {
                    $item['type'] = 'string';
                }
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $tItems[$matches[0][$i]] = ['base' => $matches[1][$i]];
                }
                // parse links
                $linkedTo = $this->parseLinkedTo($item['value'], $tableAliasValue);
                // do virtual for list bases
                $this->linkVirtualBases($groupName, $tableAliasValue, $tItems, $linkedTo);
                $item['value-patterns'] = $tItems;
                if (Config::debug() && count($linkedTo) > 0) {
                    $item['debug-value-linkedTo'] = $linkedTo;
                }
            }
        }
        
        if (array_key_exists('sortBy', $item) && $item['sortBy']) {
            // Do links
            $linkedTo = [];
            $sortBy = &$item['sortBy']['items'];
            foreach ($sortBy as $k => $v) {
                $linkedToItem = $this->parseLinkedTo($v['base'], $tableAliasSortBy);
                if (count($linkedToItem) > 0) {
                    $linkedTo[$k] = $linkedToItem;
                }
            }
            // do virtual for list bases
            $this->linkVirtualBases(
                $groupName,
                $tableAliasSortBy,
                $sortBy,
                $linkedTo
            );
            if (Config::debug() && count($linkedTo) > 0) {
                $item['sortBy']['debug-linkedTo'] = $linkedTo;
            }
        }

        return $newRef;
    }
        
    protected function linkVirtualBases($groupName, $tableAlias, &$bases, &$linkedTo)
    { 
        // Do links
        foreach ($linkedTo as $k => &$v) {
            $lkItem = $this->getLinkItemByLinkedTo($tableAlias, $v);
            $finalBase = $this->linkVirtualItem(
                $groupName,
                $lkItem['tableAlias'],
                $v['base'],
                $lkItem
            );
            $v['final-base'] = $finalBase;
        }
        unset($v);


            // Add virtual items used
        foreach ($bases as $k => &$v) {
            $baseName = $v['base'];
            $finalBase = Lot::getItem([$v['base'] , 'final-base'], $linkedTo);
            if (!$finalBase) {
                // Final base is not added, then search item by baseName
                $from = $this->tableSources[$tableAlias]['from'];
                $lkAlias = $this->tableSources[$tableAlias]['alias'];
                $lk = $this->links[$from];
                if (array_key_exists($baseName, $lk['items'])) {
                    $finalBase = $this->linkVirtualItem(
                        $groupName, $lkAlias, $baseName, $lk['items'][$baseName]
                    );
                } elseif (array_key_exists($baseName, $lk['base'])) {
                    $finalBase = $this->linkVirtualItem(
                        $groupName, $lkAlias, $baseName, $lk['base'][$baseName]
                    );
                } else {
                    throw new DebugException(
                        "Base for item \"{$baseName}\" not fount.",
                        $item
                    );
                }
            }
            $v['final-base'] = $finalBase;
            if (!array_key_exists($finalBase, $this->items[$groupName])) {
                throw new DebugException(
                    "Final base \"{$finalBase}\" not found on '{$groupName}'.",
                    [
                        'base-items' => $bases,
                        'final-bases' => $this->refItems,
                        $groupName => $this->items[$groupName]
                    ]
                );
            }
            $fItem = $this->items[$groupName][$finalBase];
            if (!array_key_exists('db', $fItem) &&
                !array_key_exists('value', $fItem)
            ) {
                throw new DebugException(
                    "Final base \"{$finalBase}\" without 'db' and 'value' attributes on '{$groupName}'.",
                    [
                        'base-items' => $sortBy['items'],
                        'final-bases' => $this->refItems,
                        $groupName => $this->items[$groupName]
                    ]
                );
            } elseif (array_key_exists('db', $fItem)) {
                $v['final-db'] = $this->items[$groupName][$finalBase]['final-db'];
            }
        }
        unset($v);
    }

    private function parseLinkedTo($base, $tableAlias)
    {
        $matches = null;
        preg_match_all(Set::getPatternLinked(), $base, $matches);
        if (count($matches[0]) === 0) {
            return [];
        }
        
        $linkedTo = [];
        $baseItems = $this->links[$tableAlias]['base'];
        for ($i = 0; $i < count($matches[0]); $i++) {
            if ($matches[1][$i] && $matches[2][$i]) {
                $baseLink = $matches[1][$i];
                $match = $matches[0][$i];
                $linkedTo[$match] = [
                    'baseItemLink' => $baseLink,
                    'baseItemName' => $matches[2][$i]
                ];
                if (!array_key_exists($baseLink, $baseItems)) {
                    throw new DebugException(
                        "Defining \"{$base}\", the link \"{$baseLink}\" was not found.",
                        $baseItems
                    );
                }
                if (!array_key_exists('link', $baseItems[$baseLink])) {
                    throw new DebugException(
                        "Defining \"{$base}\", the \"{$baseLink}\" is not a link."
                    );
                }
                $linkedWith = $baseItems[$baseLink]['link'];
                $playerNames = Handler::parseLinkText($linkedWith);
                if (!array_key_exists('grid', $playerNames)) {
                    throw new \Exception(
                        "Link \"{$linkedWith}\" without a grid name."
                    );
                }
                $linkedTo[$match] += [
                    'linkedWith-model' => $playerNames['model'],
                    'linkedWith-grid' => $playerNames['grid']
                ];
            }
        }
        return $linkedTo;
    }
    
    protected function getLinkItemByLinkedTo($fromAlias, $linkedToInfo)
    {
        $linkId = $fromAlias;
        if ($linkedToInfo['baseItemLink']) {
            $linkId .= '|' . $linkedToInfo['baseItemLink'];
        }
        return $this->getLinkItemById($linkId, $linkedToInfo['baseItemName'], $linkedToInfo);
    }
    
    protected function getLinkItemById($linkId, $baseName, $linkedToInfo = null)
    {
        if (!array_key_exists($linkId, $this->links)) {
            if (!$linkedToInfo) {
                throw new DebugException(
                    "LinkId \"{$linkId}\" not exist.",
                    $this->tableSources
                );
            }
            $model = Handler::getModel($linkedToInfo['linkedWith-model']);
            $grid = $model->getGridColumns($linkedToInfo['linkedWith-grid']);
            $this->addTable($linkId, $grid);
        }
        // Get item
        $l = $this->links[$linkId];
        $lkItem = null;
        if (array_key_exists($baseName, $l['items'])) {
            $lkItem = $l['items'][$baseName];
        } elseif (array_key_exists($baseName, $l['base'])) {
            $lkItem = $l['base'][$baseName];
        }
        if (!$lkItem) {
            throw new debugException(
                "Item \"{$baseName}\" not found on 'items' and 'base'.",
                $l
            );
        }
        $lkItem['tableAlias'] = $l['alias'];
        return $lkItem;
    }

    protected function addTable($linkId, $set) {
        $keys = $set->getKeys();
        $tableName = $set->getTableName();
        
        $tableAlias = 'T' . count($this->tableSources);
        $linkId = $linkId ? $linkId : $tableAlias;
        
        $this->links[$linkId] = array(
            'alias' => $tableAlias,
            'items' => $set->getItems(),
            'base' => $set->getBase()->getItems()
        );
        $fromId = explode('|', $linkId);
        $this->tableSources[$tableAlias] = array(
            'from' => $linkId,
            'fromAlias' => $fromId[0],
            'alias' => $tableAlias,
            'table' => $tableName,
            'keys' => $keys
        );
        $groupName = 'main';
        if ($tableAlias === 'T0') {
            $this->tableSources[$tableAlias]['fromField'] = null; 
        } else {
            $lkAlias = $fromId[0];
            $lkBaseName = $fromId[1];
            $finalBase = $this->getRef($groupName, $lkAlias, $lkBaseName);
            if (!$finalBase) {
                $finalBase = $this->linkVirtualItemByBaseName($groupName, $lkAlias, $lkBaseName);
            }
            $this->tableSources[$tableAlias]['fromField'] = 
                Lot::getItem([$groupName, $finalBase, 'final-db'], $this->items);
        }
        return $tableAlias;
    }
}
