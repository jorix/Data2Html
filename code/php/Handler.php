<?php
/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..vDx: Definitions as a `Data2Html_Collection`.
 *      * ..Ds: Definitions as a array.
 */
 
class Data2Html_Handler
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
        $debug = Data2Html_Config::debug();
        try {
            $payerNames = self::parseRequest($request);
            $model = self::createModel($payerNames['model']);
            $controller = new Data2Html_Controller($model);
            Data2Html_Utils::responseJson($controller->manage($request), $debug);
        } catch(Exception $e) {
            // Message to user
            if ($e instanceof Data2Html_Exception_User) {
                header('HTTP/1.1 409 Conflict');
            } else {
                header('HTTP/1.1 500 Error');
            }
            Data2Html_Utils::responseJson(Data2Html_Exception::toArray($e, $debug), $debug);
        }
    }
 
    /**
     * Render
     */    
    public static function render($request, $templateName)
    {
        try {
            $payerNames = self::parseRequest($request);
            $model = self::createModel($payerNames['model']);            
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
        $render = self::createRender();
        if (array_key_exists('form', $payerNames)) {
            $result = $render->renderForm($model, $templateName, $payerNames['form']);
        } elseif (array_key_exists('grid', $payerNames)) {
            $result = $render->renderGrid($model, $templateName, $payerNames['grid']);
        } else {
            throw new Exception("no request object.");
        }
        return $result;
    }

    public static function createRender()
    {
        try {
            return new Data2Html_Render();
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
    }
    
    /**
     * Load and create one model
     * $modelName string||array
     */
    public static function createModel($modelName)
    {
        if (!$modelName) {
            throw new Exception("Don't use `createModel()` without modelName.");
        }
        if (!array_key_exists($modelName, self::$modelObjects)) {
            try {
                self::$modelObjects[$modelName] = new Data2Html_Model($modelName);
            } catch(Exception $e) {
                // Is assumed is called from a view, so message on html        
                echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
                exit();
            }
        }
        return self::$modelObjects[$modelName];
    }

    public static function parseRequest($request) 
    {
        if (!array_key_exists('model', $request)) {
            throw new Data2Html_Exception(
                'The URL parameter `?model=` is not set.',
                $request
            );
        }
        if (array_key_exists('form', $request)) {
            // as ['model' => 'model_name', 'form' => 'form_name']
            $response = array('model' => $request['model']);
            $response['form'] = $request['form'];
        } elseif (array_key_exists('grid', $request)) {
            // as ['model' => 'model_name', 'grid' => 'grid_name'}
            $response = array('model' => $request['model']);
            $response['grid'] = $request['grid'];
        } else {
            // as {'model' => 'model_name:grid_name'}
            $elements = explode(':', $request['model']);
            $response = array('model' => $elements[0]);
            $response['grid'] = count($elements) > 1 ? $elements[1] : '';
        }
        return $response;
        
    }
    public static function parseLinkText($linkText) 
    {
        try {
            parse_str('model=' . $linkText, $reqArr);
            return self::parseRequest($reqArr);
        } catch(Exception $e) {
            throw new Exception("Link \"{$linkText}\" can't be parsed.");
        }
    }
}
