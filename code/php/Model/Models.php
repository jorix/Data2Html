<?php
namespace Data2Html\Model;

use Data2Html\DebugException;
use Data2Html\Config;
use Data2Html\Data\InfoFile;
use Data2Html\Data\Lot;

class Models
{
    //protected $db_params;
    protected static $modelObjects = [];
   
    /**
     * Load and create one model
     * $modelName string||array
     */
    protected static function &get($modelName)
    {
        if (!$modelName) {
            throw new DebugException("'modelName' argument is required.");
        }
        if (!array_key_exists($modelName, self::$modelObjects)) {
            $definitions = InfoFile::readPhp(
                Config::getForlder('modelFolder') . DIRECTORY_SEPARATOR . $modelName . '.php'
            );
            self::$modelObjects[$modelName] = [
                '_defs' => $definitions,
                '_base' => new Set\Base($modelName, $definitions),
                '_columns' => [],
                'lkGrids' => [],
                'lkBlocks' => []
            ];
        }
        return self::$modelObjects[$modelName];
    }
    
    public static function linkGrid($modelName, $gridName, $doLink = true)
    {
        $model = self::get($modelName);
        $modelGrids =& $model['lkGrids'];
        if (!array_key_exists($gridName, $modelGrids)) {
            $gridDef = Lot::getItem(['_defs', 'grids', $gridName], $model);
            $columns = new Set\Columns($gridName, $gridDef, $model['_base']);
            if (!$doLink) { // Only to test or debug use
                return $columns;
            }
            $linkedGrid = new Link\LinkedGrid($columns);
            if (isset($gridDef['filter'])) {
                $linkedGrid->addFilter(
                    new Set\Filter($gridName, $gridDef['filter'], $model['_base'])
                );
            }
           $modelGrids[$gridName] = $linkedGrid;
        }    
        return $modelGrids[$gridName];
    }
    
    public static function parseUrlColumns($linkedUrl)
    {
        $pNames = self::parseUrl($linkedUrl);
        if (!array_key_exists('model', $pNames) || !array_key_exists('grid', $pNames)) {
            throw new DebugException("Link \"{$linkedUrl}\" without a grid name.", [
                $linkedUrl
            ]);
        }
        $gridName = $pNames['grid'];
        $model = self::get($pNames['model']);
        $modelColumns =& $model['_columns'];
        if (!array_key_exists($gridName, $modelColumns)) {
            $modelColumns[$gridName] = new Set\Columns(
                $gridName,
                Lot::getItem(['_defs', 'grids', $gridName], $model),
                $model['_base']
            );
        }    
        return $modelColumns[$gridName];
    }
       
    public static function linkBlock($modelName, $blockName, $doLink = true)
    {
        $model = self::get($modelName);
        $modelBlocks =& $model['lkBlocks'];
        if (!array_key_exists($blockName, $modelBlocks)) {
            $block = new Set\Block(
                $blockName, 
                Lot::getItem(['_defs', 'blocks', $blockName], $model),
                $model['_base']
            );
            if (!$doLink) { // Only to test or debug use
                return $block;
            }
           $modelBlocks[$blockName] = new Link\LinkedSet($block);
        }    
        return $modelBlocks[$blockName];
    }
    
    
    public static function parseUrl($linkText) 
    {
        try {
            parse_str($linkText, $reqArr);
            return self::parseRequest($reqArr);
        } catch(\Exception $e) {
            throw new \Exception("Link \"{$linkText}\" can't be parsed.");
        }
    }

    public static function parseRequest($request) 
    {
        if (array_key_exists('block', $request)) {
            $elements = explode(':', $request['block']);
            return [
                'model' => $elements[0],
                'block' => count($elements) > 1 ? $elements[1] : 'main'
            ];
        } elseif (array_key_exists('grid', $request)) {
            $elements = explode(':', $request['grid']);
            return [
                'model' => $elements[0],
                'grid' => count($elements) > 1 ? $elements[1] : 'main'
            ];
        } else {
            throw new DebugException(
                'The URL parameter `?block=`  or `?grid=` is not set.',
                $request
            );
        }
    }
}
