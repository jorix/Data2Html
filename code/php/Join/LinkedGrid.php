<?php
namespace Data2Html\Join;

use Data2Html\Model\Set\Filter as SetFilter;
use Data2Html\Model\Set\Grid as SetGrid;

class LinkedGrid
{
    use \Data2Html\Debug;
    
    protected $gridName = '';
    
    protected $model = null;
    protected $columns = null;
    protected $filter = null;
    protected $link = null;
    
    public function __construct($model, $gridName, $defs)
    {
        $this->model = $model;
        $this->gridName = $gridName;
      
        // Set fields
        $this->columns = new LinkedSet(
            new SetGrid(
                $model,
                $gridName,
                $defs,
                $model->getBase()
            )
        );
        
        if (array_key_exists('filter', $defs)) {
            $this->filter = new LinkedSet(
                new SetFilter(
                    $model,
                    $gridName,
                    $defs['filter'],
                    $model->getBase()
                ),
                'filter',
                $this->columns->getLink()
            );
        }
    }
    
    public function __debugInfo()
    {
        $response = ['columns' => $this->columns->__debugInfo()];
        if ($this->filter) {
            $response['filter'] = $this->filter->__debugInfo();
        }
        // $response['base'] = $this->columns->getBase()->__debugInfo();
        return $response;
    }
    
    public function getId()
    {
        return $this->columns->getId();
    }

    public function getColumnsSet()
    {
        return $this->columns;
    }

    public function getAttributeUp($attrName, $default = null)
    {
        return $this->columns->getAttributeUp($attrName, $default);
    }
    
    public function getAttribute($attrName, $default = null)
    {
        return $this->columns->getAttribute($attrName, $default);
    }

    public function getFilter()
    {
        return $this->filter;
    }
    
}
