<?php

abstract class Data2Html
{
    //protected $db_params;
    protected $root_path;
    protected $id;
    protected $configOptions = array();
    protected $debug = false;
    //
    protected $table;
    protected $title;
    protected $colDefs = array();
    protected $filterDefs = array();

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
        $this->id = 'd2h_'.get_class($this);
        
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
    }
    public function getRoot()
    {
        return $this->root_path;
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
     * Abstract function for setting fields properties.
     *
     * @abstract
     */
    abstract protected function init();

    /**
     * MAIN ACTION (2): Perform operation to change data is any way.
     *
     * @param $oper - operation name
     */
    public function run($fileNameConfigDb)
    {
        // Open db
        $c = parse_ini_file($fileNameConfigDb, true);
        $dbConfig = $c['db'];
        $db_class = 'Data2Html_Db_'.$dbConfig['db_class'];
        $db = new $db_class(
            $dbConfig,
            array(
                'debug' => $this->debug
            )
        );
        
        /*
        $the_request = array_merge($_GET, $_POST);
        print_r($_POST);
        $serverMethod = $_SERVER['REQUEST_METHOD'];
        switch ($serverMethod) {
            case 'GET':
                $the_request = &$_GET; break;
            case 'POST':
                $the_request = &$_POST; break;
            default:
                throw new Exception(
                    "Server method {$serverMethod} is not supported.");
        }
        */
        $postdata = file_get_contents("php://input");
        $request = json_decode($postdata, true);
        // print_r($request);
        $this->oper($db, $request);
    }
    protected function oper($db, $request)
    {
        $r = new Data2Html_Values($request);
        $oper = $r->getString('d2h_oper', 'list');
        switch ($oper) {
            case '':
            case 'list':
                $page = $r->getArrayValues('d2h_page', array());
                $sql = new Data2Html_Sql($db);
                $this->responseJson(
                    $db->getQueryArray(
                        $sql->getSelect(
                            $this->table,
                            $this->colDefs,
                            $this->filterDefs,
                            $r->getArray('d2h_filter', array())
                        ),
                        $this->colDefs,
                        $page->getInteger('pageStart', 1),
                        $page->getInteger('pageSize', 0)
                    )
                ); 
                return;

            case 'add':
                $data = array_intersect_key($this->input, $this->cols);
                $data = $this->operData($data);
                $id = $this->opAdd($data);
                if (empty($this->primary_key_auto_increment)) {
                    $id = $this->implodePrimaryKey($data);
                }
                $this->response['new_id'] = $id;
                $this->operAfterAddEdit($id);
                break;

            case 'edit':
                $data = array_intersect_key($this->input, $this->cols);
                $data = $this->operData($data);
                $this->opEdit($id, $data);
                $this->operAfterAddEdit($id);
                break;

            case 'del':
                $this->opDel($id);
                break;

            default:
                throw new Exception("Oper {$oper} is not defined");
                break;
        }
        $this->response = array_merge(array('success' => 1), $this->response);
        $this->responseJson($this->response);
    }

    /**
     */
    protected function setCols($colArray)
    {
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
    
    //----------------
    // HELPER PART
    //----------------

    /**
     * Send to browser a JSON from a php object.
     *
     * @param  $obj object to send
     */
    protected function responseJson($obj)
    {
        header('Content-type: application/responseJson; charset=utf-8;');
 
        $options = 0;
        if ($this->debug && version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $options |= JSON_PRETTY_PRINT;
        }
        // Prefix `")]}',\n"` is due to security considerations, see: 
        //    * https://docs.angularjs.org/api/ng/service/$http
        echo ")]}',\n".json_encode($obj, $options);
    }

    //----------------
    // OPERATIONS PART
    //----------------

    /**
     * (Oper) Insert.
     *
     * Please note: this is the only "Oper" function, which must return new row id
     *
     * @param array $ins - form data
     *
     * @return int - new_id
     */
    protected function opAdd($values)
    {
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined');
        }
        if ($this->beforeInsert($values) === false) {
            exit;
        }
        // Transaction
        $this->startTransaction();
        try {
            $new_id = $this->db->insert($this->table, $values, true);
        } catch (Exception $e) {
            $this->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->afterInsert($values, $new_id);
        $this->commit();

        return $new_id;
    }
    protected function beforeInsert($values)
    {
        return true;
    }
    protected function afterInsert($values, $keyArray)
    {
    }

    /**
     * (Oper) Update.
     *
     * @param int   $id  - id to update
     * @param array $upd - form data
     */
    protected function opEdit($id, $values)
    {
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined', $this->table);
        }

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->beforeUpdate($values, $keyArray) === false) {
            exit;
        }
        // Transaction
        $this->startTransaction();
        try {
            $this->db->update($this->table, $values, $keyArray);
        } catch (Exception $e) {
            $this->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->afterUpdate($values, $keyArray);
        $this->commit();

        return '1';
    }
    protected function beforeUpdate($values, $keyArray)
    {
        return true;
    }
    protected function afterUpdate($values, $keyArray)
    {
    }

    /**
     * (Oper) Delete.
     *
     * @param int|string $id - one or multiple id's to delete
     */
    protected function opDel($id)
    {
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined');
        }

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->beforeDelete($keyArray) === false) {
            exit;
        }
        // Transaction
        $this->startTransaction();
        try {
            $this->db->delete(
                $this->table,
                $this->whereSql($keyArray)
            );
        } catch (Exception $e) {
            $this->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->afterDelete($keyArray);
        $this->commit();

        return '1';
    }
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
}
