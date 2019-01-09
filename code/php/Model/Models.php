<?php
namespace Data2Html\Model;

use Data2Html\DebugException;
use Data2Html\Model;

class Models
{
    //protected $db_params;
    protected static $modelObjects = [];
   
    /**
     * Load and create one model
     * $modelName string||array
     */
    public static function get($modelName)
    {
        if (!$modelName) {
            throw new DebugException("Don't use `Models::get()` without modelName.", [
                'modelName' => $modelName
            ]);
        }
        if (!array_key_exists($modelName, self::$modelObjects)) {
            self::$modelObjects[$modelName] = new Model($modelName);
        }
        return self::$modelObjects[$modelName];
    }
    
    public static function linkGrid($modelName, $gridName, $doLink = true)
    {
        $model = self::get($modelName);
        return $model->getLinkedGrid($gridName, $doLink);
    }
    
    public static function linkBlock($modelName, $blockName, $doLink = true)
    {
        $model = self::get($modelName);
        return $model->getLinkedBlock($blockName, $doLink);
    }
    
    public static function parseUrlColumns($linkedUrl)
    {
        $pNames = self::parseUrl($linkedUrl);
        if (!array_key_exists('model', $pNames) || !array_key_exists('grid', $pNames)) {
            throw new DebugException("Link \"{$linkedUrl}\" without a grid name.", [
                $linkedUrl
            ]);
        }
        return self::get($pNames['model'])->getColumns($pNames['grid']);
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
