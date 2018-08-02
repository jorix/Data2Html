<?php
namespace Data2Html\Model\Set;

class Base extends \Data2Html\Model\Set
{
    protected $attributeNames = array(
        'grids' => false,
        'blocks' => false,
        'table' => 'attribute',
        'sort' => 'attribute',
        'beforeInsert' => 'attribute',
        'afterInsert' => 'attribute',
        'beforeUpdate' => 'attribute',
        'afterUpdate' => 'attribute',
        'beforeDelete' => 'attribute',
        'afterDelete' => 'attribute'
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
