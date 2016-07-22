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
    protected static $modelFolder = null;
    protected $root_path;
    protected $configOptions = array();
    public $debug = false;
    public $model;
    public $url;
    //
    public $table = '';
    public $sql = '';
    protected $title;
    private $colDs = array();
    private $gridsDx = null;
    private static $idParseCount = 0;
    private $idParseCountArray = array();
    
    protected $keywords = array(
        'autoKey' => 'autoKey',
        'boolean' => 'type',
        'currency' => 'type',
        'check' => 'check',
        'date' => 'type',
        'db' => 'db',
        'default' => 'default',
        'description' => 'description',
        'email' => 'type',
        'emails' => 'type',
        'foreignKey' => 'foreignKey',
        'format' => 'format',
        'hidden' => 'display',
        'integer' => 'type',
        'maxLength' => 'xxxxx',
        'name' => 'name',
        'number' => 'type',
        'required' => 'validations',
        'orderBy' => 'orderBy',
        'string' => 'type',
        'title' => 'title',
        'type' => 'type',
        'uniqueKey' => 'constraints',
        'url' => 'type',
        'value' => 'value',
        'validations' => 'validations',
        'visualClass' => 'visualClass',
    );
    protected $keywordsToDbTypes = array(
        'autoKey' => 'integer',
        'foreignKey' => 'integer',
        'maxLength' => 'string',
    );
     protected $typesToDbTypes = array(
        'email' => 'string',
        'url' => 'string',
    );
    protected $keywordsSingle = array(
        'check', 'default', 'db', 'description', 'name', 'orderBy', 'title', 'type', 'value'
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
        if ($sufix === '') {
            self::$idParseCount++;
            return 'd2h_'.self::$idParseCount;
        } else {
            if (!array_key_exists($sufix, $this->idParseCountArray)) {
                $this->idParseCountArray[$sufix] = 0;
            }
            $this->idParseCountArray[$sufix]++;
            return 'd2h_' . $this->idParseCountArray[$sufix] . '_' . $sufix;
        }
    }
    public function getColDs()
    {
        return $this->colDs;
    }
    public function getGridsDs()
    {
        return $this->gridsDx->getValues();
    }
    public function getGridDx($grid)
    {
        return $this->gridsDx->getCollection($grid);
    }
    public function getTitle()
    {
        return $this->title;
    }
    /**
     */
    public function parse()
    {
        if ($this->table !== '' && $this->sql !== '') {
            throw new Exception(
                "Can't be simultaneously set `\$this->table` and `\$this->sql`."
            );
        }
        $aux = $this->definitions();
        $def = new Data2Html_Collection($aux);
        $this->table = $def->getString('table');
        $this->title = $def->getString('title', $this->table);
        
        $fields = $this->parseFields($def->getArray('fields'));
        $this->colDs = $fields;
        
        $grids = $def->getArray('grids', array());
        foreach ($grids as $k => &$s) {
            $this->parseGrid($k, $s, $fields);
        }
        $this->gridsDx = new Data2Html_Collection($grids, true);
    }

    protected function parseGrid($gridName, &$grid, $fields)
    {
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
        } else {
            $grid['filter'] = array();
        }
        $grid['columns'] = 
            $this->parseColumns(
                $gridName,
                $gridDx->getArray('columns'),
                $fields
            );
        if (count($grid['columns']) === 0) { // if no columns set as all fields
            $grid['columns'] = $fields;
        }
    }
    protected function parseColumns($gridName, $colums, $fields)
    {
        if (!$colums) {
            return array();
        }
        $fieldsDx = new Data2Html_Collection($fields);
        $pColumns = array();
        foreach ($colums as $k => $v) {
            $pKey = 0;
            $pCol = null;
            if (is_string($v)) {
                if (substr($v, 0, 1) === '=') {
                    list($pKey, $pCol, ) = $this->parseField(
                        $k,
                        array( 
                            'value' => substr($v, 1),
                            'db' => null
                        )
                    );
                } else {
                    if (is_int($k)) {
                        $pKey = $v;
                    } else {
                        $pKey = $k;
                    }
                    $pCol = $fieldsDx->getArray($v);
                }
            } elseif (is_array($v)) {
                $pKey = $k;
                $pCol = $fieldsDx->getArray($v);
                if ($pCol) {
                    $pCol = array_merge($pCol, $v);
                }
            }
            $this->addItem($gridName, $pKey, $pCol, $pColumns);
        }
        $matchedFields = array();
        foreach ($pColumns as $v) {
            if (isset($v['serverMatches'])) {
                $matchedFields = array_merge(
                    $matchedFields,
                    $v['serverMatches'][1]
                );
            }
        }
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $key) {
                if (!array_key_exists($key, $pColumns)) {
                    if (array_key_exists($key, $fields)) {
                        $pColumns[$key] = $fields[$key];
                    } else {
                        throw new Exception(
                            "Match `\$\${{$key}}` used in a grid not exist on `fields`."
                        );
                    }
                }
            }
        } 
        return $pColumns;
    }

    protected function addItem($objecName, $pKey, &$pItem, &$pList)
    {
        if (is_int($pKey) || array_key_exists($pKey, $pList)) {
            $pKey = $this->createIdParse($objecName);
        }
        $pList[$pKey] = $pItem;
        return $pKey;
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
                        "Filter on grid `{$gridName}`: as string `{$k}=>\"{$v}\"` needs a key as string."
                    ); 
                }
            }
            $pFieldDx->set($pField);              
            $name = $pFieldDx->getString('name');
            if ($name) {
                if (!array_key_exists($name, $fields)) {
                    throw new Exception(
                        "Filter on grid `{$gridName}`: Field `{$k}=>[... 'name'=>'{$name}']` uses a name that not exist on `fields`."
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
                    "Filter on grid `{$gridName}`: `{$k}=>[...]` requires a `db` key."
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
                if (isset($nv['serverMatches'])) {
                    $matchedFields = array_merge(
                        $matchedFields,
                        $nv['serverMatches'][1]
                    );
                }
            }
            $this->addItem('field', $pKey, $pField, $pFields);
        }
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $v) {
                if (!isset($pFields[$v])) {
                    throw new Exception(
                        "Match `\$\${{$v}}` not exist on `fields`."
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
                        "On field `{$k}` exist attribute 'orderBy' whit item
                        `{$f}` that not exist on `fields` with `db`."
                    );
                }
            }
        }
        return $pFields;
    }

    protected function parseField($key, $field)
    {    
        $fieldDx = new Data2Html_Collection($field);
        $name = $fieldDx->getString('name', (is_int($key) ? null : $key));
        $db = $fieldDx->getString('db', $name, true); // return null if ['db'] is null 
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
                    "Word \"{$word}\" on field \"{$key}\" is not supported."
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
            $pField['db'] = null;
            $matches = null;
            preg_match_all('/\$\$\{([\w.:]+)\}/', $value, $matches);
            if (count($matches[0]) > 0) {
                $pField['serverMatches'] = $matches;
            }
        }
        return array($pKey, $pField);
    }
    
    // ========================
    // Server
    // ========================
    /**
     * Render
     */    
    public function render($template, $grid)
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
            if (!isset(self::$modelObjects[$modelBase])) {
                self::$modelFolder = $modelFolder;
                self::$modelObjects[$modelBase] =
                    self::createFromModel($model, $controllerUrl);
            }
            return self::$modelObjects[$modelBase];
        } else {
            throw new Exception('The URL parameter `?model=` is not set.');
        }
    }
    private static function createFromModel($model, $controllerUrl)
    {
            $ds = DIRECTORY_SEPARATOR;
            $path = dirname($controllerUrl).$ds;
            
            $modelX = explode(':', $model);
            $file = $modelX[0].'.php';
            if (self::$modelFolder) {
                $file = self::$modelFolder . $ds . $file;
            }
            $phisicalFile = $path.$file;
            if (file_exists($phisicalFile)) {
                require $phisicalFile;
                $class =$modelX[0];
                $data = new $class(
                    basename($controllerUrl).'?model='.$model.'&'
                );
                return $data;
            } else {
                throw new Exception(
                    "load('{$model}'): File \"{$file}\" does not exist.");
            }
    }
}
