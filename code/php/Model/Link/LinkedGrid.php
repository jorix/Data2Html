<?php
namespace Data2Html\Model\Link;

use Data2Html\Model\Set\Filter;
use Data2Html\Model\Set\Columns;
use Data2Html\Model\Link\Linker;
use Data2Html\Model\Link\LinkedSet;

class LinkedGrid
{
    use \Data2Html\Debug;

    protected $linker = null;
    protected $columns = null;
    protected $filter = null;
    
    public function __construct(Columns $columns)
    {
        $this->linker = new Linker($columns);
        $this->columns = new LinkedSet($columns, $this->linker);
    }
    
    public function __debugInfo()
    {
        $response = ['columns' => $this->columns->__debugInfo()];
        if ($this->filter) {
            $response['filter'] = $this->filter->__debugInfo();
        }
        return $response;
    }
    
    public function addFilter(Filter $filter)
    {
        $this->filter = new LinkedSet($filter, $this->linker);
    }
    
    public function getModelName()
    {
        return $this->columns->getModelName();
    }  
    
    public function getId()
    {
        return $this->columns->getId();
    }

    public function getAttributeUp($attributeKeys, $default = null, $verifyName = true)
    {
        return $this->columns->getAttributeUp($attributeKeys, $default, $verifyName);
    }
    
    public function getAttribute($attributeKeys, $default = null, $verifyName = true)
    {
        return $this->columns->getAttribute($attributeKeys, $default, $verifyName);
    }

    public function getLinkedColumns()
    {
        return $this->columns;
    }

    public function getSort()
    {
        return $this->columns->getSort();
    }

    public function getFilter()
    {
        return $this->filter;
    }
    
}
