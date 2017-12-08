<?php
class Data2Html_Model_Set_Includes extends Data2Html_Model_Set 
{
    protected $attributeNames = array(
        'fields' => 'items'
    );    
    protected $keywords = array(
        'head-item' => 'array'        
    );
    
    private $alternativeItem = null;

    public function __construct(
        $model,
        $setName,
        $defs,
        $alternativeItem = null
    ) {
        $this->alternativeItem = $alternativeItem;
        parent::__construct($model, $setName, $defs);
    }
    
    protected function beforeParseItem(&$key, &$field)
    {
        if ($this->alternativeItem && array_key_exists($this->alternativeItem, $field)) {
            $field = $field[$this->alternativeItem];
        }
        return true;
    }
}
