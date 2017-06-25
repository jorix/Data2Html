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
        $this->culprit = "Link of {$fromCulprit}";
        
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
        foreach ($fromItems as $key => $item) {
            $item['tableAlias'] = $tableAlias;
            $this->addLinkedItem($groupName, $key, $item);
        }
    }
    protected function addLinkedItem($groupName, $key, $item) {
        $tableAlias = $item['tableAlias'];
        if (!$key) {
            $item['virtual'] = true;
            $key = Data2Html_Utils::toCleanName($item['ref_link'],'_');
        }
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
                    } else { // linked with a virtual item
                        $v['ref'] = $this->addLinkedItem($groupName, null, $lkItem);
                    }
                } else {
                    $v['ref'] = $this->addLinkedItem($groupName, null, $lkItem);
                }
                // if (array_key_exists('linkedTo', $item)) {
                    // $item['linkedTo'][$k] = $v;
                // }
            }
            unset($v);
        }
        if (array_key_exists('teplateItems', $item)) {
            foreach ($item['teplateItems'] as $k => &$v) {
                $refKey = Data2Html_Value::getItem($item, array('linkedTo', $v['base'] , 'ref'));
                if ($refKey) {
                    $v['ref'] = $refKey;
                    unset($item['linkedTo'][$v['base']]);
                } else {
                    if (array_key_exists($v['base'], $this->items[$groupName])) {
                        $v['ref'] = $v['base'];
                    } else {
                        $form = $this->tables[$tableAlias]['from'];
                        $baseItems = $this->links[$form]['base'];
                        $baseItem = $baseItems[$v['base']];
                        if (array_key_exists($v['base'], $baseItems)) {
                            $baseItem['tableAlias'] = $tableAlias;
                            $baseItem['ref_link'] = $tableAlias . '|' . $v['base'];
                            $v['ref'] = $this->addLinkedItem($groupName, null, $baseItem);
                        }
                    }
                }
                if (array_key_exists('linkedTo', $item) && count($item['linkedTo']) === 0) {
                    unset($item['linkedTo']);
                }
                // $item['teplateItems'][$k] = $v;
            }
            unset($v);
        }
        $this->items[$groupName][$key] = $item;
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
}
