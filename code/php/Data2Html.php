<?php

abstract class Data2Html
{
    //protected $db_params;
    protected $root_path;
    protected $id;
    protected $configOptions = array();
    public $debug = false;
    //
    public $table = '';
    public $sql = '';
    protected $title;
    public $colDefs = array();
    public $filterDefs = array();
    private static $idCount = 0;

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
        $this->init();
        
        if ($this->table !== '' && $this->sql !== '') {
            throw new Exception("At `init()` can't be simultaneously set `\$this->table` and `\$this->sql`.");
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
    public function run($fileNameConfigDb)
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
        $path = str_replace('_', '/', $class).'.php';
        $phisicalPath = $this->root_path.$path;
        #Do not interfere with other autoloads
        if (file_exists($phisicalPath)) {
            require $phisicalPath;
        } else {
            throw new Exception(
                "->autoload({$class}): File \"{$path}\" does not exist");
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
}
