<?php
class Data2Html_Model_Set_Form extends Data2Html_Model_Set 
{
    protected $attributeNames = array(
        'sort' => false,
        'layout' => 'attribute',
        'fields' => 'items'
    );
        
    protected function parseSortBy($sortBy, $baseItems) {
        return null;
    }
}
