<?php

abstract class Data2Html
{
    //protected $db_params;
    protected static $modelServices = array();
    protected static $modelFolder = null;
    protected $root_path;
    protected $id;
    protected $configOptions = array();
    public $debug = false;
    public $model;
    public $serviceUrl;
    //
    public $table = '';
    public $sql = '';
    protected $title;
    public $colDefs = array();
    public $filterDefs = array();
    public $servicesDefs = array();
    private static $idCount = 0;
    
    protected $keywords = array(
        'autoKey' => 'autoKey',
        'boolean' => 'type',
        'currency' => 'type',
        'date' => 'type',
        'db' => 'db',
        'default' => 'default',
        'email' => 'type',
        'emails' => 'type',
        'foreignKey' => 'foreignKey',
        'format' => 'format',
        'hidden' => 'display',
        'integer' => 'type',
        'label' => 'label',
        'maxLength' => 'validation',
        'name' => 'name',
        'number' => 'type',
        'required' => 'validation',
        'string' => 'type',
        'uniqueKey' => 'constraints',
        'value' => 'value',
        'visualClass' => 'visualClass',
    );
    protected $keywordsDefaultTypes = array(
        'autoKey' => 'integer',
        'email' => 'string',
        'emails' => 'string',
        'foreignKey' => 'integer',
        'maxLength' => 'string',
    );
    protected $keywordsSingle = array(
        'type', 'label', 'name', 'default', 'db', 'value'
    );
    /**
     * Class constructor, initializes basic properties.
     *
     * @param jqGridLoader $loader
     */
    public function __construct($serviceUrl = '')
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
        if ($serviceUrl) {
            $this->serviceUrl = $serviceUrl;
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
        $def = new Data2Html_Values($aux);
        $this->table = $def->getString('table');
        $this->title = $def->getString('title', $this->table);
        
        $fields = $this->parseFields($def->getArray('fields'));
        $this->filterDefs = $this->parseFilter($def->getArray('filter'), $fields);
        $services = $def->getArray('services', array());
        $dvServ = new Data2Html_Values($aux);
        foreach ($services as $k => &$s) {
            $this->parseService($s, $fields);
            $dvServ->set($s);
            $kkl = array_keys($s['columns']);
            switch ($dvServ->getString('type')) {
                case 'list':
                    $s['columns'][$kkl[0]]['name'] = 'value';
                    $s['columns'][$kkl[1]]['name'] = 'text';
                    break;
            }
        }
        $this->colDefs = $fields;
        $this->servicesDefs = $services;
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
        foreach ($colums as $k => $v) {
            if (is_int($k)) {  // name =  $v;
                if (substr($v, 0, 1) === '=') {
                    $name = 'd2h_i'.$k; 
                    $field = array( 
                        'value' => substr($v, 1),
                        'db' => null
                    );
                    $colArray += $this->parseField($name, $field);
                } else {
                    $colArray[$v] = $_f->getArray($v, array());
                }
            } else { // name =  $k;
                $colArray[$k] = $_f->getArray($k, array());
                if (is_array($v)) {
                    $colArray[$k] = array_merge($colArray[$k], $v);
                }
            }
        }
        $matchedFields = array();
        foreach ($colArray as $v) {
            if (isset($v['serverMatches'])) {
                $matchedFields = array_merge(
                    $matchedFields,
                    $v['serverMatches'][1]
                );
            }
        }
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $name) {
                if (!isset($colArray[$name])) {
                    if (isset($fields[$name])) {
                        $colArray[$name] = $fields[$name];
                    } else {
                        throw new Exception(
                            "Match `\$\${{$name}}` is used in a service but not exist on fields."
                        );
                    }
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
            if (!isset($fields[$name])) {
                throw new Exception(
                    "Filter field `{$name}` is used in a filter but not exist on fields."
                );
            }
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
        $matchedFields = array();
        foreach ($fields as $k => &$v) {
            $newField = $this->parseField($k, $v);
            foreach ($newField as $nv) {
                if (isset($nv['serverMatches'])) {
                    $matchedFields = array_merge(
                        $matchedFields,
                        $nv['serverMatches'][1]
                    );
                }
            }
            $colArray += $newField;
        }
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $v) {
                if (!isset($colArray[$v])) {
                    throw new Exception(
                        "Match \"\$\${{$v}}\" not exist on fields."
                    );
                }
            }
        } 
        return $colArray;
    }

    protected function parseField($key, $field)
    {    
        $dvField = new Data2Html_Values($field);
        $name = $dvField->getString('name', (is_int($key) ? null : $key));
        if (!$name) {
            $name = $this->createId('field');
        }
        $dvNewDef = new Data2Html_Values();
        $defTypes = new Data2Html_Values($this->keywordsDefaultTypes);
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

    protected function setFilter($filterArray)
    {
        $dvField = new Data2Html_Values();
        foreach ($filterArray as $k => &$v) {
            $dvField->set($v);
            $name = $dvField->getString('name', (is_int($k) ? null : $k));
            $check = $dvField->getString('check');
            $v['label'] = $dvField->getString('label', $name, true);
            $v['name'] = $name.'_'.$check;
            $v['db'] = $dvField->getString('db', $name, true);
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
    public function manage($fileNameConfigDb = 'd2h_config_db.ini')
    {
        $controller = new Data2Html_Controller($this, $fileNameConfigDb);
        $controller->manage();
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
    public function createService($model)
    {    
        $aux = explode('?', $this->serviceUrl);
        return self::createFromModel($model, $aux[0]);
    }
    public static function create($serviceFileName, $modelFolder = null)
    {
        if (isset($_REQUEST['model'])) {
            $model = $_REQUEST['model'];
            $modelElements = explode(':', $model);
            $modelBase = $modelElements[0];
            if (!isset(self::$modelServices[$modelBase])) {
                self::$modelFolder = $modelFolder;
                self::$modelServices[$modelBase] =
                    self::createFromModel($model, $serviceFileName);
            }
            return self::$modelServices[$modelBase];
        } else {
            throw new Exception('The URL parameter `&model=` is not set.');
        }
    }
    private static function createFromModel($model, $serviceFileName)
    {
            $_ds = DIRECTORY_SEPARATOR;
            $path = dirname($serviceFileName).$_ds;
            
            $modelX = explode(':', $model);
            $file = $modelX[0].'.php';
            if (self::$modelFolder) {
                $file = self::$modelFolder.$_ds.$file;
            }
            $phisicalFile = $path.$file;
            if (file_exists($phisicalFile)) {
                require $phisicalFile;
                $class =$modelX[0];
                $data = new $class(
                    basename($serviceFileName).'?model='.$model.'&'
                );
                return $data;
            } else {
                throw new Exception(
                    "->load({$model}): File \"{$file}\" does not exist.");
            }
    }
}
