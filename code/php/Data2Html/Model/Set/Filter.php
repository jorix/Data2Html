<?php
class Data2Html_Model_Set_Filter extends Data2Html_Model_Set 
{
    public function parseItems($items, $baseFields = array())
    {
        $baseFiledsDx = new Data2Html_Collection($baseFields);
        $pFieldDx = new Data2Html_Collection();
        foreach ($items as $k => $v) {
            $pKey = 0;
            $pField = null;
            if (is_array($v)) {
                $pKey = $k;
                $pField = $v;
            } elseif (is_string($v)) {
                if (is_string($k)) {
                    $pKey = $k;
                    $pField = array('name' => $k, 'check' => $v);
                } else {
                    throw new Exception(
                        "{$this->culprit}: String \"{$v}\" needs a value as string or array."
                    ); 
                }
            }
            $pFieldDx->set($pField);              
            $name = $pFieldDx->getString('name');
            if ($name) {
                // TODO: see link
                $baseName = explode('[', $name);
                $baseField = $baseFiledsDx->getArray($baseName[0]);
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
            if (!$db && array_key_exists('check', $pField) ) {
                throw new Exception(
                    "{$this->culprit}: Key `{$k}=>[...]` with check=\"{$pField['check']}\" requires a `db` attribute."
                );
            }
            if (is_int($pKey)) {
                $pKey = $name.'_'.$pFieldDx->getString('check', '');
            }
            $this->addParse($pKey, $pField);
        }
        return $this->setItems;
    }
}
