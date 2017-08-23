<?php
class Data2Html_Model_Link
{
    protected $culprit = '';
    protected $debug = false;
    
    protected $originSet = null;
    
    protected $soutceTables = array();
    protected $links = array();
    protected $linkDone = false;
    protected $items = array();
    protected $refItems = array();
    
    public function __construct($fromCulprit, $set)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Link of {$fromCulprit}";
        
        $this->originSet = $set;
        $this->addTable(null, $set);
        $this->add('main', $set->getItems());
        
        // Check default sort
        $dafaultSort = $this->originSet->getSort();
        if ($dafaultSort) {
            if (substr($dafaultSort, 0, 1) === '!') {
                $dafaultSort = substr($dafaultSort, 1);
            }
            $sortBy = Data2Html_Value::getItem(
                $this->items['main'],
                array($dafaultSort, 'sortBy')
            );
            if (!$sortBy) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: Default sort '{$dafaultSort}' not found or don't have sortBy .",
                    $this->items['main']
                );
            }
        }
    }

    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = array(
                'from' => $this->getFrom(),
                'refItems' => $this->refItems,
              //  'links' => $this->links,
                'items' => $this->items,
            );
        }
        Data2Html_Utils::dump($this->culprit, $subject);
    }
           
    public function getItems($groupName = null) {
        if (!$this->linkDone) {
            $this->linkKeys();
        } 
        return Data2Html_Value::getItem(
            $this->items,
            ($groupName ? $groupName : 'main')
        );
    }

    public function getFrom() {
        if (!$this->linkDone) {
            $this->linkKeys();
        } 
        return $this->soutceTables;
    }
    
    public function getKeys() {
        if (!$this->linkDone) {
            $this->linkKeys();
        } 
        return $this->soutceTables['T0']['keys'];
    }
    
    public function getVisualItems() {
        $visualItems = array();
        $words = array('display', 'format', 'size', 'title', 'type', 'validations'); 
        $lkItems = $this->getItems();
        foreach ($lkItems as $k => $v) {
            if (!Data2Html_Value::getItem($v, 'virtual')) {
                $item = array();
                $visualItems[$k] = &$item;
                foreach ($words as $w) {
                    $vv = Data2Html_Value::getItem($v, $w);
                    if ($vv) {
                        $item[$w] = $vv;
                    }
                }
                unset($item);
            }
        }
        return $visualItems;
    }
    
    public function add($groupName, $fromItems)
    {
        if ($this->linkDone) {
            throw new Data2Html_Exception(
                "{$this->culprit}: Link is done, It is not possible to add more sets.",
                $this->items[$groupName]
            );
        }
        $tableAlias = $this->links['T0']['alias'];
        $baseItems = $this->links['T0']['base'];
        
        $items = array();
        $this->items[$groupName] = &$items;
        foreach ($fromItems as $key => $item) {
            $refItem = $this->getRefByItem($groupName, $tableAlias, $item);
            if (!$refItem) {
                $item['tableAlias'] = $tableAlias;
            } else {
                if(array_key_exists('virtual', $items[$refItem])) {
                    $item = $items[$refItem];
                    unset($item['virtual']);
                    $baseName = null;
                    if (array_key_exists('base', $item)) {
                        $baseName = $item['base'];
                    } elseif (array_key_exists('db', $item)) {
                        $baseName = $item['db'];
                    }
                    unset($this->refItems[$groupName][$tableAlias][$baseName]);
                    unset($items[$refItem]);
                }
            }
            $this->linkItem($groupName, $key, $item);
        }
    }
    
    protected function linkKeys()
    {
        $groupName = 'main';
        foreach ($this->soutceTables as &$fromTable) {
            $tableAlias = $fromTable['alias'];
            foreach ($fromTable['keys'] as $baseName => &$v) {
                $ref = $this->getRef($groupName, $tableAlias, $baseName);
                if (!$ref) {
                    $lkItem = $this->getLinkItemByBase($tableAlias, $baseName);
                    $ref = $this->linkVirtualItem($groupName, $tableAlias, $baseName, $lkItem);
                }    
                $refDb = Data2Html_Value::getItem(
                    $this->items,
                    array($groupName, $ref, 'refDb')
                );
                if (!$refDb) {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: Key base \"{$baseName}\" of \"{$tableAlias}\" without refDb.",
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
    
    protected function addRefByItem($groupName, $tableAlias, $item, $ref) {
        $this->refItems[$groupName][$tableAlias][$this->getRefBase($item)] = $ref;
        return $ref;
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
    
    protected function getRef($groupName, $tableAlias, $baseName) {
        return Data2Html_Value::getItem(
            $this->refItems,
            array($groupName, $tableAlias, $baseName)
        );
    }
        
    protected function linkVirtualItem($groupName, $tableAlias, $base, $item) {
        $item['tableAlias'] = $tableAlias;
        $item['ref_link'] = $tableAlias . '|' . $base;
        $key = Data2Html_Utils::toCleanName($item['ref_link'], '_');
        if (array_key_exists($key, $this->items[$groupName])) {
            return $key;
        }
        $item['virtual'] = true;
        return $this->linkItem($groupName, $key, $item);
    }
        
    protected function linkItem($groupName, $key, $item) {
        $tableAlias = $item['tableAlias'];
        
        //Check if item already exist
        $ref = $this->getRefByItem($groupName, $tableAlias, $item);
        if ($ref) {
            return $ref;
        }
        
        // New item
        $this->items[$groupName][$key] = &$item;
        
        if (array_key_exists('linkedTo', $item)) {
            $itemBase = Data2Html_Value::getItem($item, 'base');
            foreach ($item['linkedTo'] as $k => &$v) {
                $lkItem = $this->getLinkItemByLinkTo($tableAlias, $v);
                $lkAlias = $lkItem['tableAlias'];
                if (count($item['linkedTo']) === 1) {
                    if(!array_key_exists('teplateItems', $item)) { // Merge fields
                        unset($item['linkedTo']);
                        unset($item['base']);
                        if (array_key_exists('sortBy', $item) &&
                            array_key_exists('sortBy', $lkItem)
                        ) {
                            unset($lkItem['sortBy']);
                        }
                        $item = array_replace_recursive(array(), $lkItem, $item);
                        $item['tableAlias'] = $lkAlias;
                        $tableAlias = $lkAlias; // Refesh table alias
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
        if (array_key_exists('teplateItems', $item)) {
            foreach ($item['teplateItems'] as $k => &$v) {
                $refKey = Data2Html_Value::getItem($item, array('linkedTo', $v['base'] , 'ref'));
                $baseName = $v['base'];
                if ($refKey) {
                    $v['ref'] = $refKey;
                    unset($item['linkedTo'][$baseName]);
                } else {
                    $form = $this->soutceTables[$tableAlias]['from'];
                    $lkAlias = $this->soutceTables[$tableAlias]['alias'];
                    $lk = $this->links[$form];
                    if (array_key_exists($baseName, $lk['items'])) {
                        $v['ref'] = $this->linkVirtualItem(
                            $groupName, $lkAlias, $baseName, $lk['items'][$baseName]);
                    } elseif (array_key_exists($baseName, $lk['base'])) {
                        $v['ref'] = $this->linkVirtualItem(
                            $groupName, $lkAlias, $baseName, $lk['base'][$baseName]);
                    } else {
                        throw new Data2Html_Exception(
                            "{$this->culprit}: Base \"{$baseName}\" not fount (on \"{$key}\").",
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
        if (array_key_exists('sortBy', $item) && $item['sortBy']) {
            $sortBy = &$item['sortBy'];
            if (array_key_exists('linkedTo', $sortBy)) {
                foreach ($sortBy['linkedTo'] as $k => &$v) {
                    $lkItem = $this->getLinkItemByLinkTo($tableAlias, $v);
                    $lkAlias = $lkItem['tableAlias'];
                    $v['ref'] = $this->linkVirtualItem($groupName, $lkAlias, $v['base'], $lkItem);
                }
                unset($v);
            }
            foreach ($sortBy['items'] as $k => &$v) {
                $refKey = Data2Html_Value::getItem($sortBy, array('linkedTo', $v['base'] , 'ref'));
                $baseName = $v['base'];
                if ($refKey) {
                    $v['ref'] = $refKey;
                    unset($sortBy['linkedTo'][$baseName]);
                } else {
                    $ref = $this->getRef($groupName, $tableAlias, $baseName);
                    if ($ref) {
                        $v['ref'] = $ref;
                    } else {
                        $form = $this->soutceTables[$tableAlias]['from'];
                        $lk = $this->links[$form];
                        if (array_key_exists($baseName, $lk['items'])) {
                            $v['ref'] = $this->linkVirtualItem($groupName, $tableAlias, $baseName, $lk['items'][$baseName]);
                        } elseif (array_key_exists($baseName, $lk['base'])) {
                            $v['ref'] = $this->linkVirtualItem($groupName, $tableAlias, $baseName, $lk['base'][$baseName]);
                        } else {
                            throw new Data2Html_Exception(
                                "{$this->culprit}: Base sortBy \"{$baseName}\" not fount (on \"{$key}\").",
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
                if (!array_key_exists('db', $this->items[$groupName][$ref])) {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: SortBy base \"{$baseName}\" to ref \"{$ref}\" without db attribute (on \"{$key}\").",
                        $item
                    );
                }
                $v['refDb'] = $this->items[$groupName][$ref]['refDb'];
            }
            unset($v);
            unset($sortBy);
        }
        
        // reference
        return $this->addRefByItem($groupName, $tableAlias, $item, $key);
    }
    
    protected function getLinkItemByLinkTo($fromAlias, $v) {
        $linkId = $fromAlias;
        if ($v['link']) {
            $linkId .= '|' . $v['link'];
        }
        return $this->getLinkItemById($linkId, $v['base'], $v['linkedWith']);
    }
        
    protected function getLinkItemByBase($fromAlias, $baseName) {
        $linkId = Data2Html_Value::getItem($this->soutceTables, array($fromAlias, 'from'));
        return $this->getLinkItemById($linkId, $baseName);
    }
    
    protected function getLinkItemById($linkId, $baseName, $linkedWith = null) {
        if (!array_key_exists($linkId, $this->links)) {
            if (!$linkedWith) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: LinkId \"{$linkId}\" not exist.",
                    $this->soutceTables
                );
            }
            $playerNames = Data2Html_Handler::parseLinkText($linkedWith);
            $modelName = $playerNames['model'];
            if (!array_key_exists('grid', $playerNames)) {
                throw new Exception(
                    "{$this->culprit}: Link \"{$linkedWith}\" without a grid name."
                );
            }
            $model = Data2Html_Handler::getModel($modelName);
            $grid = $model->getGrid($playerNames['grid']);
            $alias = $this->addTable($linkId, $grid->getColumnsSet());
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
            throw new Exception(
                "{$this->culprit}: Base \"{$baseName}\" from \"{$fromAlias}\" on grid \"{$linkedWith}\" not found."
            );
        }
        $lkItem['tableAlias'] = $l['alias'];
        $lkItem['ref_link'] = $l['alias'] . '|' . $baseName;
        return $lkItem;
    }

    protected function addTable($linkId, $set) {
        $keys = $set->getKeys();
        $tableName = $set->getTableName();
        
        $tableAlias = 'T' . count($this->soutceTables);
        $linkId = $linkId ? $linkId : $tableAlias;
        
        $this->links[$linkId] = array(
            'alias' => $tableAlias,
            'items' => $set->getItems(),
            'base' => $set->getBase()->getItems()
        );
        $fromId = explode('|', $linkId);
        $this->soutceTables[$tableAlias] = array(
            'from' => $linkId,
            'fromAlias' => $fromId[0],
            'alias' => $tableAlias,
            'table' => $tableName,
            'keys' => $keys
        );
        $groupName = 'main';
        if ($tableAlias === 'T0') {
            $this->soutceTables[$tableAlias]['fromField'] = null; 
        } else {
            $lkAlias = $fromId[0];
            $lkBaseName = $fromId[1];
            $ref = $this->getRef($groupName, $lkAlias, $lkBaseName);
            if (!$ref) {
                $lkItem = $this->getLinkItemByBase($lkAlias, $lkBaseName);
                $ref = $this->linkVirtualItem($groupName, $lkAlias, $lkBaseName, $lkItem);
            }
            $this->soutceTables[$tableAlias]['fromField'] = 
                Data2Html_Value::getItem(
                    $this->items, array($groupName, $ref, 'refDb')
                );
        }
        return $tableAlias;
    }
}
