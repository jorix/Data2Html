<?php
namespace Data2Html\Model\Set;

Use \Data2Html\Data\Lot;

class Filter extends \Data2Html\Model\Set
{
    protected $keywords = array('check' => 'string');
    
    protected $startToChk = array(
        '<=' => 'LE',
        '>=' => 'GE',
        '=' =>  'EQ',
        '_%' => 'SK',
        '%' =>  'LK',
        '[]' => 'IN',
        '?' =>  'WR'
    );
    
    protected function beforeParseItem(&$key, &$field)
    {
        if (is_string($field)) {
            if (is_string($key)) {
                // For example as: 'field_name' => 'EQ'
                $field = ['base' => $key, 'check' => $field];
                $key = $key . '_' . $field;
            } else {
                // For example as: '=field_name'
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
        $key = str_replace(['[', ']'], '_', $key);
        return true;
    }
    
    protected function beforeApplyBase(&$field, $baseField)
    {
        if (Lot::getItem(['validations', 'required'], $baseField) === true &&
            Lot::getItem(['validations', 'required'], $field) !== true
        ) {
            $field['validations']['required'] = false;
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
    
    protected function parseSortBy(&$sortBy) {
        $sortBy = null;
    }
}
