<?php
namespace Data2Html\Model\Set;

class Columns extends \Data2Html\Model\Set
{
    protected $attributeNames = array(
        'block-name' => 'string',
        'filter' => false,
        'sort' => 'string',
        'template' => 'string',
        'summary' => 'boolean',
        'keys' => '[string]',
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
        $sort = $this->getAttribute('sort');
        if (!$sort) {
            $sortUp = $this->getAttributeUp('sort');
            if ($sortUp && array_key_exists($sortUp, $this->setItems)) {
                $sort = $sortUp;
            }
        }
        return $sort;
    }
}
