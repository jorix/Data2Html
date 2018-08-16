<?php
namespace Data2Html\Model\Join;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Handler;

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
                $ref = $this->getRef($groupName, $tableAlias, $baseName);
                if (!$ref) {
                    $ref = $this->linkVirtualItemByBaseName($groupName, $tableAlias, $baseName);
                }    
                $refDb = Lot::getItem([$groupName, $ref, 'refDb'], $this->items);
                if (!$refDb) {
                    throw new DebugException(
                        "Key base \"{$baseName}\" of \"{$tableAlias}\" without refDb.",
                        $fromTable
                    );
                }
                $v['refDb'] = $refDb;
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
        } elseif (
            $tableAlias === 'T0' &&
            !array_key_exists($base, $this->items[$groupName])
        ) {
            $newRef = $base; // remove prefix T0_ for virtual items on T0.
        }
        $item['virtual'] = true;
        return $this->linkItem($groupName, $newRef, $item);
    }
        
    protected function linkItem($groupName, $newRef, $item) {
        $tableAlias = $item['tableAlias'];
        
        //Check if item already exist
        $ref = $this->getRefByItem($groupName, $tableAlias, $item);
        if ($ref) {
            return $ref;
        }
        
        // New item
        $this->items[$groupName][$newRef] = &$item;
        $this->refItems[$groupName][$tableAlias][$this->getRefBase($item)] = $newRef;
        
        $tableAliasInit = $tableAlias; // TODO: not use $tableAliasInit
        if (array_key_exists('linkedTo', $item)) {
            $itemBase = Lot::getItem('base', $item);
            foreach ($item['linkedTo'] as $k => &$v) {
                // $this->dump([$tableAlias, $v]);
                $lkItem = $this->getLinkItemByLinkTo($tableAlias, $v);
                $lkAlias = $lkItem['tableAlias'];
                if (count($item['linkedTo']) === 1) {
                    if(!array_key_exists('teplateItems', $item)) { // Merge fields
                        unset($item['linkedTo']);
                // $this->dump([$item, $lkItem]);
                        unset($item['base']);
                        if (array_key_exists('sortBy', $item) &&
                            array_key_exists('sortBy', $lkItem)
                        ) {
                            unset($lkItem['sortBy']);
                        }
                        $item = array_replace_recursive(array(), $lkItem, $item);
                // $this->dump([$item, $lkItem]);
                        $item['tableAlias'] = $lkAlias;
                    } else { // linked with a virtual item
                        $v['ref'] = $this->linkVirtualItem($groupName, $lkAlias, $v['base'], $lkItem);
                    }
                } else {
                    $v['ref'] = $this->linkVirtualItem($groupName, $lkAlias, $v['base'], $lkItem);
                }
            }
            unset($v);
        }
        
        if (array_key_exists('db', $item)) {
            $tableAlias = $item['tableAlias'];
            $db = $item['db'];
            if (preg_match('/^\w+$/', $db)) {
                $item['refDb'] = $tableAlias . '.' . $db;
            } else {
                $item['refDb'] = preg_replace_callback(
                    '/(\b[a-z]\w*\b\s*(?![\(]))/i', // TODO: funtionName + space + ( eg: '1000 + id + sin (e)'
                    function ($matches) use ($tableAlias) {
                        return $tableAlias . '.' . $matches[0];
                    },
                    $db
                );
            }
        }
        
        // Add reference
        if (array_key_exists('teplateItems', $item)) {
            $tableAlias = $item['tableAlias'];
            foreach ($item['teplateItems'] as $k => &$v) {
                $refKey = Lot::getItem(['linkedTo', $v['base'] , 'ref'], $item);
                $baseName = $v['base'];
                if ($refKey) {
                    $v['ref'] = $refKey;
                    unset($item['linkedTo'][$baseName]);
                } else {
                    $form = $this->tableSources[$tableAlias]['from'];
                    $lkAlias = $this->tableSources[$tableAlias]['alias'];
                    $lk = $this->links[$form];
                    if (array_key_exists($baseName, $lk['items'])) {
                        $v['ref'] = $this->linkVirtualItem($groupName, $lkAlias, $baseName, $lk['items'][$baseName]);
                    } elseif (array_key_exists($baseName, $lk['base'])) {
                        $v['ref'] = $this->linkVirtualItem($groupName, $lkAlias, $baseName, $lk['base'][$baseName]);
                    } else {
                        throw new DebugException(
                            "Base \"{$baseName}\" not fount (on \"{$newRef}\").",
                            $item
                        );
                    }
                }
                if (array_key_exists('linkedTo', $item) && count($item['linkedTo']) === 0) {
                    unset($item['linkedTo']);
                }
            }
            unset($v);
        }
        
        // sortBy
        if (array_key_exists('sortBy', $item) && $item['sortBy']) {
            $sortBy = &$item['sortBy'];
            if (array_key_exists('linkedTo', $sortBy)) {
                foreach ($sortBy['linkedTo'] as &$v) {
                    $lkItem = $this->getLinkItemByLinkTo($tableAliasInit, $v);
                    $v['ref'] = $this->linkVirtualItem(
                        $groupName, $lkItem['tableAlias'], $v['base'], $lkItem
                    );
                }
                unset($v);
            }
            
            $tableAlias = $item['tableAlias'];
            foreach ($sortBy['items'] as $k => &$v) {
                $refKey = Lot::getItem(['linkedTo', $v['base'] , 'ref'], $sortBy);
                $baseName = $v['base'];
                if ($refKey) {
                    $v['ref'] = $refKey;
                    unset($sortBy['linkedTo'][$baseName]);
                } else {
                    $ref = $this->getRef($groupName, $tableAlias, $baseName);
                    if ($ref) {
                        $v['ref'] = $ref;
                    } else {
                        $form = $this->tableSources[$tableAlias]['from'];
                        $lk = $this->links[$form];
                        if (array_key_exists($baseName, $lk['items'])) {
                            $v['ref'] = $baseName   ;
                        } elseif (array_key_exists($baseName, $lk['base'])) {
                            $v['ref'] = $this->linkVirtualItem($groupName, $tableAlias, $baseName, $lk['base'][$baseName]);
                        } else {
                            throw new DebugException(
                                "Base sortBy \"{$baseName}\" not fount (on \"{$newRef}\").",
                                $item
                            );
                        }
                    }
                }
                if (array_key_exists('linkedTo', $sortBy) &&
                    count($sortBy['linkedTo']) === 0
                ) {
                    unset($sortBy['linkedTo']);
                }
                $ref = $v['ref'];
                if (!array_key_exists($ref, $this->items[$groupName])) {
                    throw new DebugException(
                        "SortBy base \"{$baseName}\" and ref \"{$ref}\" not found on '{$groupName}'.",
                        [
                            'sortBy-items' => $sortBy['items'],
                            $groupName => $this->items[$groupName]
                        ]
                    );
                }
                if (!array_key_exists('db', $this->items[$groupName][$ref])) {
                    throw new DebugException(
                        "SortBy base \"{$baseName}\" and ref \"{$ref}\" without db attribute on '{$groupName}'.",
                        [
                            'sortBy-items' => $sortBy['items'],
                            $groupName => $this->items[$groupName]
                        ]
                    );
                }
                $v['refDb'] = $this->items[$groupName][$ref]['refDb'];
            }
            unset($v);
            unset($sortBy);
        }
        
        return $newRef;
    }
    
    protected function getLinkItemByLinkTo($fromAlias, $v) {
        $linkId = $fromAlias;
        if ($v['link']) {
            $linkId .= '|' . $v['link'];
        }
        return $this->getLinkItemById($linkId, $v['base'], $v['linkedWith']);
    }
    
    protected function getLinkItemById($linkId, $baseName, $linkedWith = null) {
        if (!array_key_exists($linkId, $this->links)) {
            if (!$linkedWith) {
                throw new DebugException(
                    "LinkId \"{$linkId}\" not exist.",
                    $this->tableSources
                );
            }
            $playerNames = Handler::parseLinkText($linkedWith);
            $modelName = $playerNames['model'];
            if (!array_key_exists('grid', $playerNames)) {
                throw new \Exception(
                    "Link \"{$linkedWith}\" without a grid name."
                );
            }
            $model = Handler::getModel($modelName);
            $grid = $model->getGridColumns($playerNames['grid']);
            $alias = $this->addTable($linkId, $grid);
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
            $ref = $this->getRef($groupName, $lkAlias, $lkBaseName);
            if (!$ref) {
                $ref = $this->linkVirtualItemByBaseName($groupName, $lkAlias, $lkBaseName);
            }
            $this->tableSources[$tableAlias]['fromField'] = 
                Lot::getItem([$groupName, $ref, 'refDb'], $this->items);
        }
        return $tableAlias;
    }
}
