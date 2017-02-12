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
    protected $title = '';
    public $table = '';
    protected $modelName = '';
    protected $culprit = '';
    private $fields = null;
    private $grids = null;
    private $forms = null;
    
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
        
        $this->parse();
    }

    public function getModelName()
    {
        return $this->modelName;
    }
    public function getControllerUrl()
    {
        return Data2Html_Config::get('controllerUrl') . '?';
    }
    public function getColDs()
    {
        return $this->fields;
    }
    public function getGrid($gridName = '')
    {
        if (!$gridName) {
            $gridName = 'default';
        }
        if (!array_key_exists($gridName, $this->grids)) {
            throw new Exception(
                "{$this->culprit}: Grid \"{$gridName}\" not exist on `grids`."
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
             $this->forms[$formName]['fields'] = $this->parseFormFields(
                $formName,
                Data2Html_Value::getItem($this->forms[$formName], 'fields', array()),
                $this->fields
            );
            $this->forms[$formName]['_parsed'] = true;
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
    /**
     */
    protected function parse()
    {
        $aux = $this->definitions();
        $def = new Data2Html_Collection($aux);
        $this->table = $def->getString('table');
        $this->title = $def->getString('title', $this->table);
        
        $baseFields = $this->parseFields($def->getArray('fields'));
        $this->fields = $baseFields;
        
        $this->grids = $def->getArray('grids', array());
        if (!array_key_exists('default', $this->grids)) {
            $this->grids['default'] = array();
        }
        foreach ($this->grids as $k => &$v) {
            $this->parseGrid($k, $v, $baseFields);
        }
        unset($v);
        
        
        $this->forms = $def->getArray('forms', array());
        if (!array_key_exists('default', $this->forms)) {
            $this->forms['default'] = array();
        }
    }

    protected function parseFields($fields)
    {    
        $set = new Data2Html_Model_Set('fields');
        $matchedFields = array();
        foreach ($fields as $k => &$v) {
            $pKey = $set->addParse($k, $v);
            $pField = $set->getItem($pKey);
            if (isset($pField['sortBy'])) {
                $sortBy = $pField['sortBy'];
                if (is_string($sortBy)) {
                    $sortBy = array($sortBy);
                }
                $sortByNew = array();
                foreach ($sortBy as $sk => $sv) {
                    if (is_numeric($sk)) {
                        if (substr($sv, 0, 1) === '!') {
                            $sortByNew[substr($sv, 1)] = 'desc';
                        } else {
                            $sortByNew[$sv] = 'asc';
                        }
                    } else {
                        $sortByNew[$sk] = (preg_match("/php/i", 'desc') ? 'desc' : 'asc');
                    }
                }
                $pField['sortBy'] = $sortByNew;
            } elseif (!array_key_exists('sortBy', $pField) && isset($pField['db'])) {
                $pField['sortBy'] = array($k => 'asc');
            } else { // is set to null
                unset($pField['sortBy']);
            }
            foreach ($pField as $nv) {
                if (isset($nv['teplateItems'])) {
                    $matchedFields = array_merge(
                        $matchedFields,
                        $nv['teplateItems'][1]
                    );
                }
            }
            $set->setItem($pKey, $pField);
        }
        $pFields = $set->getItems();
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $v) {
                if (!isset($pFields[$v])) {
                    throw new Exception(
                        "{$this->culprit}: Match `\$\${{$v}}` not exist on `fields`."
                    );
                }
            }
        }
        return $pFields;
    }
    protected function parseGrid($gridName, &$grid, $baseFields)
    {
        $grid['name'] = $gridName;
        $gridDx = new Data2Html_Collection($grid);
        $filterDx = $gridDx->getCollection('filter');
        if ($filterDx) {
            $grid['filter'] = array(
                'layout' => $filterDx->getString('layout'),
                'fields' => $this->parseFilterFields(
                    $gridName,
                    $filterDx->getArray('fields', array()),
                    $baseFields
                )
            );
        }
        list($grid['keys'], $columns) = $this->parseColumns(
            $gridName,
            $gridDx->getArray('columns', array()),
            $baseFields
        );
        $grid['modelName'] = $this->modelName;
        $grid['table'] = $this->table;
        $grid['columns'] = $columns;
        $grid['columnNames'] = array_keys($columns);
        $grid['_parsed'] = true;
    }

    protected function parseColumns($gridName, $columns, $baseFields)
    {
        if (count($columns) === 0) { // if no columns set as all baseFields
            $columns = $baseFields;
        }
        $fieldsDx = new Data2Html_Collection($baseFields);
        $set = new Data2Html_Model_Set($gridName);
        foreach ($columns as $k => $v) {
            $pKey = 0;
            $pCol = null;
            if (is_string($v)) {
                if (substr($v, 0, 1) === '=') { // Is a value
                    $set->addParse($k, array('value' => substr($v, 1)));
                } elseif (preg_match($this->matchLinkedOnce, $v)) { // Is a link
                    $set->addParse($k, array('db' => $v));
                } else {
                    $pCol = $fieldsDx->getArray($v);
                    if (!$pCol) {
                        throw new Exception(
                            "{$this->culprit}: Field `{$v}` used in grid `{$gridName}` not exist on `fields`."
                        );
                    }
                    if (is_int($k)) {
                        $set->addParse($v, $pCol);
                    } else {
                        $set->addParse($k, $pCol);
                    }
                }
            } elseif (is_array($v)) {
                $nameField = Data2Html_Value::getItem($v, 'name');
                if ($nameField) {
                    $baseField = $fieldsDx->getArray($nameField);
                    if (!$baseField) {
                        throw new Exception(
                            "{$this->culprit}: Field `{$k}` used in grid `{$gridName}` not exist on `fields`."
                        );
                    }
                    $set->addParse($k, $v, $baseField);
                } else {
                    $set->addParse($k, $v);
                }
            }
        }
        
        // Final parse
        $pColumns = $set->getItems();
        $keyFields = array();
        foreach ($pColumns as $k => $v) {
            if (array_key_exists('key', $v)) {
                array_push($keyFields, $k);
            }
        }
        return array($keyFields, $pColumns);
    }
    
    protected function parseFilterFields($gridName, $filter, $baseFields)
    {
        $baseFiledsDx = new Data2Html_Collection($baseFields);
        $set = new Data2Html_Model_Set($gridName);
        $pFieldDx = new Data2Html_Collection();
        foreach ($filter as $k => $v) {
            $pKey = 0;
            $pField = null;
            if (is_array($v)) {
                $pKey = $k;
                $pField = $v;
            } elseif (is_string($v)) {
                if (is_string($k)) {
                    $pKey = $k;
                    $pField = array('name' => $k, 'check' => $v);
                } else {
                    throw new Exception(
                        "{$this->culprit}: Filter on grid `{$gridName}`: as string `{$k}=>\"{$v}\"` needs a key as string."
                    ); 
                }
            }
            $pFieldDx->set($pField);              
            $name = $pFieldDx->getString('name');
            if ($name) {
                // if (!array_key_exists($name, $baseFields)) {
                    // throw new Exception(
                        // "{$this->culprit}: Filter on grid `{$gridName}`: Field `{$k}=>[... 'name'=>'{$name}']` uses a name that not exist on `fields`."
                    // );
                // }
                $pField = array_merge($baseFields[$name], $pField);
                $db = $pFieldDx->getString('db');
            } else {
                $db = $pFieldDx->getString('db');
                $name = $db;
            }
            if (!$db && array_key_exists('check', $pField) ) {
                throw new Exception(
                    "{$this->culprit}: Filter on grid `{$gridName}`: `{$k}=>[...]` requires a `db` attribute."
                );
            }
            if (is_int($pKey)) {
                $pKey = $name.'_'.$pFieldDx->getString('check', '');
            }
            $set->addParse($pKey, $pField);
        }
        return $set->getItems();
    }
    
    protected function parseFormFields($formName, $fields, $baseFields)
    {
        if (count($fields) === 0) { // if no columns set as all baseFields
            $fields = $baseFields;
        }
        $baseFiledsDx = new Data2Html_Collection($baseFields);
        $set = new Data2Html_Model_Set($formName);
        $pFieldDx = new Data2Html_Collection();
        foreach ($fields as $k => $v) {
            $pFieldDx->set($v);              
            $name = $pFieldDx->getString('name');
            if ($name) {
                // if (!array_key_exists($name, $baseFields)) {
                    // throw new Exception(
                        // "{$this->culprit}: Form `{$formName}`: Field `{$k}=>[... 'name'=>'{$name}']` uses a name that not exist on `fields`."
                    // );
                // }
                $v = array_merge($baseFields[$name], $v);
                $db = $pFieldDx->getString('db');
            } else {
                $db = $pFieldDx->getString('db');
                $name = $db;
            }
            if (is_int($k)) {
                $k = $name.'_'.$pFieldDx->getString('check', '');
            }
            $set->addParse($k, $v);
        }
        return $set->getImetms();
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
            throw new Exception('The URL parameter `?model=` is not set.');
        }
        list($modelName, $gridName) = self::explodeLink($request['model']);
        $response = array('model' => $modelName);
        if (array_key_exists('form', $request)) {
            $response['form'] = $request['form'];
        } elseif (array_key_exists('grid', $request)) {
            $response['grid'] = $request['grid'];
        } elseif ($gridName) {
            $response['grid'] = $gridName;
        }
        return $response;
        
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
