<?php
namespace Data2Html\Model\Set;

class Base extends \Data2Html\Model\Set
{
    protected $modelName;
    
    protected $attributeNames = [
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
    ];
    protected $keywords = [
        'sortBy' => null,
        'db-items' => null
    ];

    public function __construct($modelName, $defs) {
        $this->modelName = $modelName;
        parent::__construct('', $defs);
    }
        
    protected function beforeAddItem(&$key, &$field)
    {
        // set default for sortBy 
        if (!array_key_exists('sortBy', $field)) {
            if (isset($field['db-items'])) {
                $field['db'] = null;
                $field['sortBy'] = null;
            }
            if (isset($field['db'])) {
                $field['sortBy'] = $key;
            }
        }
        return true;
    }
}
