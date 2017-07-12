<?php
class Data2Html_Model_Link
{
    protected $culprit = '';
    protected $debug = false;
    
    protected $sort = null;
    
    protected $tables = array();
    protected $links = array();
    protected $items = array();
    protected $refItems = array();
    
    public function __construct($fromCulprit, $set)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Link of {$fromCulprit}";
        
        $this->addTable(null, $set);
        $this->sort = $set->getSort();
        $this->add('columns', $set->getItems());
    }
    
        
    function getSort() {
        return $this->sort;
    }

    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = array(
                'tables' => $this->getFromTables(),
               // 'refItems' => $this->refItems,
              //  'links' => $this->links,
                'items' => $this->items,
            );
        }
        Data2Html_Utils::dump($this->culprit, $subject);
    }
    
    public function add($groupName, $fromItems)
    {
        $tableAlias = $this->links['T0']['alias'];
        $baseItems = $this->links['T0']['base'];
        $this->items[$groupName] = array();
        foreach ($fromItems as $key => $item) {
            $item['tableAlias'] = $tableAlias;
            $this->addLinkedItem($groupName, $key, $item);
        }
        if ($groupName === 'columns') {
            $sort = $this->getSort();
            if ($sort) {
                if (substr($sort, 0, 1) === '!') {
                    $sort = substr($sort, 1);
                }
                $sortBy = Data2Html_Value::getItem($this->items[$groupName], array($sort, 'sortBy'));
                if (!$sortBy) {
                    throw new Data2Html_Exception(
                        "getOrderBy(): Default sort '{$sort}' not found or don't have sortBy .",
                        $this->items[$groupName]
                    );
                }
            }
        }
    }
    
    protected function linkKeys()
    {
        $groupName = 'columns';
        foreach ($this->tables as &$fromTable) {
            $tableAlias = $fromTable['alias'];
            foreach ($fromTable['keys'] as $k => &$v) {
                $baseName = $v['base'];
                $ref = $this->getRef($groupName, $tableAlias, $baseName);
                if (!$ref) {
                    $lkItem = $this->getLinkItemByBase($tableAlias, $baseName);
                    $ref = $this->addLinkedVirtual($groupName, $tableAlias, $baseName, $lkItem);
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
    }
    
    public function get($groupName) {
        return Data2Html_Value::getItem($this->items, $groupName);
    }

    public function getFromTables() {
        $this->linkKeys();
        return $this->tables;
    }
    
    protected function getRef($groupName, $tableAlias, $baseName) {
        return Data2Html_Value::getItem(
            $this->refItems,
            array($groupName, $tableAlias, $baseName)
        );
    }
        
    protected function addLinkedVirtual($groupName, $tableAlias, $base, $item) {
        $item['tableAlias'] = $tableAlias;
        $item['ref_link'] = $tableAlias . '|' . $base;
        $key = Data2Html_Utils::toCleanName($item['ref_link'], '_');
        if (array_key_exists($key, $this->items[$groupName])) {
            return $key;
        }
        $item['virtual'] = true;
        return $this->addLinkedItem($groupName, $key, $item);
    }
        
    protected function addLinkedItem($groupName, $key, $item) {
        $baseName = null;
        if (array_key_exists('base', $item)) {
            $baseName = $item['base'];
        } elseif (array_key_exists('db', $item)) {
            $baseName = $item['db'];
        }
        $tableAlias = $item['tableAlias'];
        
        //Check if item already exist
        if ($baseName) {
            $ref = $this->getRef($groupName, $tableAlias, $baseName);
            if ($ref) {
                return $ref;
            }
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
                        $v['ref'] = $this->addLinkedVirtual($groupName, $lkAlias, $v['base'], $lkItem);
                    }
                } else {
                    $v['ref'] = $this->addLinkedVirtual($groupName, $lkAlias, $v['base'], $lkItem);
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
                    $form = $this->tables[$tableAlias]['from'];
                    $lkAlias = $this->tables[$tableAlias]['alias'];
                    $lk = $this->links[$form];
                    if (array_key_exists($baseName, $lk['items'])) {
                        $v['ref'] = $this->addLinkedVirtual(
                            $groupName, $lkAlias, $baseName, $lk['items'][$baseName]);
                    } elseif (array_key_exists($baseName, $lk['base'])) {
                        $v['ref'] = $this->addLinkedVirtual(
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
                    $v['ref'] = $this->addLinkedVirtual($groupName, $lkAlias, $v['base'], $lkItem);
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
                        $form = $this->tables[$tableAlias]['from'];
                        $lk = $this->links[$form];
                        if (array_key_exists($baseName, $lk['items'])) {
                            $v['ref'] = $this->addLinkedVirtual($groupName, $tableAlias, $baseName, $lk['items'][$baseName]);
                        } elseif (array_key_exists($baseName, $lk['base'])) {
                            $v['ref'] = $this->addLinkedVirtual($groupName, $tableAlias, $baseName, $lk['base'][$baseName]);
                        } else {
                            throw new Data2Html_Exception(
                                "{$this->culprit}: Base sortBy \"{$baseName}\" not fount (on \"{$key}\").",
                                $item
                            );
                        }
                    }
                }
                if (array_key_exists('linkedTo', $sortBy) && count($sortBy['linkedTo']) === 0) {
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
        $baseName = null;
        if (array_key_exists('base', $item)) {
            $baseName = $item['base'];
        } elseif (array_key_exists('db', $item)) {
            $baseName = $item['db'];
        }
        $this->refItems[$groupName][$tableAlias][$baseName] = $key;
        
        return $key;
    }
    
    protected function getLinkItemByLinkTo($fromAlias, $v) {
        $linkId = $fromAlias;
        if ($v['link']) {
            $linkId .= '|' . $v['link'];
        }
        return $this->getLinkItemById($linkId, $v['base'], $v['linkedWith']);
    }
        
    protected function getLinkItemByBase($fromAlias, $baseName) {
        $linkId = Data2Html_Value::getItem($this->tables, array($fromAlias, 'from'));
        return $this->getLinkItemById($linkId, $baseName);
    }
    
    protected function getLinkItemById($linkId, $baseName, $linkedWith = null) {
        if (!array_key_exists($linkId, $this->links)) {
            if (!$linkedWith) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: LinkId \"{$linkId}\" not exist.",
                    $this->tables
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
        $model = $set->getModel();
        $keys = $set->getKeys();
        $tableName = $set->getTableName();
        
        $tableAlias = 'T' . count($this->tables);
        $linkId = $linkId ? $linkId : $tableAlias;
        
        $this->links[$linkId] = array(
            'alias' => $tableAlias,
            'items' => $set->getItems(),
            'base' => $model->getBase()->getItems()
        );
        $fromId = explode('|', $linkId);
        $this->tables[$tableAlias] = array(
            'from' => $linkId,
            'fromAlias' => $fromId[0],
            'alias' => $tableAlias,
            'table' => $tableName,
            'keys' => $keys
        );
        $groupName = 'columns';
        if ($tableAlias === 'T0') {
            $this->tables[$tableAlias]['fromField'] = null; 
        } else {
            $lkAlias = $fromId[0];
            $lkBaseName = $fromId[1];
            $ref = $this->getRef($groupName, $lkAlias, $lkBaseName);
            if (!$ref) {
                $lkItem = $this->getLinkItemByBase($lkAlias, $lkBaseName);
                $ref = $this->addLinkedVirtual($groupName, $lkAlias, $lkBaseName, $lkItem);
            }
            $this->tables[$tableAlias]['fromField'] = Data2Html_Value::getItem($this->items, array($groupName, $ref, 'refDb'));
        }
        return $tableAlias;
    }
}
