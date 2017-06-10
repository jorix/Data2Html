<?php
class Data2Html_Model_Set_Filter extends Data2Html_Model_Set_Form 
{
    protected $keywords = array(
        'words' => array('check'=> 'string')
    );
    protected function beforeParseItem(&$key, &$field)
    {
        if (is_array($field)) {
            $field = $field;
        } elseif (is_string($field)) {
            if (is_string($key)) {
                $field = array('name' => $key, 'check' => $field);
            } else {
                throw new Exception(
                    "{$this->culprit}: String \"{$field}\" needs a value as string or array."
                ); 
            }
        }
        if (is_int($key) && array_key_exists('check', $field)) {
            $key = $name.'_'.$field['check'];
        }
        return true;
    }
    protected function beforeAddItem(&$key, &$field)
    {
        if (
            !array_key_exists('db', $field) &&
            array_key_exists('check', $field)
        ) {
            throw new Exception(
                "{$this->culprit}: Key `{$key}=>[...]` with check=\"{$field['check']}\" requires a `db` attribute."
            );
        }
        return true;
    }
}
