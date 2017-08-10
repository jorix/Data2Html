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

    protected static function responseJson($obj, $debug)
    {
        if ($debug && isset($_REQUEST['debug'])) {
            echo "<pre>\n" . Data2Html_Value::toJson($obj, $debug). "\n</pre>\n";
        } else {
            header('Content-type: application/responseJson; charset=utf-8;');
            // The prefix `)]}',\n` is used due a security considerations, see: 
            //    * https://docs.angularjs.org/api/ng/service/$http
            echo // ")]}',\n" . 
                Data2Html_Value::toJson($obj, $debug);
        }
    }
    
    // ========================
    // Server
    // ========================
    /**
     * Render
     */    
    public static function render($request, $template)
    {
        try {
            $payerNames = self::parseRequest($request);
            $model = self::getModel($payerNames['model']);
            $render = new Data2Html_Render($template, $model);
            $resul = $render->render($payerNames);
            echo 
                "{$resul['html']}
                \n<script>{$resul['js']}</script>";
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
        }
    }
    /**
     * Controller
     */    
    public static function manage($request)
    {
        $debug = Data2Html_Config::debug();
        try {
            $payerNames = self::parseRequest($request);
            $model = self::getModel($payerNames['model']);
            $controller = new Data2Html_Controller($model);
            self::responseJson($controller->manage($request), $debug);
        } catch(Exception $e) {
            // Message to user
            if ($e instanceof Data2Html_Exception_User) {
                header('HTTP/1.1 409 Conflict');
            } else {
                header('HTTP/1.1 500 Error');
            }
            try {
                self::responseJson(Data2Html_Exception::toArray($e, $debug), $debug);
            } catch(Exception $ee) {
                header('Content-type: application/responseJson; charset=utf-8;');                
                echo serialize(Data2Html_Exception::toArray($e, $debug));
            }
        }
    }
    /**
     * Load and create one model
     * $modelName string||array
     */
    public static function getModel($modelName)
    {
        if (!$modelName) {
            throw new Exception("Don't use `getModel()` without modelName.");
        }
        if (array_key_exists($modelName, self::$modelObjects)) {
            return self::$modelObjects[$modelName];
        } elseif (count(self::$modelObjects) === 0) {
            self::$modelFolder = Data2Html_Config::get('modelFolder');
        }
        
        $ds = DIRECTORY_SEPARATOR;
        $modelFile = self::$modelFolder . $ds . $modelName . '.php';
        if (file_exists($modelFile)) {
            require $modelFile;
            $model = new $modelName();
            self::$modelObjects[$modelName] = $model;
            return $model;
        } else {
            throw new Exception(
                "Can't load model \"{$modelName}\": File \"{$modelFile}\" does not exist.");
        }
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
            list($modelName, $gridName) = self::explodeLink($request['model']);
            if ($gridName) {
                $response = array('model' => $modelName);
                $response['grid'] = $gridName;
            }
        }
        return $response;
        
    }
    public static function parseLinkText($linkText) 
    {
        try {
            parse_str('model=' . $linkText, $reqArr);
        } catch(Exception $e) {
            throw new Exception(
                "Link \"{$linkText}\" can't be parsed.");
            return null;
        }
        return self::parseRequest($reqArr);
    }
    protected static function explodeLink($modelLink)
    {
        $modelElements = explode(':', $modelLink);
        $gridName = 
            count($modelElements) > 1 ?
            $modelElements[1] :
            'default';
        return array($modelElements[0], $gridName);
    }
}
