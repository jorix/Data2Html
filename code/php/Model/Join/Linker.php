<?php
namespace Data2Html\Model\Join;

use Data2Html\DebugException;
use Data2Html\Config;
use Data2Html\Handler;
use Data2Html\Model\Set;
use Data2Html\Data\Lot;

class Linker
{
    use \Data2Html\Debug;
    
    protected $items = [];
    protected $refItems = [];
    protected $refAlias = [];
    protected $tableCount = 0;
    protected $tableSources = [];
    protected $sources = [];

    public function __debugInfo()
    {
        return [
            'getFrom()' => $this->getFrom(),
            'getKeys()' => $this->getKeys(),
            'refItems' => $this->refItems,
            'refAlias' => $this->refAlias,
            'items' => $this->items,
            'sources' => $this->sources,
        ];
    }
    
    public function getItems($groupName = null)
    {
        return Lot::getItem(($groupName ? $groupName : 'main'), $this->items);
    }

    public function getFrom()
    {
        return $this->tableSources;
    }
    
    public function getKeys()
    {
        return $this->tableSources['T0']['keys'];
    }
    
    public function linkUp($groupName, Set $set)
    {
        // First prepare reference through $this to array items of $groupName.
        $items = [];
        $this->items[$groupName] = &$items;
        
        // Add table
        $dafaultSort = null;
        if ($groupName === 'main') {
            $this->addTable(null, null, $set);
            $dafaultSort = $set->getSort();
        }
        
        // LinkUp
        $fromItems = $set->getItems();
        $tableAlias = 'T0';
        
        foreach ($fromItems as $key => $item) {
            $refItem = $this->getRef($groupName, $tableAlias, $this->getRefBase($item));
            // Prepare a real item to added
            if (!$refItem) {
                $item['tableAlias'] = $tableAlias;
            } else {
                // If is previous added as virtual remove it to link up in real position.
                if(array_key_exists('_instrumental', $items[$refItem])) {
                    $item = $items[$refItem];
                    unset($item['_instrumental']);
                    unset($item['_virtual']);
                    unset($this->refItems[$groupName][$tableAlias][$this->getRefBase($item)]);
                    unset($items[$refItem]);
                }
            }
            $this->makeItem($groupName, $tableAlias, $key, $item);
        }
        
        // Check default sort
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
    
    protected function getRef($groupName, $tableAlias, $baseName)
    {
        return Lot::getItem([$groupName, $tableAlias, $baseName], $this->refItems);
    }
    
    protected static function getRefBase($item)
    {
        $baseName = null;
        if (isset($item['base'])) {
            $baseName = $item['base'];
        } elseif (isset($item['db-items'])) {
            $baseName = $item['name'];
        } elseif (isset($item['db'])) {
            $baseName = $item['db'];
        } elseif (isset($item['value'])) {
            $baseName = 'v[' . $item['value'] . ']';
        }
        return $baseName;
    }
    
    protected function makeInstrumentalItemByBase($groupName, $fromAlias, $baseName)
    {
        $finalBaseName = $this->getRef($groupName, $fromAlias, $baseName);
        if (!$finalBaseName) {
            $finalBaseName = $this->makeInstrumentalItem(
                $groupName, 
                $fromAlias, 
                $baseName,
                $this->getOriginItem($fromAlias, $baseName)
            );
        }
        $this->items[$groupName][$finalBaseName]['_virtual'] = false;
        return $finalBaseName;
    }
       
    protected function makeVirtualItemByBase($groupName, $fromAlias, $baseName)
    {
        $finalBaseName = $this->getRef($groupName, $fromAlias, $baseName);
        if (!$finalBaseName) {
            $finalBaseName = $this->makeInstrumentalItem(
                $groupName, 
                $fromAlias, 
                $baseName,
                $this->getOriginItem($fromAlias, $baseName)
            );
            $this->items[$groupName][$finalBaseName]['_virtual'] = true;
        }
        return $finalBaseName;
    }
    
    protected function makeInstrumentalItem($groupName, $tableAlias, $base, $item) {
        $newRef = $tableAlias . '_' . $base;
        if (array_key_exists($newRef, $this->items[$groupName])) {
            $this->items[$groupName][$newRef]['_virtual'] = false;
            return $newRef;
        } elseif ($tableAlias === 'T0') {
            // remove prefix T0_ for virtual items on T0 if is new.
            if (!array_key_exists($base, $this->items[$groupName])) {
                $newRef = $base; 
            }
        }
        $item['_instrumental'] = true;
        $item['_virtual'] = false;
        return $this->makeItem($groupName, $tableAlias, $newRef, $item);
    }
        
    protected function makeItem($groupName, $tableAlias, $newRef, $item) {
        $item['tableAlias'] = $tableAlias;
        $item['name'] = $newRef;
        
        //Check if item already exist
        $finalBaseName = $this->getRef($groupName, $tableAlias, $this->getRefBase($item));
        if ($finalBaseName) {
            return $finalBaseName;
        }
        
        // Add new item
        $this->items[$groupName][$newRef] = &$item;
        $this->refItems[$groupName][$tableAlias][$this->getRefBase($item)] = $newRef;
        
        $tableAliasValue = $tableAlias;
        $tableAliasSortBy = $tableAlias;
        $linkedTo = $this->parseLinkedTo(Lot::getItem('base', $item), $tableAlias);
        if (count($linkedTo) > 0) {
            foreach ($linkedTo as $v) {
                $lkItem = $this->obtainItemByLinkedTo($groupName, $tableAlias, $v);
                $lkAlias = $lkItem['tableAlias'];
                if (count($linkedTo) === 1) {
                    if (!array_key_exists('value-patterns', $item)) { // Merge fields
                        if (!array_key_exists('value', $item)) {
                            $tableAliasValue = $lkAlias;
                        }
                        if (!array_key_exists('sortBy', $item)) {
                            $tableAliasSortBy = $lkAlias;
                        }
                        unset($item['base']);
                        self::applyAttibutes($lkItem, $item);
                        $item['tableAlias'] = $lkAlias;
                        if (Lot::getItem('linkedWith-list', $v)) {
                            $item['link-list'] =
                                $this->tableSources[$lkAlias]['from-list'];
                        }
                    } else { // linked with a virtual item
                        $this->makeInstrumentalItem($groupName, $lkAlias, $v['base'], $lkItem);
                    }
                } else {
                    $this->makeInstrumentalItem($groupName, $lkAlias, $v['base'], $lkItem);
                }
            }
            if (Config::debug()) {
                $item['debug-linkedTo'] = $linkedTo;
            }
        }
        
        // Set final-db
        if (array_key_exists('db', $item)) {
            $tableAliasDb = $item['tableAlias'];
            $db = $item['db'];
            if (preg_match('/^\w+$/', $db)) { // is a name
                $item['final-db'] = $tableAliasDb  . '.' . $db;
            } else {
                $item['final-db'] = preg_replace_callback(
                    '/(\b[a-z]\w*\b\s*(?![\(]))/i', // TODO: funtionName + space + ( eg: '1000 + id + sin (e)'
                    function ($matches) use ($tableAliasDb) {
                        return $tableAliasDb . '.' . $matches[0];
                    },
                    $db
                );
                if (!$item['final-db']) { // remove if is ''
                    unset($item['final-db']);
                }
            }
        }
                    
        // Force read linked model (uses final-db)
        if (array_key_exists('link', $item) || array_key_exists('list', $item)) {
            $toAlias = $this->prepareToAlias(
                $groupName, 
                $tableAlias,
                $this->getRefBase($item),
                Lot::getItem('link', $item),
                Lot::getItem('list', $item)
            );
            $keys = $this->tableSources[$toAlias]['keys'];
            foreach ($keys as $k => $v) {
                $lkItem = $this->getOriginItem($toAlias, $k);
            }
            self::applyAttibutes($lkItem, $item);
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
                $this->applyFinalBases($groupName, $tableAliasValue, $tItems, $linkedTo);
                $item['value-patterns'] = $tItems;
                if (Config::debug() && count($linkedTo) > 0) {
                    $item['debug-value-linkedTo'] = $linkedTo;
                }
            }
        }
        
        if (isset($item['sortBy'])) {
            // Do links
            $linkedTo = [];
            $sortBy = &$item['sortBy']['items'];
            foreach ($sortBy as $k => $v) {
                $linkedToItem = $this->parseLinkedTo($v['base'], $tableAliasSortBy);
                if (count($linkedToItem) > 0) {
                    $linkedTo += $linkedToItem;
                }
            }
            // Apply base
            $this->applyFinalBases(
                $groupName,
                $tableAliasSortBy,
                $sortBy,
                $linkedTo
            );
            // Change key if it is a linkedTo
            $fSortBy = [];
            foreach ($sortBy as $k => $v) {
                if (array_key_exists($v['base'], $linkedTo)) {
                    $linkedToVal = $linkedTo[$v['base']];
                    $fSortBy[$linkedToVal['final-base']] = $v;
                } else {
                    $fSortBy[$k] = $v;
                }
            }
            $item['sortBy']['items'] = $fSortBy;

            if (Config::debug() && count($linkedTo) > 0) {
                $item['sortBy']['debug-linkedTo'] = $linkedTo;
            }
        }
        return $newRef;
    }
      
    protected function applyFinalBases($groupName, $tableAlias, &$bases, &$linkedTo)
    { 
        // Do links
        foreach ($linkedTo as $k => &$v) {
            $lkItem = $this->obtainItemByLinkedTo($groupName, $tableAlias, $v);
            $itemBase = self::getRefBase($lkItem);
            if (!$itemBase || preg_match('/^\w$/', $itemBase)) {
                throw new DebugException(
                    "Internal error",
                    [$groupName, $tableAlias, $k, $v, $itemBase, $lkItem]
                );
            }
            $finalBaseName = $this->makeInstrumentalItem(
                $groupName,
                $lkItem['tableAlias'],
                $itemBase,
                $lkItem
            );
            $v['final-base'] = $finalBaseName;
        }
        unset($v);

        // Add virtual items used
        $lk = $this->sources[$tableAlias];
        foreach ($bases as $k => &$v) {
            $baseName = $v['base'];
            $finalBaseName = Lot::getItem([$v['base'] , 'final-base'], $linkedTo);
            if (!$finalBaseName) {
                // Final base is not added, then search item by baseName
                if (array_key_exists($baseName, $lk['_items'])) {
                    $finalBaseName = $this->makeInstrumentalItem(
                        $groupName, $tableAlias, $baseName, $lk['_items'][$baseName]
                    );
                } elseif (array_key_exists($baseName, $lk['_base'])) {
                    $finalBaseName = $this->makeInstrumentalItem(
                        $groupName, $tableAlias, $baseName, $lk['_base'][$baseName]
                    );
                } else {
                    throw new DebugException(
                        "Base for item \"{$baseName}\" not fount.",
                        $item
                    );
                }
            }
            $v['final-base'] = $finalBaseName;
            if (!array_key_exists($finalBaseName, $this->items[$groupName])) {
                throw new DebugException(
                    "Final base \"{$finalBaseName}\" not found on '{$groupName}'.",
                    [
                        'base-items' => $bases,
                        'final-bases' => $this->refItems,
                        $groupName => $this->items[$groupName]
                    ]
                );
            }
            $fItem = $this->items[$groupName][$finalBaseName];
            if (!array_key_exists('db', $fItem) &&
                !array_key_exists('value', $fItem)
            ) {
                throw new DebugException(
                    "Final base \"{$finalBaseName}\" without 'db' and 'value' attributes on '{$groupName}'.",
                    [
                        'base-items' => $sortBy['items'],
                        'final-bases' => $this->refItems,
                        $groupName => $this->items[$groupName]
                    ]
                );
            } elseif (isset($fItem['db'])) {
                if (!isset($fItem['final-db'])) {
                    throw new DebugException("Internal error", [$bases, $fItem, $v]);
                }
                $v['final-db'] = $fItem['final-db'];
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
        $baseItems = $this->sources[$tableAlias]['_base'];
        for ($i = 0; $i < count($matches[0]); $i++) {
            if ($matches[1][$i]) {
                $baseLink = $matches[1][$i];
                $match = $matches[0][$i];
                $linkedTo[$match] = [
                    'fromBaseLinkName' => $baseLink,
                    'toBaseItemName' => $matches[2][$i]
                ];
                if (!array_key_exists($baseLink, $baseItems)) {
                    throw new DebugException(
                        "Defining \"{$base}\", the link \"{$baseLink}\" was not found.",
                        $baseItems
                    );
                }
                $isLinked = false;
                if (array_key_exists('link', $baseItems[$baseLink])) {
                    $linkedWith = $baseItems[$baseLink]['link'];
                    $linkedTo[$match] += [
                        'linkedWith-grid' => $baseItems[$baseLink]['link']
                    ];
                    $isLinked = true;
                } 
                if (array_key_exists('list', $baseItems[$baseLink])) {
                    $linkedTo[$match] += ['linkedWith-list' => true];
                    $isLinked = true;
                }
                if (!$isLinked) {
                    throw new DebugException(
                        "Defining \"{$base}\", the \"{$baseLink}\" is not a link or a list."
                    );
                }
            }
        }
        return $linkedTo;
    }
    
    protected function prepareToAlias(
        $groupName,
        $fromAlias,
        $fromBaseLinkName,
        $linkedWith,
        $ListWith
    ) {
        $toAlias = $this->getRefAlias($fromAlias, $fromBaseLinkName);
        if (!$toAlias) {
            if ($linkedWith) {
                $playerNames = Handler::parseLinkText($linkedWith);
                if (!array_key_exists('grid', $playerNames)) {
                    throw new \Exception(
                        "Link \"{$linkedWith}\" without a grid name."
                    );
                }
                $model = Handler::getModel($playerNames['model']);
                $grid = $model->getGridColumns($playerNames['grid']);
                if ($grid->getAttribute('summary')) {
                    throw new DebugException(
                        "Is not possible link with a summary grid", [
                            'groupName' => $groupName, 
                            'fromAlias' => $fromAlias, 
                            'fromBaseLinkName' => $fromBaseLinkName,
                            'linkedWith' => $linkedWith
                    ]);
                }
                $toAlias = $this->addTable($fromAlias, $fromBaseLinkName, $grid);
            }
            if ($ListWith) {
                $toAlias = $this->addListItem($groupName, $fromAlias, $fromBaseLinkName);
            }
        }
        return $toAlias;
    }
    
    protected function obtainItemByLinkedTo($groupName, $fromAlias, $linkedToInfo)
    {
        if (!isset($linkedToInfo['fromBaseLinkName'])) {
            throw new DebugException(
                "Internal error",
                [$groupName, $fromAlias, $linkedToInfo]
            );
        }
        $toAlias = $this->prepareToAlias(
            $groupName,
            $fromAlias,
            $linkedToInfo['fromBaseLinkName'],
            Lot::getItem('linkedWith-grid', $linkedToInfo),
            Lot::getItem('linkedWith-list', $linkedToInfo)
        );
        return $this->getOriginItem($toAlias, $linkedToInfo['toBaseItemName']);
    }
    
    protected function getOriginItem($tableAlias, $baseName)
    {
        // Get item
        $l = $this->sources[$tableAlias];
        $lkItem = null;
        if (array_key_exists($baseName, $l['_items'])) {
            $lkItem = $l['_items'][$baseName];
        } elseif (array_key_exists($baseName, $l['_base'])) {
            $lkItem = $l['_base'][$baseName];
        } elseif (array_key_exists('[list]', $l['_items'])) {
            $baseName = '[list]';
            $lkItem = $l['_items'][$baseName];
        }
        if (!$lkItem) {
            throw new debugException(
                "Item \"{$baseName}\" not found on 'items' and 'base'.",
                $l
            );
        }
        $lkItem['tableAlias'] = $tableAlias;
        return $lkItem;
    }

    protected function getRefAlias($fromAlias, $fromBaseLinkName)
    {
        return Lot::getItem([$fromAlias, $fromBaseLinkName], $this->refAlias);
    }
    
    protected function addTable($fromAlias, $fromBaseLinkName, $set) {
        $toAlias = 'T' . $this->tableCount;
        $this->tableCount++;
        
        $tableSource = [
            'from-final-db'=> null,
            'table' =>  $set->getTableName(),
            'join-type' => null,
            'keys' => null
        ];
        $source = [
            '_items' => $set->getItems(),
            '_base' => $set->getBase()->getItems()
        ];
        $this->tableSources[$toAlias] = &$tableSource;
        $this->sources[$toAlias] = &$source;
        
        // Apply final-db on keys
        $keys = $set->getKeys();
        foreach ($keys as $k => &$v) {
            if ($toAlias === 'T0') {
                $finalName = $this->makeInstrumentalItemByBase('main', $toAlias, $k);
            } else {
                $finalName = $this->makeVirtualItemByBase('main', $toAlias, $k);
            }
            $refDb = Lot::getItem(['main', $finalName, 'final-db'], $this->items);
            if (!$refDb) {
                throw new DebugException(
                    "Key \"{$k}\" of \"{$toAlias}\" without 'final-db'.",
                    $tableSource
                );
            }
            $v['final-db'] = $refDb;
        }
        unset($v);
        $tableSource['keys'] = $keys;
        
        $groupName = 'main';
        if ($toAlias !== 'T0') {

            $this->refAlias[$fromAlias][$fromBaseLinkName] = $toAlias;
            // TODO: multi key on $baseLinkNames
            $baseLinkNames = Lot::getItem(
                [$fromAlias, '_base', $fromBaseLinkName, 'db-items'],
                $this->sources
            );
            if (!$baseLinkNames) {
                $baseLinkNames = [$fromBaseLinkName];
            }
            
            $originBaseNames = [];
            foreach($baseLinkNames as $v) {
                $originBaseNames[] = $this->makeVirtualItemByBase($groupName, $fromAlias, $v);
            }
            
            // Get attributes from origin keys for link field
            if (count($originBaseNames) !== count($keys)) {
                throw new DebugException(
                    "Linked origin db-names and destination keys has a different number of fields.", [
                    'Origin fromAlias' => $fromAlias,
                    'Origin db-names' => $originBaseNames,
                    'Destination keys' => $keys,
                    'tableSource' => $tableSource,
                    'debugInfo' => $this->__debugInfo()
                ]);
            }
            $i = 0;
            $areRequired = true;
            $formFinalDb = [];
            foreach (array_keys($keys) as $k) {
                $finalBaseItem = &$this->items[$groupName][$originBaseNames[$i]];
                self::applyAttibutes($source['_base'][$k], $finalBaseItem, ['key']);
                $formFinalDb[] = Lot::getItem('final-db', $finalBaseItem);
                $areRequired = $areRequired &&
                    Lot::getItem(['validations', 'required'], $finalBaseItem, false);
                unset($finalBaseItem);
                $i++;
            }
            $tableSource['from-final-db'] = $formFinalDb;
            $tableSource['join-type'] = ($areRequired ? 'inner' : 'left');
        }
        return $toAlias;
    }
    
    protected function addListItem($groupName, $fromAlias, $fromBaseLinkName) {
      //  $finalBaseName = $this->getRef($groupName, $fromAlias, $fromBaseLinkName);
        $toAlias = $this->getRefAlias($fromAlias, $fromBaseLinkName);
        $isNew = false;
        if ($toAlias) {
            // Alias already exist e.g. by addTable
            $tableSource = &$this->tableSources[$toAlias];
            $source = &$this->sources[$toAlias];
        } else {
            // Create new alias
            $isNew = true;
            $toAlias = 'T' . $this->tableCount;
            $this->tableCount++;
            
            $tableSource = [
                'from-final-db'=> null,
                'table' =>  null,
                'keys' => null
            ];
            $source = [
                '_items' => [],
                '_base' => []
            ];
            $this->tableSources[$toAlias] = &$tableSource;
            $this->sources[$toAlias] = &$source;
            $this->refAlias[$fromAlias][$fromBaseLinkName] = $toAlias;
        }
        
        // Add origin list as item
        $finalBaseName =
            $this->makeInstrumentalItemByBase($groupName, $fromAlias, $fromBaseLinkName);
        $tableSource['from-list'] = $finalBaseName;
        $origin = $this->items[$groupName][$finalBaseName];
        if ($isNew) {
            $listItem = [
                'tableAlias' => $toAlias,
                'type' => 'string'
            ];
            if (array_key_exists('title', $origin)) {
                $listItem['title'] = $origin['title'];
            }
            if (array_key_exists('description', $origin)) {
                $listItem['description'] = $origin['description'];
            }
            $source['_items']['[list]'] = $listItem;
        }
        
        return $toAlias;
    }
    
    protected static function applyAttibutes($fromItem, &$toItem, $except = null) {
        $origin = ['sortBy', 'value', 'value-patterns', 'size'];
        foreach($origin as $v) {
            if (array_key_exists($v, $toItem)) {
                unset($fromItem[$v]);
            }
        }
        if ($except) {
            foreach((array)$except as $v) {
                unset($fromItem[$v]);
            }
        }
        $toItem = array_replace_recursive([], $fromItem, $toItem);
    }
}
