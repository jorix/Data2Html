<?php
class Data2Html_Model_Set_Table extends Data2Html_Model_Set
{
    protected $attributeNames = array(
        'sort' => 'attribute',
        'layout' => 'attribute',
        'layouts' => 'attribute',
        'filter' => false
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
    
    public function getSort() {
        return $this->getAttribute('sort');
    }
}
