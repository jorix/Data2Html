<?php
class Data2Html_Model_Grid
{
    protected $gridName = '';
    protected $culprit = '';
    protected $debug = false;
    
    protected $model = null;
    protected $baseFields = null;
    protected $table = null;
    protected $filter = null;
    
    public function __construct($model, $gridName, $defs, $baseFields)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit =
            "d2h_Grid for \"{$model->getModelName()}->{$gridName}\"";
        
        $this->model = $model;
        $this->gridName = $gridName;
         
        $this->baseFields = $baseFields;
      
        // Set fields
        $this->table = new Data2Html_Model_Set_Table(
            $model,
            $gridName,
            $defs,
            $baseFields->getItems()
        );
        if (array_key_exists('filter', $defs)) {
            $this->filter = new Data2Html_Model_Set_Filter(
                $model,
                $gridName,
                $defs['filter'],
                $baseFields->getItems()
            );
        }
    }
    public function getModel()
    {
        return $this->model;
    }
    public function getLink()
    {
        $link = new Data2Html_Model_Link($this->culprit, $this->table);
        $link->add('table', $this->table->getItems());
        if ($this->filter) {
            $link->add('filter', $this->filter->getItems());
        }
        return $link;
    }
    public function dump()
    {
        $this->table->dump();
        if ($this->filter) {
            $this->filter->dump();
        }
        $this->baseFields->dump();
    }

    public function getKeys()
    {
        return $this->table->getKeys();
    }
    public function getTableSet()
    {
        return $this->table;
    }
    public function getTableName()
    {
        return $this->model->getTableName();
    }
    
    public function getGridName()
    {
        return $this->gridName;
    }
    
}
