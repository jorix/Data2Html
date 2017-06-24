<?php
class Data2Html_Model_Link
{
    protected $culprit = '';
    protected $debug = false;
    protected $tables = array();
    protected $links = array();
    protected $items = array();
    protected $keyItems = array();
    
    public function __construct($fromCulprit, $set)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Link for {$fromCulprit}";
        
        $this->addTable(null, $set);
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
    public function dump()
    {
        if (!$this->debug) {
            echo "Debug mode is not activated, activate it to make a dump!";
            return;
        }
        Data2Html_Utils::dump($this->culprit, array(
            'tables' => $this->tables,
            'keyItems' => $this->keyItems,
           // 'links' => $this->links,
            'items' => $this->items,
        ));
    }
    public function add($groupName, $fromItems) {
        $tableAlias = $this->links['T0']['alias'];
        $baseItems = $this->links['T0']['base'];
        
        $virtuals = array();
        foreach ($fromItems as $key => $item) {
            // $item['tableAlias'] = $tableAlias;
            // if (!array_key_exists('linkedTo', $item)) {
                    // $this->keyItems[$tableAlias . '|' . $key] = $key;
            // } else {
                // $itemBase = Data2Html_Value::getItem($item, 'base');
                // foreach ($item['linkedTo'] as $k => &$v) {
                    // list($lkAlias, $lkItem) = $this->getLinkItem($tableAlias, $v);
                    // $virtualKey = null;
                    // if ($k === $itemBase && 
                        // !array_key_exists('teplateItems', $item)
                    // ) { // Merge fields
                        // unset($item['linkedTo']);
                        // unset($item['base']);
                        // $item = array_replace_recursive(array(), $lkItem, $item);
                        // $item['tableAlias'] = $lkAlias;
                        // $this->keyItems[$tableAlias . '|' . $key] = $key;
                    // } else { // linked with a virtual item
                        // $virtualKey = $lkAlias . '|' . $v['base'];
                    // }
                    // if ($virtualKey) {
                        // $this->keyItems[$virtualKey] = null;
                        // $v['virtual'] = $virtualKey;
                        // $virtuals[$virtualKey] = $lkItem;
                    // }
                // }
                // unset($v);
            // }
            // if (array_key_exists('teplateItems', $item)) {
                // foreach ($item['teplateItems'] as $k => &$v) {
                    // $itemKey = $item['tableAlias'] . '|' . $v['base'];
                    // if (array_key_exists($itemKey, $this->keyItems)) {
                        // $v['virtual'] = $itemKey;
                    // } else {
                        // $virtualKey = Data2Html_Value::getItem($item, array('linkedTo', $v['base'] , 'virtual'));
                        // if ($virtualKey) {
                            // $v['virtual'] = $virtualKey;
                        // } else {
                            // if(array_key_exists($v['base'], $baseItems)) {
                                // $virtualKey = $tableAlias . '|' . $v['base'];
                                // $this->keyItems[$virtualKey] = null;
                                // $v['virtual'] = $virtualKey;
                                // $virtuals[$virtualKey] = $baseItems[$v['base']];
                            // }
                        // }
                    // }
                // }
                // unset($v);
            // } 
            $this->newItem($tableAlias, $baseItems, $key, $item, $virtuals);
            $this->items[$groupName][$key] = $item;
        }
        $virt = array();
        foreach ($virtuals as $key => $item) {
            $newKey = Data2Html_Utils::toCleanName($key, '_');
            if (array_key_exists($newKey, $this->keyItems)) {
                $newKey = 'liiii';
            }
//            $this->newItem($tableAlias, $baseItems, $newKey, $item, $virt);
            $this->keyItems[$key] = $newKey;
        }
    }
    protected function newItem($tableAlias, $baseItems, $key, &$item, &$virtuals) { 
        $item['tableAlias'] = $tableAlias;
        if (!array_key_exists('linkedTo', $item)) {
                $this->keyItems[$tableAlias . '|' . $key] = $key;
        } else {
            $itemBase = Data2Html_Value::getItem($item, 'base');
            foreach ($item['linkedTo'] as $k => &$v) {
                list($lkAlias, $lkItem) = $this->getLinkItem($tableAlias, $v);
                $virtualKey = null;
                if ($k === $itemBase && 
                    !array_key_exists('teplateItems', $item)
                ) { // Merge fields
                    unset($item['linkedTo']);
                    unset($item['base']);
                    $item = array_replace_recursive(array(), $lkItem, $item);
                    $item['tableAlias'] = $lkAlias;
                    $this->keyItems[$tableAlias . '|' . $key] = $key;
                } else { // linked with a virtual item
                    $virtualKey = $lkAlias . '|' . $v['base'];
                }
                if ($virtualKey) {
                    $this->keyItems[$virtualKey] = null;
                    $v['virtual'] = $virtualKey;
                    $virtuals[$virtualKey] = $lkItem;
                }
            }
            unset($v);
        }
        if (array_key_exists('teplateItems', $item)) {
            foreach ($item['teplateItems'] as $k => &$v) {
                $itemKey = $item['tableAlias'] . '|' . $v['base'];
                if (array_key_exists($itemKey, $this->keyItems)) {
                    $v['virtual'] = $itemKey;
                } else {
                    $virtualKey = Data2Html_Value::getItem($item, array('linkedTo', $v['base'] , 'virtual'));
                    if ($virtualKey) {
                        $v['virtual'] = $virtualKey;
                    } else {
                        if(array_key_exists($v['base'], $baseItems)) {
                            $virtualKey = $tableAlias . '|' . $v['base'];
                            $this->keyItems[$virtualKey] = null;
                            $v['virtual'] = $virtualKey;
                            $virtuals[$virtualKey] = $baseItems[$v['base']];
                        }
                    }
                }
            }
            unset($v);
        }
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
        return array($l['alias'], $lkItem);
    }
}
