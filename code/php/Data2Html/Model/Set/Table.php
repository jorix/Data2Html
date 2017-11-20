<?php
class Data2Html_Model_Set_Table extends Data2Html_Model_Set_Base 
{
    protected $attributeNames = array(
        'sort' => 'attribute',
        'layout' => 'attribute',
        'layouts' => 'attribute',
        'columns' => 'items',
        'filter' => false
    );
    
    public function __construct(
        $model,
        $setName,
        $defs,
        $baseSet = null,
        $subItemsKey = 'items'
    ) {
        parent::__construct($model, $setName, $defs, $baseSet, $subItemsKey);
        
        if (!$this->setItems) {
        // If no items then set items as baseIntems
            $this->setItems = array();
            $this->parseItems($baseSet->getItems());
        }
    }
    
    public function getSort() {
        return $this->getAttribute('sort');
    }
}
