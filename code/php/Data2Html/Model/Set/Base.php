<?php
class Data2Html_Model_Set_Base extends Data2Html_Model_Set 
{
    protected $attributeNames = array(
        'table' => 'attribute',
        'grids' => false,
        'forms' => false
    );
    protected $keywords = array(
        'sortBy' => null,
        'teplateItems' => 'array'
    );

    protected function beforeAddItem(&$key, &$field)
    {
        // set default for sortBy 
        if (!array_key_exists('sortBy', $field)) {
            if (isset($field['db'])) {
                $field['sortBy'] = $key;
            }
        }
        return true;
    }
}
