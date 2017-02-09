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
    protected static $controllerUrl = null;
    protected static $modelFolder = null;
    
    // 
    protected $configOptions = array();
    protected $debug = false;
    protected $reason;
    
    // Parsed object definitions
    protected $title = '';
    public $table = '';
    protected $requestUrl = '';
    protected $modelName = '';
    private $fields = null;
    private $grids = null;
    private $forms = null;
    
    // To parse
    private $idParseCountArray = array();
    protected $matchLinkedOnce = '/^[a-z]\w*\[([a-z]\w*|\d+)\]$/';
    protected $matchTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/';
    protected $keywords = array(
        'autoKey' => 'key',
        'boolean' => 'type',
        'check' => 'check',
        'currency' => 'type',
        'date' => 'type',
        'db' => 'db',
        'default' => 'default',
        'description' => 'description',
        'digits' => 'size',
        'display' => 'display',
        'email' => 'type',
        'emails' => 'type',
        'format' => 'format',
        'hidden' => 'display',
        'integer' => 'type',
        'key' => 'key',
        'length' => 'size',
        'link' => 'link',
        'linkedTo' => 'linkedTo',
        'name' => 'name',
        'number' => 'type',
        'sortBy' => 'sortBy',
        'required' => 'validations',
        'size' => 'size',
        'string' => 'type',
        'teplateItems' => 'teplateItems',
        'title' => 'title',
        'type' => 'type',
        'uniqueKey' => 'constraints',
        'url' => 'type',
        'validations' => 'validations',
        'value' => 'value',
        'visualClass' => 'visualClass',
    );
    protected $keywordsToDbTypes = array(
        'autoKey' => 'integer',
        'digits' => 'number',
        'link' => 'integer',
        'length' => 'string',
    );
    protected $typesToDbTypes = array(
        'email' => 'string',
        'url' => 'string',
    );
    protected $keywordsSingle = array(
        'check',
        'db',
        'default',
        'description',
        'key',
        'name',
        'sortBy',
      //  'size', <- join of 'length' or 'digits' 
        'title',
        'type',
        'value',
    );
    /**
     * Class constructor, initializes basic properties.
     *
     * @param jqGridLoader $loader
     */
    public function __construct($requestUrl = '')
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }

        $this->debug = Data2Html_Config::debug();
        $this->modelName = get_class($this);
        $this->reason = "Model \"{$this->modelName}\"";
        
        $this->requestUrl = $requestUrl;
        $this->parse();
    }

    public function createIdParse($sufix = '') {
        if (!array_key_exists($sufix, $this->idParseCountArray)) {
            $this->idParseCountArray[$sufix] = 0;
        }
        $this->idParseCountArray[$sufix]++;
        return 'd2h_' . $this->idParseCountArray[$sufix] . '_' . $sufix;
    }
    public function getModelName()
    {
        return $this->modelName;
    }
    public function getControllerUrl()
    {
        return $this->requestUrl;
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
                "{$this->reason}: Grid \"{$gridName}\" not exist on `grids`."
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
                "{$this->reason}: Form \"{$formName}\" not exist on `forms`."
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
        $pFields = array();
        $matchedFields = array();
        foreach ($fields as $k => &$v) {
            list($pKey, $pField) = $this->parseField($k, $v);
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
            $this->addItem('field', $pKey, $pField, $pFields);
        }
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $v) {
                if (!isset($pFields[$v])) {
                    throw new Exception(
                        "{$this->reason}: Match `\$\${{$v}}` not exist on `fields`."
                    );
                }
            }
        }
        return $pFields;
    }
        
    protected function addItem($objecName, $pKey, &$pItem, &$pList)
    {
        if (is_int($pKey) || array_key_exists($pKey, $pList)) {
            $pKey = $this->createIdParse($objecName);
        }
        $pList[$pKey] = $pItem;
        return $pKey;
    }

    protected function parseField($key, $field)
    {
        if (is_string($field)) {
            if (substr($field, 0, 1) === '=') {
                $field = array('value' => substr($field, 1));
            } elseif (preg_match($this->matchLinkedOnce, $field)) { // Is a link
                $field = array('db' => $field);
            } else {
                throw new Exception(
                    "{$this->reason}: Field `{$key}` as string must bee a `value` " .
                    "as \"=xxx\" or a link as \"link[name]\"."
                );
            }
        }
        $fieldDx = new Data2Html_Collection($field);
        $name = $fieldDx->getString('name', (is_int($key) ? null : $key));
        $db = null;
        if (array_key_exists('db', $field)) {
            $db = $field['db'];
        } elseif (!array_key_exists('value', $field)) {
            $db = $name;
        }
        $pKey = 0;
        if (is_string($key)) {
            $pKey = $key;
        }
        $pField = array();
        if ($name) {
           // $pField['name'] = $name;
        }
        if ($db) {
            $pField['db'] = $db;
        }
        $defTypes = new Data2Html_Collection($this->keywordsToDbTypes);
        $defaultType = null;
        foreach ($field as $kk => $vv) {
            $isValue = is_int($kk);
            if ($isValue) {
                $word = $vv;
            } else {
                $word = $kk;
            }
            if (!isset($this->keywords[$word])) {
                throw new Exception(
                    "{$this->reason}: Word \"{$word}\" on field \"{$key}\" is not supported."
                );
            }
            $kwGroup = $this->keywords[$word];
            if ($kwGroup === $word) {
                $pField[$word] = $vv; 
            } elseif (in_array($kwGroup, $this->keywordsSingle)) {
                $pField[$kwGroup] = ($isValue ? $vv : array($kk => $vv)); 
            } else {
                if (!isset($pField[$kwGroup])) {
                    $pField[$kwGroup] = array();
                }
                if ($isValue) {
                    array_push($pField[$kwGroup], $vv);
                } else {
                    $pField[$kwGroup][$kk] = $vv;
                }
            }
            if (!$defaultType) {
                $defaultType = $defTypes->getString($word);
            }
        }
        if (!array_key_exists('description', $pField) &&
            array_key_exists('title', $pField)) {
            $pField['description'] = $pField['title'];
        }
        if (!isset($pField['type']) && $defaultType) {
            $pField['type'] = $defaultType;
        }
        $value = null;
        if (array_key_exists('value', $pField)) {
            $value = $pField['value'];
        }
        /*
'/>\$\$([\w.]+)</'
'/\'\$\$([\w.]+)\'/'
'/"\$\$([\w.]+)"/'
        */
        if ($value) {
            if (array_key_exists('db', $field) ) {
                if (isset($field['db'])) {
                    throw new Exception(
                        "{$this->reason}: Field \"{$key}\": `db` and `value` can not be used simultaneously."
                    );  
                }
                unset($field['db']);
            }
            $matches = null;
            // $${name} | $${link[name]}
            preg_match_all($this->matchTemplate, $value, $matches);
            if (count($matches[0]) > 0) {
                if (!array_key_exists('type', $pField)) {
                    $pField['type'] = 'string';
                }
                $pField['teplateItems'] = $matches;
            }
        }
        return array($pKey, $pField);
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
        $pColumns = array();
        foreach ($columns as $k => $v) {
            $pKey = 0;
            $pCol = null;
            if (is_string($v)) {
                if (substr($v, 0, 1) === '=') { // Is a value
                    list($pKey, $pCol) = $this->parseField(
                        $k,
                        array( 
                            'value' => substr($v, 1)
                        )
                    );
                } elseif (preg_match($this->matchLinkedOnce, $v)) { // Is a link
                    list($pKey, $pCol) = $this->parseField(
                        $k,
                        array('db' => $v)
                    );
                } else {
                    $pCol = $fieldsDx->getArray($v);
                    if (!$pCol) {
                        throw new Exception(
                            "{$this->reason}: Field `{$v}` used in grid `{$gridName}` not exist on `fields`."
                        );
                    }
                    if (is_int($k)) {
                        $pKey = $v;
                    } else {
                        $pKey = $k;
                    }
                }
            } elseif (is_array($v)) {
                $nameField = Data2Html_Value::getItem($v, 'name');
                if ($nameField) {
                    $pField = $fieldsDx->getArray($nameField);
                    if (!$pField) {
                        throw new Exception(
                            "{$this->reason}: Field `{$k}` used in grid `{$gridName}` not exist on `fields`."
                        );
                    }
                    list($pKey, $pCol) = $this->parseField($k, $v);
                    $pCol = array_merge($pCol, $pField);
                } else {
                    list($pKey, $pCol) = $this->parseField($k, $v);
                }
            }
            $this->addItem($gridName, $pKey, $pCol, $pColumns);
        }
        
        // Final parse
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
        $pFields = array();
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
                        "{$this->reason}: Filter on grid `{$gridName}`: as string `{$k}=>\"{$v}\"` needs a key as string."
                    ); 
                }
            }
            $pFieldDx->set($pField);              
            $name = $pFieldDx->getString('name');
            if ($name) {
                // if (!array_key_exists($name, $baseFields)) {
                    // throw new Exception(
                        // "{$this->reason}: Filter on grid `{$gridName}`: Field `{$k}=>[... 'name'=>'{$name}']` uses a name that not exist on `fields`."
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
                    "{$this->reason}: Filter on grid `{$gridName}`: `{$k}=>[...]` requires a `db` attribute."
                );
            }
            if (is_int($pKey)) {
                $pKey = $name.'_'.$pFieldDx->getString('check', '');
            }
            list($pKey, $pField) = $this->parseField($pKey, $pField);
            $this->addItem($gridName, $pKey, $pField, $pFields);
        }
        return $pFields;
    }
    
    protected function parseFilterChek($fieldName)
    {
    }
    
    protected function parseFormFields($formName, $fields, $baseFields)
    {
        if (count($fields) === 0) { // if no columns set as all baseFields
            $fields = $baseFields;
        }
        $baseFiledsDx = new Data2Html_Collection($baseFields);
        $pFields = array();
        $pFieldDx = new Data2Html_Collection();
        foreach ($fields as $k => $v) {
            $pFieldDx->set($v);              
            $name = $pFieldDx->getString('name');
            if ($name) {
                // if (!array_key_exists($name, $baseFields)) {
                    // throw new Exception(
                        // "{$this->reason}: Form `{$formName}`: Field `{$k}=>[... 'name'=>'{$name}']` uses a name that not exist on `fields`."
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
            list($k, $v) = $this->parseField($k, $v);
            $this->addItem($formName, $k, $v, $pFields);
        }
        return $pFields;
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
            self::$controllerUrl = Data2Html_Config::get('controllerUrl');
            self::$modelFolder = Data2Html_Config::get('modelFolder');
        }
        if (self::$controllerUrl === null) {
            throw new Exception(
                'Don\'t use `createGrid()` before load a parent grid.');
        }
        $ds = DIRECTORY_SEPARATOR;
        $path = dirname(self::$controllerUrl) . $ds;
        $file = $modelName . '.php';
        if (self::$modelFolder) {
            $file = self::$modelFolder . $ds . $file;
        }
        $phisicalFile = $path . $file;
        if (file_exists($phisicalFile)) {
            require $phisicalFile;
            $data = new $modelName(self::$controllerUrl . '?');
            self::$modelObjects[$modelName] = $data;
            return $data;
        } else {
            throw new Exception(
                "load('{$modelName}'): File \"{$file}\" does not exist.");
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
