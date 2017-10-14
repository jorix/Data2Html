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
        $this->columns = new Data2Html_Model_Set_Table(
            $model,
            $gridName,
            $defs,
            $model->getBase()
        );
        if (array_key_exists('filter', $defs)) {
            $this->filter = new Data2Html_Model_Set_Filter(
                $model,
                $gridName,
                $defs['filter'],
                $model->getBase()
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
            $this->baseSet->dump();
        } else {
            Data2Html_Utils::dump($this->culprit, $subject);
        }
    }
    
    public function getColumnsSet()
    {
        return $this->columns;
    }
    public function getFilterSet()
    {
        return $this->filter;
    }
    
    public function getTableName()
    {
        return $this->model->getTableName();
    }
    
    public function getGridName()
    {
        return $this->gridName;
    }

    public function getAttribute($attrName, $default = null)
    {
        return $this->columns->getAttribute($attrName, $default);
    }

    public function getFilter()
    {
        if (!$this->link) {
            throw new Exception(
                "{$this->culprit} getFilter(): Before get the linked filter, must create by createLink()."
            );
        }
        return $this->filter;
    }
    
    public function createLink()
    {
        if ($this->link) {
            return $this->link;
        }
        $this->link = $this->columns->createLink();
        if ($this->filter) {
            $this->filter->addToLink($this->link);
        }
        return $this->link;
    }
}
