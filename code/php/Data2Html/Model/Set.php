<?php
/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..Dx: Definitions as a `Data2Html_Collection`.
 *      * ..Ds: Definitions as a array.
 */
 
class Data2Html_Model_Set
{
    protected $idCount = 0;
    protected $setName = '';
    protected $culprit = '';
    protected $setItems = array();
    
    public function __construct($setName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->setName = $setName;
        $this->culprit = "Set \"{$this->setName}\"";
    }
    
    public function addItem($pKey, $pItem)
    {
        if (is_int($pKey) || array_key_exists($pKey, $this->setItems)) {
            $pKey = $this->createId();
        }
        $this->setItems[$pKey] = $pItem;
    }
    public function getItems()
    {
        return $this->setItems;
    }
    protected function createId() {
        $this->idCount++;
        return 'd2h_' . $this->setName . '_' . $this->idCount;
    }
}
