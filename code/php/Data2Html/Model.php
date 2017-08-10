<?php
/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..vDx: Definitions as a `Data2Html_Collection`.
 *      * ..Ds: Definitions as a array.
 */
 
abstract class Data2Html_Model
{
    protected $debug = false;
    protected $modelName = '';
    protected $culprit = '';
    
    // original definitions
    private $definitions = null;
    
    // Parsed object definitions
    private $base = null;
    private $grids = array();
    private $forms = array();
    
    /**
     * Class constructor, initializes basic properties.
     *
     * @param jqGridLoader $loader
     */
    public function __construct()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }

        $this->debug = Data2Html_Config::debug();
        $this->modelName = get_class($this);
        $this->culprit = "Model \"{$this->modelName}\"";
        
        $this->definitions = $this->definitions();
        $this->base = new Data2Html_Model_Set_Base(
            $this, null, $this->definitions
        );
    }
    
    abstract protected function definitions();
 
    public function getModelName()
    {
        return $this->modelName;
    }
    
    public function getBase()
    {
        return $this->base;
    }
    
    public function getGrid($gridName = '')
    {
        if (!$gridName) {
            $gridName = 'default';
        }
        if (!array_key_exists($gridName, $this->grids)) {
            if (!array_key_exists('grids', $this->definitions)) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: The \"grids\" key not exist on definitions.",
                    $this->definitions
                );
            }
            $gridsDf = $this->definitions['grids'];
            if (!array_key_exists($gridName, $gridsDf)) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: The grid \"{$gridName}\" not exist on grids definitions.",
                    $this->definitions
                );
            }
            $this->grids[$gridName] = new Data2Html_Model_Grid(
                    $this, $gridName, $gridsDf[$gridName], $this->base
            );
        }    
        return $this->grids[$gridName];
    }
    
    public function getTableName()
    {
        return $this->base->getAttribute('table');
    }
    
    public function getTitle()
    {
        return $this->base->getAttribute('title', $this->getTableName());
    }
    
    public function getForm($formName = '')
    {
        if (!$formName) {
            $formName = 'default';
        }
        if (!array_key_exists($formName, $this->forms)) {
            if (!array_key_exists('forms', $this->definitions)) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: The \"forms\" key not exist on definitions.",
                    $this->definitions
                );
            }
            $gridsDf = $this->definitions['forms'];
            if (!array_key_exists($formName, $gridsDf)) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: The form \"{$formName}\" not exist on forms definitions.",
                    $this->definitions
                );
            }
            $this->forms[$formName] = new Data2Html_Model_Set_FormEdit(
                    $this, $formName, $gridsDf[$formName], $this->base
            );
        }    
        return $this->forms[$formName];
    }
    
    // ========================
    // Events
    // ========================
    /**
     * Insert events
     */
    protected function beforeInsert($values)
    {
        return true;
    }
    protected function afterInsert($values, $keyArray)
    {
    }

    /**
     * Update events
     */
    protected function beforeUpdate($values, $keyArray)
    {
        return true;
    }
    protected function afterUpdate($values, $keyArray)
    {
    }

    /**
     * Delete events
     */
    protected function beforeDelete($keyArray)
    {
        return true;
    }
    protected function afterDelete($keyArray)
    {
    }
}
