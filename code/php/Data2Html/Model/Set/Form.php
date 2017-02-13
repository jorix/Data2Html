<?php
class Data2Html_Model_Set_Form extends Data2Html_Model_Set 
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
            $pField = null;
            if (is_array($v)) {
                $pKey = $k;
                $pField = $v;
            } elseif (is_string($v)) {
                if (is_numeric($k)) {
                    throw new Exception(
                        "{$this->culprit}: String \"{$v}\" needs a value as array."
                    ); 
                } else {
                    $pKey = $v;
                    $pField = array('name' => $v);
                }
            }
            $pFieldDx->set($pField);              
            $name = $pFieldDx->getString('name');
            if ($name) {
                // TODO: see link
                $baseField = $baseFiledsDx->getArray($name);
                if (!$baseField) {
                    throw new Exception(
                        "{$this->culprit}: Name \"{$name}\" not exist on base fields."
                    );
                }
                $pField = array_replace_recursive(array(), $baseField, $pField);
                $db = $pFieldDx->getString('db');
            } else {
                $db = $pFieldDx->getString('db');
                $name = $db;
            }
            $this->addParse($pKey, $pField);
        }
    }
}
