<?php
namespace Data2Html\Model\Set;

class Block extends \Data2Html\Model\Set
{
    protected $attributeNames = array(
        'template' => 'string',
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
    
    public function __construct($setName, $defs, Base $baseSet)
    {
        if (!$defs && $setName === 'main') {
            $defs = ['items' => $baseSet->getItems()];
        }
        parent::__construct($setName, $defs, $baseSet);
    }

}
