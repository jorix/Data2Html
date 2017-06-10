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
      
        if(array_key_exists('columns', $defs)) {
            $this->table = new Data2Html_Model_Set_Table($model,
                $gridName,
                $defs,
                $baseFields->getItems()
            );
        } else {
            $this->table = new Data2Html_Model_Set_Table($model,
                $gridName,
                $baseFields->getItems(),
                $baseFields->getItems()
            );
        }
        if (array_key_exists('filter', $defs)) {
            $this->filter = new Data2Html_Model_Set_Filter($model,
                $gridName,
                $defs['filter'],
                $baseFields->getItems()
            );
        }
    }

    public function dump()
    {
        if (!$this->debug) {
            echo "Debug mode is not activated, activate it to make a dump!";
            return;
        }
        $this->table->dump();
        if ($this->filter) {
            $this->filter->dump();
        }
        $this->baseFields->dump();
    }

    
    public function getName()
    {
        return $this->gridName;
    }
    
}
