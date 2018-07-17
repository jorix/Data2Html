<?php
namespace Data2Html\Model\Set;

class Grid extends \Data2Html\Model\Set
{
    protected $attributeNames = array(
        'block-name' => 'attribute',
        'filter' => false,
        'sort' => 'attribute',
        'template' => 'attribute',
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
        return $this->getAttributeUp('sort');
    }
}
