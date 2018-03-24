<?php
class Data2Html_Model_Set_Element extends Data2Html_Model_Set
{
    protected $attributeNames = array(
        'template' => 'attribute',
        'beforeInsert' => 'attribute',
        'afterInsert' => 'attribute',
        'beforeUpdate' => 'attribute',
        'afterUpdate' => 'attribute'
    );

    protected $keywords = array(
        'sortBy' => null
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
