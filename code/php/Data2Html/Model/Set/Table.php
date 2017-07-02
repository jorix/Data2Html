<?php
class Data2Html_Model_Set_Table extends Data2Html_Model_Set_Base 
{
    protected $attributeNames = array(
        'sort' => 'attribute',
        'layouts' => 'attribute',
        'columns' => 'items',
        'filter' => false
    );
    
    function __construct($model, $setName, $defs, $baseItems = null) {
        parent::__construct($model, $setName, $defs, $baseItems);
        if (!$this->setItems) {
            $this->setItems = array();
            $this->parseItems($baseItems);
        }
    }
}
