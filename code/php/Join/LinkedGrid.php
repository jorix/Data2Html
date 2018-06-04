<?php
class Data2Html_Join_LinkedGrid
{
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
        $this->columns = new Data2Html_Join_LinkedSet(
            new Data2Html_Model_Set_Grid(
                $model,
                $gridName,
                $defs,
                $model->getBase()
            )
        );
        
        if (array_key_exists('filter', $defs)) {
            $this->filter = new Data2Html_Join_LinkedSet(
                new Data2Html_Model_Set_Filter(
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
