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

    public function __construct($setName, $defs, Base $baseSet) {
        if (!$defs && $setName === 'main') {
            $defs = ['items' => $baseSet->getItems()];
        }
        parent::__construct($setName, $defs, $baseSet);
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
