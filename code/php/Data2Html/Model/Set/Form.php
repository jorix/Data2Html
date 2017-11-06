<?php
class Data2Html_Model_Set_Form extends Data2Html_Model_Set 
{
    protected $attributeNames = array(
        'sort' => false,
        'layout' => 'attribute',
        'fields' => 'items'
    );
    
    protected $keywords = array(
        'layout' => 'string',
        'layouts' => 'array',
        'icon' => 'string',
        'action' => 'string',
        'input' => 'string'
        
    );
        
    protected function parseSortBy($sortBy, $baseItems) {
        return null;
    }
}
