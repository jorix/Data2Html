<?php
namespace Data2Html\Model\Set;

class Filter extends \Data2Html\Model\Set
{
    protected $keywords = array('check' => 'string');
    
    protected $startToChk = array(
        '<=' => 'EQ',
        '>=' => 'EQ',
        '=' => 'EQ',
        '_%' => 'SK',
        '%' => 'LK'
    );
    
    protected function beforeParseItem(&$key, &$field)
    {
        if (is_string($field)) {
            if (is_string($key)) {
                $field = ['base' => $key, 'check' => $field];
                $key = $key . '_' . $field;
            } else {
                foreach ($this->startToChk as $k => $v) {
                    if (self::startsWith($field, $k)) {
                        $field = array(
                            'base' => substr($field, strlen($k)),
                            'check' => $v
                        );
                        break;
                    }
                }
                if (is_string($field)) {
                    throw new \Exception(
                        "String \"{$field}\" needs a value as string or array."
                    );
                }
            }
        } elseif (is_array($field)) {
            if (!array_key_exists('base', $field) && is_string($key)) {
                $field['base'] = $key;
            }
            if (array_key_exists('base', $field)) {
                $base = $field['base'];
                foreach ($this->startToChk as $k => $v) {
                    if (self::startsWith($base, $k)) {
                        $field['base'] = substr($base, strlen($k));
                        $field['check'] = $v;
                        $key = $field['base'] . '_' . $v;
                        break;
                    }
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
    
    protected function beforeApplyBase(&$baseField, &$field)
    {
        if (array_key_exists('validations', $baseField) &&
            array_key_exists('required', $baseField['validations'])
        ) {
            $baseField['validations']['required'] = false;
        } 
    }
    
    protected function beforeAddItem(&$key, &$field)
    {
        if (
            !array_key_exists('db', $field) &&
            !array_key_exists('base', $field) &&
            array_key_exists('check', $field)
        ) {
            throw new \Exception(
                "Key `{$key}=>[...]` with check=\"{$field['check']}\" requires a `db` or `base` attributes."
            );
        }
        return true;
    }
    
    protected function parseSortBy($sortBy, $baseItems) {
        return null;
    }
}
