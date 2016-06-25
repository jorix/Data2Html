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
    protected static $modelServices = array();
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
    public function createIdParse() {
        self::$idParseCount++;
        return 'd2h_'.self::$idParseCount;
    }
    public function getColDs()
    {
        return $this->colDs;
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
        $grids = $def->getArray('grids', array());
        $dvServ = new Data2Html_Collection($aux);
        foreach ($grids as $k => &$s) {
            $this->parseGrid($s, $fields);
            $dvServ->set($s);
            $kkl = array_keys($s['columns']);
            switch ($dvServ->getString('type')) {
                case 'list':
                    $s['columns'][$kkl[0]]['name'] = 'value';
                    $s['columns'][$kkl[1]]['name'] = 'text';
                    break;
            }
        }
        $this->colDs = $fields;
        $this->gridsDx = new Data2Html_Collection($grids, true);
    }

    protected function parseGrid(&$grid, $fields)
    {
        $servV = new Data2Html_Collection($grid);
        $flV = $servV->getCollection('filter');
        $grid['filter'] = array(
            'layout' => $flV->getString('layout'),
            'fields' => $this->parseFilter($flV->getArray('fields'), $fields)
        );
        $grid['columns'] = 
            $this->parseColumns($servV->getArray('columns'), $fields);
    }
    protected function parseColumns($colums, $fields)
    {
        if (!$colums) {
            return array();
        }
        $_f = new Data2Html_Collection($fields);
        $pColumns = array();
        foreach ($colums as $k => $v) {
            if (is_int($k)) {  // name =  $v;
                if (substr($v, 0, 1) === '=') {
                    $name = 'd2h_i'.$k; 
                    $field = array( 
                        'value' => substr($v, 1),
                        'db' => null
                    );
                    $pColumns += $this->parseField($name, $field);
                } else {
                    $pColumns[$v] = $_f->getArray($v, array());
                }
            } else { // name =  $k;
                $pColumns[$k] = $_f->getArray($k, array());
                if (is_array($v)) {
                    $pColumns[$k] = array_merge($pColumns[$k], $v);
                }
            }
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
            foreach ($matchedFields as $name) {
                if (!isset($pColumns[$name])) {
                    if (isset($fields[$name])) {
                        $pColumns[$name] = $fields[$name];
                    } else {
                        throw new Exception(
                            "Match `\$\${{$name}}` used in a grid not exist on `fields`."
                        );
                    }
                }
            }
        } 
        return $pColumns;
    }

    protected function parseFilter($filter, $fields)
    {
        if (!$filter) {
            return array();
        }
        $pFields = array();
        $_v = new Data2Html_Collection();
        foreach ($filter as $name => $v) {
            if (!isset($fields[$name])) {
                throw new Exception(
                    "Filter field `{$name}` used in a filter not exist on `fields`."
                );
            }
            $field = $fields[$name];
            if (is_array($v)) {
                $fieldArr = $this->parseField($name, array_merge($field, $v));
                $field = $fieldArr[$name];
            } else {
                $field['check'] = $v;
            }
            $_v->set($field);
            $field['db'] = $_v->getString('db', $name);
            $nameNew = $name.'_'.$_v->getString('check', '');
            $field['name'] = $nameNew;
            $pFields[$nameNew] = $field;
        }
        return $pFields;
    }

    protected function parseFields($fields)
    {    
        $pFields = array();
        $fSorts = array();
        $matchedFields = array();
        foreach ($fields as $k => &$v) {
            $newField = $this->parseField($k, $v);
            if (isset($newField['orderBy'])) {
                $fSorts += array($newField['name'] => $newField['orderBy']);
            }
            foreach ($newField as $nv) {
                if (isset($nv['serverMatches'])) {
                    $matchedFields = array_merge(
                        $matchedFields,
                        $nv['serverMatches'][1]
                    );
                }
            }
            $pFields += $newField;
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
        foreach ($fSorts as $orderFiels) {
            foreach ($orderFiels as $k => $v) {
                $f = $v;
                if (substr($f, 0, 1) === '!') {
                   $f = substr($f, 1);
                }
                if (!isset($fields[$f])) {
                    throw new Exception(
                        "'orderBy' item `{$f}}` on `{$k}}` not exist on `fields`."
                    );
                }
            }
        }
        return $pFields;
    }

    protected function parseField($key, $field)
    {    
        $dvField = new Data2Html_Collection($field);
        $name = $dvField->getString('name', (is_int($key) ? null : $key));
        if (!$name) {
            $name = $this->createIdParse();
        }
        $dvNewDef = new Data2Html_Collection();
        $defTypes = new Data2Html_Collection($this->keywordsToDbTypes);
        $newField = array(
            'name' => $name,
            'db' => $dvField->getString('db', $name, true)
        );
        $dvNewDef->set($newField);
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
                    "Word \"{$word}\" on field \"{$name}\" is not supported."
                );
            }
            $kwGroup = $this->keywords[$word];
            if ($kwGroup === $word) {
                $newField[$word] = $vv; 
            } elseif (in_array($kwGroup, $this->keywordsSingle)) {
                $newField[$kwGroup] = ($isValue ? $vv : array($kk => $vv)); 
            } else {
                if (!isset($newField[$kwGroup])) {
                    $newField[$kwGroup] = array();
                }
                if ($isValue) {
                    array_push($newField[$kwGroup], $vv);
                } else {
                    $newField[$kwGroup][$kk] = $vv;
                }
            }
            if (!$defaultType) {
                $defaultType = $defTypes->getString($word);
            }
        }
        if (!isset($newField['type']) && $defaultType) {
            $newField['type'] = $defaultType;
        }
        $value = $dvNewDef->getString('value');
        /*
'/>\$\$([\w.]+)</'
'/\'\$\$([\w.]+)\'/'
'/"\$\$([\w.]+)"/'
         */
        if ($value) {
            $newField['db'] = null;
            $matches = null;
            preg_match_all('/\$\$\{([\w.:]+)\}/', $value, $matches);
            if (count($matches[0]) > 0) {
                $newField['serverMatches'] = $matches;
            }
        }
        return array($name => $newField);
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
            $exData = $this->exception2Data($e);
            $html = '<h3>Error: <span style="color:red">'.
                $exData['error'].'</span></h3>';
            if (isset($exData['exception'])) {
                $html .= '<div style="margin-left:1em">Exception:<pre>'.
                    $this->toJson($exData['exception']).'</pre></div>';
            }
            echo $html;
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
            $this->responseJson($this->exception2Data($e));
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
     * Exception to data structure
     */
    protected function exception2Data($exception)
    {
        $response = array();
        if ($this->debug) {
            // Error
            $response['error'] = $exception->getMessage();
            if ($exception->getCode()) {
                $response['error'] .= ' [ code: '.$exception->getCode().' ]';
            }
            // Exception to debug
            $exeptionData = array();
            $exeptionData['fileLine'] = $exception->getFile().
                ' [ line: '.$exception->getLine().' ]';
            $exeptionData['trace'] = explode("\n", $exception->getTraceAsString());
            if ($exception instanceof Data2Html_Exception) {
                $exeptionData['data'] = $exception->getData();
            }
            $response['exception'] = $exeptionData;
        } else {
            $response['error'] =
                'An unexpected error has stopped the execution on the server.';
        }
        return $response;
    }
    // -------------
    /**
     * Auto load.
     */
    protected function autoload($class)
    {
        #Not a Data2Html_% class
        error_log("autoload({$class})<br>");
        if (strpos($class, 'Data2Html_') !== 0) {
            return;
        }
        $file = str_replace('_', '/', $class).'.php';
        $phisicalFile = $this->root_path.$file;
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
        $options = 0;
        if ($this->debug && version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $options |= JSON_PRETTY_PRINT;
        }
        return json_encode($obj, $options);
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
            // Prefix `")]}',\n"` is due to security considerations, see: 
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
            if (!isset(self::$modelServices[$modelBase])) {
                self::$modelFolder = $modelFolder;
                self::$modelServices[$modelBase] =
                    self::createFromModel($model, $controllerUrl);
            }
            return self::$modelServices[$modelBase];
        } else {
            throw new Exception('The URL parameter `&model=` is not set.');
        }
    }
    private static function createFromModel($model, $controllerUrl)
    {
            $ds = DIRECTORY_SEPARATOR;
            $path = dirname($controllerUrl).$ds;
            
            $modelX = explode(':', $model);
            $file = $modelX[0].'.php';
            if (self::$modelFolder) {
                $file = self::$modelFolder.$ds.$file;
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
                    "->load({$model}): File \"{$file}\" does not exist.");
            }
    }
}
