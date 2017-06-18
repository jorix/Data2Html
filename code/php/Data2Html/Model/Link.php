<?php
class Data2Html_Model_Link
{
    protected $linkName = '';
    protected $culprit = '';
    protected $debug = false;
    
    protected $model = null;
    
    protected $links = array();
    
    public function __construct($model, $linkName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit =
            "d2h_Grid for \"{$model->getModelName()}->{$linkName}\"";
        
        array_push $links
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
