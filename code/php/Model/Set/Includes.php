<?php
namespace Data2Html\Model\Set;

use Data2Html\Model;

class Includes extends \Data2Html\Model\Set
{
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
        parent::__construct(new Model(''), $setName, $defs);
    }
    
    protected function beforeParseItem(&$key, &$field)
    {
        if ($this->alternativeItem && array_key_exists($this->alternativeItem, $field)) {
            $field = $field[$this->alternativeItem];
        }
        return true;
    }
}
