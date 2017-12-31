<?php
class Data2Html_Model_Set_Filter extends Data2Html_Model_Set
{
    protected $attributeNames = array(
        'layout' => 'attribute'
    );
    
    protected $keywords = array('check' => 'string');
    
    protected $startToChk = array(
        '<=' => 'EQ',
        '>=' => 'EQ',
        '=' => 'EQ',
        '%' => 'LK'
    );
    
    protected function beforeParseItem(&$key, &$field)
    {
        $startsWith = function($haystack, $needle) {
            return (
                substr($haystack, 0, strlen($needle)) === $needle
            );
        };
        if (is_string($field)) {
            if (is_string($key)) {
                $field = array('base' => $key, 'check' => $field);
            } else {
                foreach ($this->startToChk as $k => $v) {
                    if ($startsWith($field, $k)) {
                        $field = array(
                            'base' => substr($field, strlen($k)),
                            'check' => $v
                        );
                        break;
                    }
                }
                if (is_string($field)) {
                    throw new Exception(
                        "{$this->culprit}: String \"{$field}\" needs a value as string or array."
                    );
                }
            }
        }
        
        if (is_int($key) && 
            array_key_exists('base', $field) &&
            array_key_exists('check', $field)
        ) {
            $key = $field['base'] . '_' . strtolower($field['check']);
        }
        return true;
    }
    
    protected function beforeAddItem(&$key, &$field)
    {
        if (
            !array_key_exists('db', $field) &&
            !array_key_exists('base', $field) &&
            array_key_exists('check', $field)
        ) {
            throw new Exception(
                "{$this->culprit}: Key `{$key}=>[...]` with check=\"{$field['check']}\" requires a `db` or `base` attributes."
            );
        }
        return true;
    }
    
    protected function parseSortBy($sortBy, $baseItems) {
        return null;
    }
}
