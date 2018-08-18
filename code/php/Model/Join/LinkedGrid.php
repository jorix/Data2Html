<?php
namespace Data2Html\Model\Join;

use Data2Html\Model\Set\Filter;
use Data2Html\Model\Set\Grid;
use Data2Html\Model\Join\Linker;
use Data2Html\Model\Join\LinkedSet;

class LinkedGrid
{
    use \Data2Html\Debug;

    protected $columns = null;
    protected $filter = null;
    
    public function __construct(Linker $linker, Grid $grid, $filter)
    {
        // Set fields
        $this->columns = new LinkedSet($linker, $grid);
        if ($filter) {
            $this->filter = new LinkedSet($linker, $filter, 'filter');
        }
    }
    
    public function __debugInfo()
    {
        $response = ['columns' => $this->columns->__debugInfo()];
        if ($this->filter) {
            $response['filter'] = $this->filter->__debugInfo();
        }
        $response['linkUp'] = $this->columns->getLink()->__debugInfo();
        return $response;
    }
    
    public function getId()
    {
        return $this->columns->getId();
    }

    public function getAttributeUp($attributeKeys, $default = null)
    {
        return $this->columns->getAttributeUp($attributeKeys, $default);
    }
    
    public function getAttribute($attributeKeys, $default = null)
    {
        return $this->columns->getAttribute($attributeKeys, $default);
    }

    public function getColumnsSet()
    {
        return $this->columns;
    }

    public function getFilter()
    {
        return $this->filter;
    }
    
}
