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
use Data2Html\Model\Set\Base;
use Data2Html\Model\Set\Block;
use Data2Html\Model\Set\Columns;
use Data2Html\Model\Set\Filter;
use Data2Html\Model\Link\Linker;
use Data2Html\Model\Link\LinkedSet;
use Data2Html\Model\Link\LinkedGrid;

class Model
{
    use \Data2Html\Debug;
    
    // original definitions
    private $definitions = null;
    private static $idModelCount = 0;
    
    // Parsed object definitions
    private $id = '';
    private $baseSet = null;
    private $grids = [];
    private $unlinkedColumns = [];
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
            $this->definitions = InfoFile::readPhp(
                Config::getForlder('modelFolder') . DIRECTORY_SEPARATOR . $modelName . '.php'
            );
        } else {
            $this->definitions = [];
        }
        $this->baseSet = new Base(
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
    
    public function getBase()
    {
        return $this->baseSet;
    }
    
    public function getColumns($gridName)
    {
        if (!array_key_exists($gridName, $this->unlinkedColumns)) {
            $this->unlinkedColumns[$gridName] = new Columns(
                $this,
                $gridName,
                $this->getSetDefs('grids', $gridName),
                $this->getBase()
            );
        }    
        return $this->unlinkedColumns[$gridName];
    }
    
    public function getLinkedGrid($gridName, $doLink = true)
    {
        if (!$gridName) {
            $gridName = 'main';
        }
        $gridDef = $this->getSetDefs('grids', $gridName);
        if (!array_key_exists($gridName, $this->grids)) {
            $columns = new Columns($this, $gridName, $gridDef, $this->baseSet);
            if (!$doLink) { // Only to test or debug use
                return $columns;
            }
            $linkedGrid = new LinkedGrid($columns);
            if (isset($gridDef['filter'])) {
                $linkedGrid->addFilter(
                    new Filter($this, $gridName, $gridDef['filter'], $this->baseSet)
                );
            }
            $this->grids[$gridName] = $linkedGrid;
        }    
        return $this->grids[$gridName];
    }
    
    public function getLinkedBlock($blockName = '', $doLink = true)
    {
        if (!$blockName) {
            $blockName = 'main';
        }
        if (!array_key_exists($blockName, $this->blocks)) {
            $block = new Block(
                $this,
                $blockName, 
                $this->getSetDefs('blocks', $blockName),
                $this->baseSet
            );
            if (!$doLink) { // Only to test or debug use
                return $block;
            }
            $this->blocks[$blockName] = new LinkedSet($block);
        }    
        return $this->blocks[$blockName];
    }
    
    protected function getSetDefs($setName, $itemName)
    {
        if (array_key_exists($setName, $this->definitions)) {
            $df = $this->definitions[$setName];
        } else {
            $df = [];
        }
        if (array_key_exists($itemName, $df)) {
            $objDf = $df[$itemName];
        } else {
            if ($itemName === 'main') {
                $objDf = [];
            } else {
                throw new DebugException(
                    "\"{$itemName}\" not exist on \"{$setName}\" definitions.",
                    $this->definitions
                );
            }
        }
        if ($objDf === null) {
            throw new DebugException(
                "\"{$itemName}\" not be used as \"{$setName}\" definition, is null.",
                $this->definitions
            );
        }
        return $objDf;
    }
}
