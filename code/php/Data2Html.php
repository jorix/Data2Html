<?php

abstract class Data2Html
{
    //protected $db_params;
    protected $root_path;
    protected $id;
    protected $configOptions = array();
    public $debug = false;
    public $model;
    public $controller;
    //
    public $table = '';
    public $sql = '';
    protected $title;
    public $colDefs = array();
    public $filterDefs = array();
    public $servicesDefs = array();
    private static $idCount = 0;
    
    protected $keywords = array(
        'autoKey' => 'constraints',
        'boolean' => 'type',
        'currency' => 'type',
        'date' => 'type',
        'db' => 'db',
        'default' => 'default',
        'email' => 'type',
        'emails' => 'type',
        'foreignKey' => 'constraints',
        'format' => 'format',
        'integer' => 'type',
        'label' => 'label',
        'maxLength' => 'validation',
        'name' => 'name',
        'number' => 'type',
        'required' => 'validation',
        'string' => 'type',
        'uniqueKey' => 'constraints',
    );
    protected $keywordsDefaultTypes = array(
        'autoKey' => 'integer',
        'email' => 'string',
        'emails' => 'string',
        'foreignKey' => 'integer',
        'maxLength' => 'string',
    );
    protected $keywordsSingle = array(
        'type', 'label', 'name', 'default', 'db'
    );
    /**
     * Class constructor, initializes basic properties.
     *
     * @param jqGridLoader $loader
     */
    public function __construct($controllerURL = '')
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }
        
        // Base
        //----------------
        $this->root_path = dirname(__FILE__).DIRECTORY_SEPARATOR;
        $this->id = $this->createId(get_class($this));
        
        // Register autoload
        //------------------
        spl_autoload_register(array($this, 'autoload'));

        // Load config if exists
        //------------------
        if (file_exists('d2h_config.ini')) {
            $this->configOptions = parse_ini_file('d2h_config.ini', true);
        }
        $aux = new Data2Html_Values($this->configOptions);
        $config = $aux->getArrayValues('config');
        $this->debug = $config->getBoolean('debug');
        
        // Init
        //----------------
        if ($controllerURL) {
            $this->controller = $controllerURL;
        }
        $this->init();
        
        if ($this->table !== '' && $this->sql !== '') {
            throw new Exception(
                "Can't be simultaneously set `\$this->table` and `\$this->sql`."
            );
        }
    }
    public function getRoot()
    {
        return $this->root_path;
    }
    public function createId($name) {
        self::$idCount++;
        return 'd2h_'.self::$idCount.'_'.$name;
    }
    public function getId()
    {
        return $this->id;
    }
    public function getColDefs()
    {
        return $this->colDefs;
    }
    public function getFilterDefs()
    {
        return $this->filterDefs;
    }
    public function getTitle()
    {
        return $this->title;
    }
    public function getLocalJs()
    {
        return $this->toJson(array(
                'controller' => $this->controller
        ));
    }
    /**
     * Abstract function for setting fields properties.
     *
     * @abstract
     */
    abstract protected function init();
    /**
     */
    public function setModel($model)
    {
        $matches = null;
        preg_match_all(
            '/\$\$\{([\w.:]+)\}/',
            '$subject',
            $matches
        );
        $matches = $matches[1];
    }
    protected function parse()
    {
        $aux = $this->definitions();
        $def = new Data2Html_Values($aux);
        $this->table = $def->getString('table');
        $this->title = $def->getString('title', $this->table);
        
        $fields = $this->parseFields($def->getArray('fields'));
        $this->colDefs = $fields;
        $this->filterDefs = $this->parseFilter($def->getArray('filter'), $fields);
        
        $services = $def->getArray('services', array());
        $this->servicesDefs = array();
        foreach ($services as $k => &$s) {
            $this->parseService($s, $fields);
        }
    }

    protected function parseService(&$service, $fields)
    {
        $servV = new Data2Html_Values($service);
        $service['filter'] = 
            $this->parseFilter($servV->getArray('filter'), $fields);
        $service['columns'] = 
            $this->parseColumns($servV->getArray('columns'), $fields);
    }

    protected function parseColumns($colums, $fields)
    {
        if (!$colums) {
            return array();
        }
        $_f = new Data2Html_Values($fields);
        $colArray = array();
        $_v = new Data2Html_Values();
        foreach ($colums as $k => $v) {
            if (is_int($k)) {  // name =  $v;
                $colArray[$v] = $_f->getArray($v, array());
            } else { // name =  $k;
                $colArray[$k] = $_f->getArray($k, array());
                if (is_array($v)) {
                    $colArray[$k] = array_merge($colArray[$k], $v);
                }
            }
        }
        return $colArray;
    }

    protected function parseFilter($filter, $fields)
    {
        if (!$filter) {
            return array();
        }
        $colArray = array();
        $_v = new Data2Html_Values();
        foreach ($filter as $name => $check) {
            $field = $fields[$name];
            $_v->set($field);
            $nameNew = $name.'_'.$check;
            $field['check'] = $check;
            $field['name'] = $nameNew;
            $field['db'] = $_v->getString('db', $name, true);
            $colArray[$nameNew] = $field;
        }
        return $colArray;
    }

    protected function parseFields($fields)
    {    
        $colArray = array();
        $_v = new Data2Html_Values();
        $defTypes = new Data2Html_Values($this->keywordsDefaultTypes);
        foreach ($fields as $k => &$v) {
            $_v->set($v);
            $name = $_v->getString('name', (is_int($k) ? null : $k));
            $def = array('name' => $name);
            $defaultType = null;
            foreach ($v as $kk => $vv) {
                $isValue = is_int($kk);
                if ($isValue) {
                    $word = $vv;
                } else {
                    $word = $kk;
                }
                if (!isset($this->keywords[$word])) {
                    throw new Exception(
                        "paseFiels(): Word \"{$word}\" on field \"{$name}\" is not supported."
                    );
                }
                $kwGroup = $this->keywords[$word];
                if ($kwGroup === $word) {
                    $def[$word] = $vv; 
                } elseif (in_array($kwGroup, $this->keywordsSingle)) {
                    $def[$kwGroup] = ($isValue ? $vv : array($kk => $vv)); 
                } else {
                    if (!isset($def[$kwGroup])) {
                        $def[$kwGroup] = array();
                    }
                    if ($isValue) {
                        array_push($def[$kwGroup], $vv);
                    } else {
                        $def[$kwGroup][$kk] = $vv;
                    }
                }
                if (!$defaultType) {
                    $defaultType = $defTypes->getString($word);
                }
            }
            if (!isset($def['type']) && $defaultType) {
                $def['type'] = $defaultType;
            }
            $colArray[$name] = $def;
        }
        return $colArray;
    }
  
    protected function setCols($colArray)
    {
        $matches = null;
        preg_match_all(
            '/\$\$\{([\w.:]+)\}/',
            '$subject',
            $matches
        );
        $matches = $matches[1];
        $this->colDefs = $colArray;
    }
    protected function setFilter($filterArray)
    {
        $_v = new Data2Html_Values();
        foreach ($filterArray as $k => &$v) {
            $_v->set($v);
            $name = $_v->getString('name', (is_int($k) ? null : $k));
            $check = $_v->getString('check');
            $v['label'] = $_v->getString('label', $name, true);
            $v['name'] = $name.'_'.$check;
            $v['db'] = $_v->getString('db', $name, true);
        }
        $this->filterDefs = $filterArray;
    }
    
    //-----------------
    // Operation executor and events
    //-----------------
    /**
     * Operation executor
     */    
    public function render($template)
    {
        $render = new Data2Html_Render($template);
        echo $render->render($this);
    }
    public function run($fileNameConfigDb = 'd2h_config_db.ini')
    {
        $controller = new Data2Html_Controller($this, $fileNameConfigDb);
        $controller->run();
    }

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
    {
    }

    //----------------
    // Utils
    //----------------
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
    /**
     * PHP object to a JSON text
     */
    public function toJson($obj)
    {
        $options = 0;
        if ($this->debug && version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $options |= JSON_PRETTY_PRINT;
        }
        return json_encode($obj, $options);
    }
    
    public static function create($loaderFileName, $prefix = '', $folder = null)
    {
        if (isset($_REQUEST['model'])) {
            $_ds = DIRECTORY_SEPARATOR;
            $path = dirname($loaderFileName).$_ds;
            
            $model = $_REQUEST['model'];
            $modelX = explode(':', $model);
            $file = $modelX[0].'.php';
            if ($folder) {
                $file = $folder.$_ds.$file;
            }
            $phisicalFile = $path.$file;
            if (file_exists($phisicalFile)) {
                require $phisicalFile;
                $class = $prefix.$modelX[0];
                $data = new $class(
                    basename($loaderFileName).'?model='.$model.'&'
                );
                return $data;
            } else {
                throw new Exception(
                    "->load({$model}): File \"{$file}\" does not exist.");
            }
        } else {
            throw new Exception('The URL paramenter `&model=` is not set.');
        }
    }
}
