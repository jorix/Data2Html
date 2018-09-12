<?php
namespace Data2Html\Model\Set;

class Base extends \Data2Html\Model\Set
{
    protected $attributeNames = array(
        'grids' => false,
        'blocks' => false,
        'table' => 'string',
        'sort' => 'string',
        'beforeInsert' => 'function',
        'afterInsert' => 'function',
        'beforeUpdate' => 'function',
        'afterUpdate' => 'function',
        'beforeDelete' => 'function',
        'afterDelete' => 'function'
    );
    protected $keywords = array(
        'sortBy' => null
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
