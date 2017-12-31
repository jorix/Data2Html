<?php
class Data2Html_Model_Set_FormEdit extends Data2Html_Model_Set
{
    protected $attributeNames = array(
        'layout' => 'attribute',
        'beforeInsert' => 'attribute',
        'afterInsert' => 'attribute',
        'beforeUpdate' => 'attribute',
        'afterUpdate' => 'attribute'
    );
    
    public function __construct(
        $model,
        $setName,
        $defs,
        $baseSet = null
    ) {
        parent::__construct($model, $setName, $defs, $baseSet);
        
        if (!$this->setItems) {
        // If no items then set items as baseIntems
            $this->setItems = array();
            $this->parseItems($baseSet->getItems());
        }
    }

}
