<?php
/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..Dx: Definitions as a `Data2Html_Collection`.
 *      * ..Ds: Definitions as a array.
 */
 
abstract class Data2Html_Model
{
    //protected $db_params;
    protected static $modelObjects = array();
    protected static $modelFolder = null;
    
    // 
    protected $configOptions = array();
    protected $debug = false;
    
    // Parsed object definitions
    protected $table = '';
    protected $definitions = null;
    protected $title = '';
    protected $modelName = '';
    protected $culprit = '';
    private $base = null;
    private $grids = array();
    private $forms = array();
    
    // To parse
    protected $matchLinkedOnce = '/^[a-z]\w*\[([a-z]\w*|\d+)\]$/';

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
        
        $dx = new Data2Html_Collection($this->definitions);
        $this->table = $dx->getString('table');
        $this->title = $dx->getString('title', $this->table);
        $this->base = new Data2Html_Model_Set_Base(
            $this, null, $dx->getArray('base')
        );
        //$this->parse();
    }
    abstract protected function definitions();
 
    public function getModelName()
    {
        return $this->modelName;
    }
    public function getControllerUrl()
    {
        return Data2Html_Config::get('controllerUrl') . '?';
    }
    public function getBaseFields()
    {
        return $this->fields;
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
            "{$this->culprit}: The \"{$gridName}\" grid not exist on grid definitions.",
                    $this->definitions
                );
            }
            $this->grids[$gridName] = new Data2Html_Model_Grid(
                    $this, $gridName, $gridsDf[$gridName], $this->base
            );
        }    
        return $this->grids[$gridName];
    }
    
    public function getForm($formName = '')
    {
        if (!$formName) {
            $formName = 'default';
        }
        if (!array_key_exists($formName, $this->forms)) {
            throw new Exception(
                "{$this->culprit}: Form \"{$formName}\" not exist on `forms`."
            );
        }
        if (!Data2Html_Value::getItem($this->forms[$formName],'_parsed')) {
             $this->forms[$formName] = new Data2Html_Model_Set_Form($this,
                $formName,
                Data2Html_Value::getItem($this->forms[$formName], 'fields', array()),
                $this->fields
            );
        }
        return $this->forms[$formName];
    }
    public function getLinkedGrid($gridName) {
        $link = new Data2Html_Parse_Link($this);
        return $link->getGrid($gridName);
    }
    public function getTable()
    {
        return $this->table;
    }
    public function getTitle()
    {
        return $this->title;
    }
    
    protected function parseFilterFields($gridName, $filter, $baseFields)
    {
        return;
    }
    
    protected function parseFormFields($formName, $fields, $baseFields)
    {
        return;
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
    {    }

    // ========================
    // Utils
    // ========================
    // -------------

    /**
     * @param $obj object to send
     */
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
            $payerNames = self::extractPlayerNames($request);
            $model = self::createModel($payerNames['model']);
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
            $payerNames = self::extractPlayerNames($request);
            $model = self::createModel($payerNames['model']);
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
    public static function createModel($modelName)
    {
        if (array_key_exists($modelName, self::$modelObjects)) {
            return self::$modelObjects[$modelName];
        }
        if (count(self::$modelObjects) === 0) {
            self::$modelFolder = Data2Html_Config::get('modelFolder');
        }
        if (!$modelName) {
            throw new Exception("Don't use `createModel()` without modelName.");
        }
        if (self::$modelFolder === null) {
            throw new Exception(
                'Don\'t use `createGrid()` before load a parent grid.');
        }
        $ds = DIRECTORY_SEPARATOR;
        $modelFile = self::$modelFolder . $ds . $modelName . '.php';
        if (file_exists($modelFile)) {
            require $modelFile;
            $data = new $modelName();
            self::$modelObjects[$modelName] = $data;
            return $data;
        } else {
            throw new Exception(
                "load('{$modelName}'): File \"{$modelFile}\" does not exist.");
        }
    }
            
    public static function extractPlayerNames($request) 
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
    public static function linkToPlayerNames($linkText) 
    {
        try {
            parse_str('model=' . $linkText, $reqArr);
        } catch(Exception $e) {
            throw new Exception(
                "Link \"{$linkText}\" can't be parsed.");
            return null;
        }
        return self::extractPlayerNames($reqArr);
    }
    public static function explodeLink($modelLink)
    {
        $modelElements = explode(':', $modelLink);
        $gridName = 
            count($modelElements) > 1 ?
            $modelElements[1] :
            'default';
        return array($modelElements[0], $gridName);
    }
}
