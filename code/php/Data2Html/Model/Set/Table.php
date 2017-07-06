<?php
class Data2Html_Model_Set_Table extends Data2Html_Model_Set_Base 
{
    protected $attributeNames = array(
        'sort' => 'attribute',
        'layouts' => 'attribute',
        'columns' => 'items',
        'filter' => false
    );
    
    public function __construct($model, $setName, $defs, $baseItems = null) {
        parent::__construct($model, $setName, $defs, $baseItems);
        
        if (!$this->setItems) {
        // If no items then set items as baseIntems
            $this->setItems = array();
            $this->parseItems($baseItems);
        }
    }
    
    public function getSort() {
        return Data2Html_Value::getItem($this->attributes, 'sort');
    }
}
