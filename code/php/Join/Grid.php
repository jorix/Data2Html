<?php
class Data2Html_Model_Grid
{
    protected $gridName = '';
    protected $culprit = '';
    protected $debug = false;
    
    protected $model = null;
    protected $columns = null;
    protected $filter = null;
    protected $link = null;
    
    public function __construct($model, $gridName, $defs)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit =
            "d2h_Grid for \"{$model->getModelName()}->{$gridName}\"";
        
        $this->model = $model;
        $this->gridName = $gridName;
      
        // Set fields
        $columns = ;
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
    
    public function dump($subject = null)
    {
        if (!$subject) {
            $this->columns->dump();
            if ($this->filter) {
                $this->filter->dump();
            }
            $this->columns->getBase()->dump();
        } else {
            Data2Html_Utils::dump($this->culprit, $subject);
        }
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
