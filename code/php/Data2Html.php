<?php
/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..Dx: Definitions as a `Data2Html_Collection`.
 *      * ..Ds: Definitions as a array.
 */
 
abstract class Data2Html
{
    //protected $db_params;
    protected static $modelObjects = array();
    protected static $controllerUrl = null;
    protected static $modelFolder = null;
    
    // 
    protected $root_path;
    protected $configOptions = array();
    public $debug = false;
    protected $reason;
    
    // Parsed object definitions
    protected $title = '';
    public $table = '';
    public $url = '';
    private $colDs = null;
    private $gridsDs = null;
    
    // To parse
    private $idParseCountArray = array();
    protected $matchLinkedOnce = '/^([a-z]\w*|.)\[([a-z]\w*|\d+)\]$/';
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
        'orderBy' => 'orderBy',
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
        'orderBy',
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
    public function __construct($url = '')
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }

        // Base
        //----------------
        $this->root_path = dirname(__FILE__).DIRECTORY_SEPARATOR;
        
        // Register autoload
        //------------------
        spl_autoload_register(array($this, 'autoload'));

        // Load config if exists
        //------------------
        if (file_exists('d2h_config.ini')) {
            $this->configOptions = parse_ini_file('d2h_config.ini', true);
        }
        $aux = new Data2Html_Collection($this->configOptions);
        $config = $aux->getCollection('config');
        $this->debug = $config->getBoolean('debug');
        
        // Init
        //----------------
        if ($url) {
            $this->url = $url;
        }
    }
    public function getRoot()
    {
        return $this->root_path;
    }
    public function createIdParse($sufix = '') {
        if (!array_key_exists($sufix, $this->idParseCountArray)) {
            $this->idParseCountArray[$sufix] = 0;
        }
        $this->idParseCountArray[$sufix]++;
        return 'd2h_' . $this->idParseCountArray[$sufix] . '_' . $sufix;
    }
    public function getColDs()
    {
        return $this->colDs;
    }
    public function getGrid($gridName)
    {
        if ($this->colDs === null) {
            $this->parse();
        }
        if (!$gridName) {
            $gridName = 'default';
        }
        if (!array_key_exists($gridName, $this->gridsDs)) {
            throw new Exception(
                "{$this->reason}: Grid \"{$gridName}\" not exist on `grids`."
            );
        }
        return $this->gridsDs[$gridName];
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
    public function getReason()
    {
        return $this->reason;
    }
    /**
     */
    public function parse()
    {
        $aux = $this->definitions();
        $def = new Data2Html_Collection($aux);
        $this->table = $def->getString('table');
        $this->reason = "Model of table \"{$this->table}\"";
        $this->title = $def->getString('title', $this->table);
        
        $fields = $this->parseFields($def->getArray('fields'));
        $this->colDs = $fields;
        
        $this->gridsDs = $def->getArray('grids', array());
        if (!array_key_exists('default', $this->gridsDs)) {
            $this->gridsDs['default'] = array();
        }
        foreach ($this->gridsDs as $k => &$s) {
            $this->parseGrid($k, $s, $fields);
        }
    }
    protected function parseFields($fields)
    {    
        $pFields = array();
        $fSorts = array();
        $matchedFields = array();
        foreach ($fields as $k => &$v) {
            list($pKey, $pField) = $this->parseField($k, $v);
            if (isset($pField['orderBy'])) {
                $fSorts += array($pField['db'] => $pField['orderBy']);
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
        foreach ($fSorts as $k => $orderFiels) {
            foreach ($orderFiels as $v) {
                $f = $v;
                if (substr($f, 0, 1) === '!') {
                   $f = substr($f, 1);
                }
                if (!Data2Html_Array::get($pFields, array($f, 'db'))) {
                    throw new Exception(
                        "{$this->reason}: On field `{$k}` exist attribute 'orderBy' whit item
                        `{$f}` that not exist on `fields` with `db`."
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
        $db = null; //$fieldDx->getString('db', $name, true); // return null if ['db'] is null
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
                $pField['teplateItems'] = $matches;
            }
        }
        return array($pKey, $pField);
    }

    protected function parseGrid($gridName, &$grid, $fields)
    {
        $grid['name'] = $gridName;
        $gridDx = new Data2Html_Collection($grid);
        $filterDx = $gridDx->getCollection('filter');
        if ($filterDx) {
            $grid['filter'] = array(
                'layout' => $filterDx->getString('layout'),
                'fields' => $this->parseFilter(
                    $gridName,
                    $filterDx->getArray('fields'),
                    $fields
                )
            );
        }
        list($grid['keys'], $columns) = $this->parseColumns(
            $gridName,
            $gridDx->getArray('columns', array()),
            $fields
        );
        $grid['table'] = $this->table;
        $grid['columns'] = $columns;
    }

    protected function parseColumns($gridName, $columns, $fields)
    {
        if (count($columns) === 0) { // if no columns set as all fields
            $columns = $fields;
        }
        $fieldsDx = new Data2Html_Collection($fields);
        $pColumns = array();
        foreach ($columns as $k => $v) {
            $pKey = 0;
            $pCol = null;
            if (is_string($v)) {
                if (substr($v, 0, 1) === '=') { // Is a value
                    list($pKey, $pCol) = $this->parseField(
                        $k,
                        array( 
                            'value' => substr($v, 1),
                            'db' => null
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
                $nameField = Data2Html_Array::get($v, 'name');
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
        $matchedFields = array();
        $keyFields = array();
        foreach ($pColumns as $k => $v) {
            if (array_key_exists('teplateItems', $v)) {
                $matchedFields = array_merge(
                    $matchedFields,
                    $v['teplateItems'][1]
                );
            }
            if (array_key_exists('key', $v)) {
                array_push($keyFields, $k);
            }
        }
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $key) {
                if (!array_key_exists($key, $pColumns)) {
                    if (array_key_exists($key, $fields)) {
                        $pColumns[$key] = $fields[$key];
                    } else {
                        throw new Exception(
                            "{$this->reason}: Match `\$\${{$key}}` used in grid `{$gridName}` not exist on `fields`."
                        );
                    }
                }
            }
        } 
        return array($keyFields, $pColumns);
    }
    
    protected function parseFilter($gridName, $filter, $fields)
    {
        if (!$filter) {
            return array();
        }
        $baseFiledsDx = new Data2Html_Collection($fields);
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
                if (!array_key_exists($name, $fields)) {
                    throw new Exception(
                        "{$this->reason}: Filter on grid `{$gridName}`: Field `{$k}=>[... 'name'=>'{$name}']` uses a name that not exist on `fields`."
                    );
                }
                $pField = array_merge($fields[$name], $pField);
                $db = $pFieldDx->getString('db');
            } else {
                $db = $pFieldDx->getString('db');
                $name = $db;
            }
            if (!$db) {
                throw new Exception(
                    "{$this->reason}: Filter on grid `{$gridName}`: `{$k}=>[...]` requires a `db` key."
                );
            }
            if (is_int($pKey)) {
                $pKey = $name.'_'.$pFieldDx->getString('check', '');
            }
            list($pKey, $pField) = $this->parseField($pKey, $pField);
            $pKey = $this->addItem($gridName, $pKey, $pField, $pFields);
        }
        return $pFields;
    }
    
    // ========================
    // Server
    // ========================
    /**
     * Render
     */    
    public function render($template, $grid = 'default')
    {
        try {
            $this->parse();
            $render = new Data2Html_Render($template);
            echo $render->render($this, $grid);
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, $this->debug);
        }
    }
    /**
     * Controller
     */    
    public function manage($fileNameConfigDb = 'd2h_config_db.ini')
    {
        try {$this->parse();
            $controller = new Data2Html_Controller($this, $fileNameConfigDb);
            $this->responseJson($controller->manage());
        } catch(Exception $e) {
            // Message to user
            if ($e instanceof Data2Html_Exception_User) {
                header('HTTP/1.1 409 Conflict');
            } else {
                header('HTTP/1.1 500 Error');
            }
            $this->responseJson(Data2Html_Exception::toArray($e, $this->debug));
        }
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
     * Auto load.
     */
    protected function autoload($class)
    {
        #Not a Data2Html_% class
        if (strpos($class, 'Data2Html_') !== 0) {
            return;
        }
        $file = str_replace('_', '/', $class).'.php';
        $phisicalFile = $this->root_path . $file;
        #Do not interfere with other autoloads
        if (file_exists($phisicalFile)) {
            require $phisicalFile;
        } else {
            throw new Exception(
                "->autoload({$class}): File \"{$file}\" does not exist");
        }
    }
    // -------------
    /**
     * PHP-object to a JSON text
     */
    public function toJson($obj)
    {
        return Data2Html_Value::toJson($obj, $this->debug);
    }
    /**
     * @param $obj object to send
     */
    protected function responseJson($obj)
    {
        if ($this->debug && isset($_REQUEST['debug'])) {
            echo "<pre>\n".$this->toJson($obj)."\n</pre>\n";
        } else {
            header('Content-type: application/responseJson; charset=utf-8;');
            // The prefix `)]}',\n` is used due a security considerations, see: 
            //    * https://docs.angularjs.org/api/ng/service/$http
            echo ")]}',\n".$this->toJson($obj);
        }
    }
    /**
     * Load and create one model
     */
    public static function create($controllerUrl, $modelFolder = null)
    {
        if (isset($_REQUEST['model'])) {
            $model = $_REQUEST['model'];
            $modelElements = explode(':', $model);
            $modelBase = $modelElements[0];
            if (!array_key_exists($modelBase, self::$modelObjects)) {
                self::$controllerUrl = $controllerUrl;
                self::$modelFolder = $modelFolder;
                self::createModel($model, $controllerUrl);
            }
            return self::$modelObjects[$modelBase];
        } else {
            throw new Exception('The URL parameter `?model=` is not set.');
        }
    }
    public static function createModel($modelName)
    {
            if (self::$controllerUrl === null) {
                throw new Exception(
                    'Don\'t use `createModel()` before a call to `create()` method.');
            }
            $ds = DIRECTORY_SEPARATOR;
            $path = dirname(self::$controllerUrl).$ds;
            
            $modelElements = explode(':', $modelName);
            $modelBase = $modelElements[0];
            $file = $modelBase . '.php';
            if (self::$modelFolder) {
                $file = self::$modelFolder . $ds . $file;
            }
            $phisicalFile = $path.$file;
            if (file_exists($phisicalFile)) {
                require $phisicalFile;
                $data = new $modelBase(
                    basename(self::$controllerUrl) . 
                    '?model=' . $modelName . '&'
                );
                self::$modelObjects[$modelBase] = $data;
                return $data;
            } else {
                throw new Exception(
                    "load('{$modelName}'): File \"{$file}\" does not exist.");
            }
    }
    public static function getGridNameByModel($modelName)
    {
        $modelElements = explode(':', $modelName);
        return 
            count($modelElements) > 1 ?
            $modelElements[1] :
            'default';
    }
}
