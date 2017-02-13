<?php
class Data2Html_Model_Set_Columns extends Data2Html_Model_Set 
{
    public function parseItems($items, $baseFields = array())
    {
        $baseFiledsDx = new Data2Html_Collection($baseFields);
        $pFieldDx = new Data2Html_Collection();
        if (count($items) === 0) { // if no columns then set as all baseFields
            $items = $baseFields;
        }
        foreach ($items as $k => $v) {
            $pKey = 0;
            $pCol = null;
            if (is_string($v)) {
                if (substr($v, 0, 1) === '=') { // Is a value
                    $this->addParse($k, array('value' => substr($v, 1)));
                } elseif (preg_match($this->matchLinkedOnce, $v)) { // Is a link
                    $this->addParse($k, array('db' => $v));
                } else {
                    $pCol = $baseFiledsDx->getArray($v);
                    if (!$pCol) {
                        throw new Exception(
                            "{$this->culprit}: Field `{$v}` not exist on base fields."
                        );
                    }
                    if (is_int($k)) {
                        $this->addParse($v, $pCol);
                    } else {
                        $this->addParse($k, $pCol);
                    }
                }
            } elseif (is_array($v)) {
                $nameField = Data2Html_Value::getItem($v, 'name');
                if ($nameField) {
                    $baseField = $baseFiledsDx->getArray($nameField);
                    if (!$baseField) {
                        throw new Exception(
                            "{$this->culprit}: Field `{$k}` not exist on base fields."
                        );
                    }
                    $this->addParse($k, $v, $baseField);
                } else {
                    $this->addParse($k, $v);
                }
            }
        }
        return $this->setItems;
    }
}
