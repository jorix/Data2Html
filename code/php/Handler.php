<?php
namespace Data2Html;

use Data2Html\DebugException;
use Data2Html\Controller\SqlSelect;
use Data2Html\Data\Lot;
use Data2Html\Data\To;
use Data2Html\Data\Response;

/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..vDx: Definitions as a `Lot` instance.
 *      * ..Ds: Definitions as a array.
 */
 
class Handler
{
    //protected $db_params;
    protected static $modelObjects = array();
    protected static $modelFolder = null;

    // ========================
    // Server
    // ========================
    /**
     * Controller
     */    
    public static function manage($request)
    {
        try {
            $payerNames = self::parseRequest($request);
            $model = self::getModel($payerNames['model']);
            $controller = new Controller();
            Response::json($controller->manage($model, $request));
        } catch(\Exception $e) {
            // Message to user
            // header('HTTP/1.1 409 Conflict');
            header('HTTP/1.1 500 Error');
            Response::json(DebugException::toArray($e));
        }
    }
 
    /**
     * Render
     */    
    public static function render($request, $templateName)
    {
        try {
            $payerNames = self::parseRequest($request);
            $model = self::getModel($payerNames['model']);            
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
        $render = self::createRender();
        if (array_key_exists('block', $payerNames)) {
            $result = $render->renderBlock($model, $payerNames['block'], $templateName);
        } elseif (array_key_exists('grid', $payerNames)) {
            $result = $render->renderGrid($model, $payerNames['grid'], $templateName);
        } else {
            throw new \Exception("No requested object name in parameter.");
        }
        return $result;
    }

    public static function createRender()
    {
        try {
            return new Render();
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
    }
    
    /**
     * Load and create one model
     * $modelName string||array
     */
    public static function getModel($modelName)
    {
        if (!$modelName) {
            throw new \Exception("Don't use `getModel()` without modelName.");
        }
        if (!array_key_exists($modelName, self::$modelObjects)) {
            try {
                self::$modelObjects[$modelName] = new Model($modelName);
            } catch(\Exception $e) {
                // Is assumed is called from a view, so message on html        
                echo DebugException::toHtml($e);
                exit();
            }
        }
        return self::$modelObjects[$modelName];
    }

    public static function parseRequest($request) 
    {
        if (array_key_exists('block', $request)) {
            $elements = explode(':', $request['block']);
            return [
                'model' => $elements[0],
                'block' => count($elements) > 1 ? $elements[1] : ''
            ];
        } elseif (array_key_exists('grid', $request)) {
            $elements = explode(':', $request['grid']);
            return [
                'model' => $elements[0],
                'grid' => count($elements) > 1 ? $elements[1] : ''
            ];
        } else {
            throw new DebugException(
                'The URL parameter `?block=`  or `?grid=` is not set.',
                $request
            );
        }
    }
    public static function parseLinkText($linkText) 
    {
        try {
            parse_str('grid=' . $linkText, $reqArr);
            return self::parseRequest($reqArr);
        } catch(\Exception $e) {
            throw new \Exception("Link \"{$linkText}\" can't be parsed.");
        }
    }
}
