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
    
    public function __construct(
        $model,
        $setName,
        $defs,
        $baseSet = null,
        $options = null
    ) {
        parent::__construct($model, $setName, $defs, $baseSet);
        
        if (!$this->setItems) {
        // If no items then set items as baseIntems
            $this->setItems = array();
            $this->parseItems($baseSet->getItems());
        }
    }

}
