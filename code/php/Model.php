<?php
/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..vDx: Definitions as a `Data2Html_Collection`.
 *      * ..Ds: Definitions as a array.
 */
 
class Data2Html_Model
{
    protected $debug = false;
    protected $modelName = '';
    protected $culprit = '';
    
    // original definitions
    private $definitions = null;
    private static $idModelCount = 0;
    
    // Parsed object definitions
    private $id = '';
    private $baseSet = null;
    private $grids = [];
    private $unlinkedGrids = [];
    private $elements = [];
    
    /**
     * Class constructor, initializes basic properties.
     *
     * @param jqGridLoader $loader
     */
    public function __construct($modelName)
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }

        $this->debug = Data2Html_Config::debug();
        $this->modelName = $modelName;
        $this->culprit = "Model \"{$this->modelName}\"";
        
        $this->id = 'd2h_' . ++self::$idModelCount;
        $this->definitions = Data2Html_Utils::readFilePhp(
            Data2Html_Config::getForlder('modelFolder') . DIRECTORY_SEPARATOR . $modelName . '.php'
        );
        $this->baseSet = new Data2Html_Model_Set_Base(
            $this, null, $this->definitions
        );
    }
  
    public function getId()
    {
        return $this->id;
    }
    
    public function getModelName()
    {
        return $this->modelName;
    }
    
    public function getBase()
    {
        return $this->baseSet;
    }
    
    public function getGridColumns($gridName)
    {
        if (!array_key_exists($gridName, $this->unlinkedGrids)) {
            $this->unlinkedGrids[$gridName] = new Data2Html_Model_Set_Grid(
                $this,
                $gridName,
                $this->getSetDefs($gridName, 'grids'),
                $this->getBase()
            );
        }    
        return $this->unlinkedGrids[$gridName];
    }
    
    public function getLinkedGrid($gridName = '')
    {
        if (!$gridName) {
            $gridName = 'main';
        }
        if (!array_key_exists($gridName, $this->grids)) {
            $this->grids[$gridName] = new Data2Html_Join_LinkedGrid(
                $this,
                $gridName,
                $this->getSetDefs($gridName, 'grids'),
                ['linked' => true]
            );
        }    
        return $this->grids[$gridName];
    }
    
    public function getLinkedElement($elementName = '')
    {
        if (!$elementName) {
            $elementName = 'main';
        }
        if (!array_key_exists($elementName, $this->elements)) {
            $this->elements[$elementName] = new Data2Html_Join_LinkedSet(
                new Data2Html_Model_Set_Element(
                    $this,
                    $elementName, 
                    $this->getSetDefs($elementName, 'elements'),
                    $this->baseSet
                )
            );
        }    
        return $this->elements[$elementName];
    }
    
    protected function getSetDefs($name, $setName)
    {
        if (array_key_exists($setName, $this->definitions)) {
            $df = $this->definitions[$setName];
        } else {
            $df = array();
        }
        if (array_key_exists($name, $df)) {
            $objDf = $df[$name];
        } else {
            if ($name === 'main') {
                $objDf = array();
            } else {
                throw new Data2Html_Exception(
                    "{$this->culprit}: \"{$name}\" not exist on \"{$setName}\" definitions.",
                    $this->definitions
                );
            }
        }
        if ($objDf === null) {
            throw new Data2Html_Exception(
                "{$this->culprit}: \"{$name}\" not be used as \"{$setName}\" definition, is null.",
                $this->definitions
            );
        }
        return $objDf;
    }

    // ========================
    // Events
    // ========================
    /**
     * Insert events
     */
    public function beforeInsert($db, &$values)
    {
        return true;
    }
    public function afterInsert($db, &$values, &$keys)
    {
    }

    /**
     * Update events
     */
    public function beforeUpdate($db, &$values, &$keys)
    {
        return true;
    }
    public function afterUpdate($db, &$values, &$keys)
    {
    }

    /**
     * Delete events
     */
    public function beforeDelete($db, &$values, &$keys)
    {
        return true;
    }
    public function afterDelete($db, &$values, &$keys)
    {
    }
}
