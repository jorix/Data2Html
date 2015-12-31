<?php

abstract class Data2Html
{
    //protected $db_params;
    protected $root_path;
    protected $id;
    //
    protected $db;
    protected $table;
    protected $title;
    protected $colsDefs = array();
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
    public function getDefs()
    {
        return array(
            'colsDefs' => $this->colsDefs
        );
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
    public function run($dbParams)
    {
        // Open db		
        $db_driver = $dbParams;
        $db_class = 'Data2Html_Db_'.$db_driver['db_class'];
        $this->db = new $db_class($db_driver);
        
        $serverMethod = $_SERVER['REQUEST_METHOD'];
        switch ($serverMethod) {
        case 'GET': $the_request = &$_GET; break;
        case 'POST': $the_request = &$_POST; break;
        default:
            throw new Exception("Server method {$serverMethod} is not supported.");
        }
        $this->oper($the_request);
    }
    protected function oper($request)
    {
        $r = new Data2Html_Values($request);
        $oper = $r->getString('oper', 'list');
        switch ($oper) {
            case '':
            case 'list':
                $pageNumber = $r->getInteger('pageNumber', 1);
                $pageSize = $r->getInteger('pageSize', 12);
                $orderBy = $r->getString('orderBy');
                $query = "select * from {$this->table}";
                $where = '';
                foreach ($this->filterDefs as $v) {
                    $where .= " and {$v['name']} = {$v['value']}";
                }
                if ($where !== '') {
                    $query .= ' where '.substr($where, 5);
                }
                if ($orderBy) {
                    $query .= " order by {$orderBy}";
                }
                $this->responseJson(
                    $this->db->getQueryArray($query, $this->colsDefs,
                        $pageNumber, $pageSize)
                );

                return;
            case 'add':
                $data = array_intersect_key($this->input, $this->cols);
                $data = $this->operData($data);

                $id = $this->opAdd($data);

                #Not auto increment -> build new_id from data
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
                $callback = array($this, jqGrid_Utils::uscore2camel('op', $oper));

                if (is_callable($callback)) {
                    call_user_func($callback);
                } else {
                    throw new jqGrid_Exception("Oper $oper is not defined");
                }
                break;
        }

        $this->response = array_merge(array('success' => 1), $this->response);

        $this->operComplete($oper);

        //----------------
        // Output result
        //----------------
        $this->responseJson($this->response);
    }

    /**
     * MAIN ACTION (3): Render grid.
     *
     * $jq_loader->render('jq_example');
     *
     * @param array $render_data
     *
     * @return string
     */
    protected function setCols($colArray)
    {
        $this->colsDefs = $colArray;
    }
    protected function setFilter($filterArray)
    {
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
        // echo '<pre>'.Data2Html_Utils::jsonEncode($obj).'</pre>'; return;
        header('Content-type: application/responseJson; charset=utf-8;');
        echo json_encode($obj);
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
