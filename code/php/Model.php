<?php
/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..vDx: Definitions as a `Lot` instance.
 *      * ..Ds: Definitions as a array.
 */
namespace Data2Html;

use Data2Html\Config;
use Data2Html\DebugException;
use Data2Html\Data\InfoFile;
use Data2Html\Model\Set\Base as SetBase;
use Data2Html\Model\Set\Block as SetBlock;
use Data2Html\Model\Set\Grid as SetGrid;
use Data2Html\Join\LinkedSet;
use Data2Html\Join\LinkedGrid;
use Data2Html\Join\LinkedBlock;

class Model
{
    use \Data2Html\Debug;
    
    protected $modelName = '';
    
    // original definitions
    private $definitions = null;
    private static $idModelCount = 0;
    
    // Parsed object definitions
    private $id = '';
    private $baseSet = null;
    private $grids = [];
    private $unlinkedGrids = [];
    private $blocks = [];
    
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
        
        $this->id = 'd2h_' . ++self::$idModelCount;
        
        if ($modelName) {
            $this->modelName = $modelName;
            $this->definitions = InfoFile::readPhp(
                Config::getForlder('modelFolder') . DIRECTORY_SEPARATOR . $modelName . '.php'
            );
        } else {
            $this->modelName = '[empty]';
            $this->definitions = [];
        }
        $this->baseSet = new SetBase(
            $this, null, $this->definitions
        );
    }
  
    public function __debugInfo()
    {
        return [
            'definitions' => $this->definitions,
            'baseSet' => $this->baseSet->__debugInfo()
        ];
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
            $this->unlinkedGrids[$gridName] = new SetGrid(
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
            $this->grids[$gridName] = new LinkedGrid(
                $this,
                $gridName,
                $this->getSetDefs($gridName, 'grids'),
                ['linked' => true]
            );
        }    
        return $this->grids[$gridName];
    }
    
    public function getLinkedBlock($elementName = '')
    {
        if (!$elementName) {
            $elementName = 'main';
        }
        if (!array_key_exists($elementName, $this->blocks)) {
            $this->blocks[$elementName] = new LinkedSet(
                new SetBlock(
                    $this,
                    $elementName, 
                    $this->getSetDefs($elementName, 'blocks'),
                    $this->baseSet
                )
            );
        }    
        return $this->blocks[$elementName];
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
                throw new DebugException(
                    "\"{$name}\" not exist on \"{$setName}\" definitions.",
                    $this->definitions
                );
            }
        }
        if ($objDf === null) {
            throw new DebugException(
                "\"{$name}\" not be used as \"{$setName}\" definition, is null.",
                $this->definitions
            );
        }
        return $objDf;
    }
}
