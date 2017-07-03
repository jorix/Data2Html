<?php
class Data2Html_Model_Link
{
    protected $culprit = '';
    protected $debug = false;
    protected $tables = array();
    protected $links = array();
    protected $items = array();
    
    public function __construct($fromCulprit, $set)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Link of {$fromCulprit}";
        
        $this->addTable(null, $set);
    }

    public function dump()
    {
        if (!$this->debug) {
            echo "Debug mode is not activated, activate it to make a dump!";
            return;
        }
        Data2Html_Utils::dump($this->culprit, array(
            'tables' => $this->tables,
           // 'links' => $this->links,
            'items' => $this->items,
        ));
    }
    public function add($groupName, $fromItems) {
        $tableAlias = $this->links['T0']['alias'];
        $baseItems = $this->links['T0']['base'];
        $this->items[$groupName] = array();
        foreach ($fromItems as $key => $item) {
            $item['tableAlias'] = $tableAlias;
            $this->addLinkedItem($groupName, $key, $item);
        }
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
        $tableAlias = $item['tableAlias'];
        $this->items[$groupName][$key] = &$item;
        
        if (array_key_exists('linkedTo', $item)) {
            $itemBase = Data2Html_Value::getItem($item, 'base');
            foreach ($item['linkedTo'] as $k => &$v) {
                $lkItem = $this->getLinkItem($tableAlias, $v);
                $lkAlias = $lkItem['tableAlias'];
                if (count($item['linkedTo']) === 1) {
                    if(!array_key_exists('teplateItems', $item)) { // Merge fields
                        unset($item['linkedTo']);
                        unset($item['base']);
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
        if (array_key_exists('teplateItems', $item)) {
            foreach ($item['teplateItems'] as $k => &$v) {
                $refKey = Data2Html_Value::getItem($item, array('linkedTo', $v['base'] , 'ref'));
                $baseName = $v['base'];
                if ($refKey) {
                    $v['ref'] = $refKey;
                    unset($item['linkedTo'][$baseName]);
                } else {
                    if (array_key_exists($baseName, $this->items[$groupName])) {
                        $v['ref'] = $baseName;
                    } else {
                        $form = $this->tables[$tableAlias]['from'];
                        $lk = $this->links[$form];
                        if (array_key_exists($baseName, $lk['items'])) {
                            $v['ref'] = $this->addLinkedVirtual(
                                $groupName, $tableAlias, $baseName, $lk['items'][$baseName]);
                        } elseif (array_key_exists($baseName, $lk['base'])) {
                            $v['ref'] = $this->addLinkedVirtual(
                                $groupName, $tableAlias, $baseName, $lk['base'][$baseName]);
                        } else {
                            throw new Data2Html_Exception(
                                "{$this->culprit}: Base \"{$baseName}\" not fount on \"{$key}\".",
                                $item
                            );
                        }
                    }
                }
                if (array_key_exists('linkedTo', $item) && count($item['linkedTo']) === 0) {
                    unset($item['linkedTo']);
                }
            }
            unset($v);
        }
        if (array_key_exists('sortBy', $item)) {
            $sortBy = &$item['sortBy'];
            if (array_key_exists('linkedTo', $sortBy)) {
                $itemBase = Data2Html_Value::getItem($sortBy, 'base');
                foreach ($sortBy['linkedTo'] as $k => &$v) {
                    $lkItem = $this->getLinkItem($tableAlias, $v);
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
                    if (array_key_exists($baseName, $this->items[$groupName])) {
                        $v['ref'] = $baseName;
                    } else {
                        $form = $this->tables[$tableAlias]['from'];
                        $lk = $this->links[$form];
                        if (array_key_exists($baseName, $lk['items'])) {
                            $v['ref'] = $this->addLinkedVirtual($groupName, $tableAlias, $baseName, $lk['items'][$baseName]);
                        } elseif (array_key_exists($baseName, $lk['base'])) {
                            $v['ref'] = $this->addLinkedVirtual($groupName, $tableAlias, $baseName, $lk['base'][$baseName]);
                        } else {
                            throw new Data2Html_Exception(
                                "{$this->culprit}: sortBy \"{$baseName}\" not fount on \"{$key}\".",
                                $item
                            );
                        }
                    }
                }
                if (array_key_exists('linkedTo', $sortBy) && count($sortBy['linkedTo']) === 0) {
                    unset($sortBy['linkedTo']);
                }
            }
            unset($v);
            unset($sortBy);
        }
        return $key;
    }
    protected function getLinkItem($fromAlias, $v) {
        $link = $v['link'];
        $linkedWith = $v['linkedWith'];
        $baseName = $v['base'];
        
        $linkId = $fromAlias . '|' . $link;
        if (!array_key_exists($linkId, $this->links)) {
            $playerNames = Data2Html_Handler::parseLinkText($linkedWith);
            $modelName = $playerNames['model'];
            if (!array_key_exists('grid', $playerNames)) {
                throw new Exception(
                    "{$this->culprit}: Link \"{$linkedWith}\" without a grid name."
                );
            }
            $model = Data2Html_Handler::getModel($modelName);
            $grid = $model->getGrid($playerNames['grid']);
            $alias = $this->addTable($linkId, $grid->getTableSet());
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
                "{$this->culprit}: Base \"{$baseName}\" on grid \"{$linkedWith}\""
            );
        }
        $lkItem['tableAlias'] = $l['alias'];
        $lkItem['ref_link'] = $l['alias'] . '|' . $baseName;
        return $lkItem;
    }

    protected function addTable($from, $set) {
        $model = $set->getModel();
        $keys = $set->getKeys();
        $tableName = $set->getTableName();
        
        $tableAlias = 'T' . count($this->tables);
        $from = $from ? $from : $tableAlias;

        $this->links[$from] = array(
            'alias' => $tableAlias,
            'items' => $set->getItems(),
            'base' => $model->getBase()->getItems()
        );
        $this->tables[$tableAlias] = array(
            'from' => $from,
            'alias' => $tableAlias,
            'table' => $tableName,
            'keys' => $keys
        );
        return $tableAlias;
    }
}
