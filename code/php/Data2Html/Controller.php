<?php
class Data2Html_Controller
{
    protected $fileNameConfigDb;
    protected $data;
    protected $debug = false;
    public function __construct(&$data, $fileNameConfigDb)
    {
        $this->data = $data;
        $this->fileNameConfigDb = $fileNameConfigDb;
        $this->debug = $data->debug;
    }
    public function manage()
    {
        /*
        $the_request = array_merge($_GET, $_POST);
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

        
        $this->data->parse();
        $c = parse_ini_file($this->fileNameConfigDb, true);
        $dbConfig = $c['db'];
        $db_class = 'Data2Html_Db_'.$dbConfig['db_class'];
        $db = new $db_class(
            $dbConfig,
            array(
                'debug' => $this->debug
            )
        );
        $postData = file_get_contents("php://input");
        $request = json_decode($postData, true);
        $model = '';
        if (isset($_REQUEST['model'])) {
            $model = $_REQUEST['model'];
        }
        return $this->oper($db, $model, $request);
    }
    protected function oper($db, $model, $request)
    {
        $data = $this->data;
        $r = new Data2Html_Collection($request);
        $oper = $r->getString('d2h_oper', '');
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                $page = $r->getArrayValues('d2h_page', array());
                $table = $data->table;
                $sql = $data->sql;
                if ($model && strpos($model, ':') !== false ) {
                    $aux = explode(':', $model);
                    $serviceDef = $data->servicesDefs[$aux[1]];
                    $colDefs = $serviceDef['columns'];
                    $filterDefs = null;
                } else {
                    $colDefs = $data->colDefs;
                    $filterDefs = $data->filterDefs;
                }
                if (!$sql) {
                    $sqlObj = new Data2Html_Sql($db);
                    $sql = $sqlObj->getSelect(
                        $table,
                        $colDefs,
                        $filterDefs,
                        $r->getArray('d2h_filter', array())
                    );
                }
                $ra = $db->getQueryArray(
                    $sql,
                    $colDefs,
                    $page->getInteger('pageStart', 1),
                    $page->getInteger('pageSize', 0)
                );
                return $ra;
            case 'insert':
                $response['new_id'] = $this->opInsert($data);
                break;

            case 'update':
                $this->opUpdate($id, $data);
                break;

            case 'delete':
                $this->opDelete($id);
                break;

            default:
                throw new Exception("Oper {$oper} is not defined");
                break;
        }
        $response['success'] = 1;
        return $response;
    }
    protected function opAdd($values)
    {
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined');
        }
        if ($this->data->beforeInsert($values) === false) {
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
        $this->data->afterInsert($values, $new_id);
        $this->commit();

        return $new_id;
    }
    protected function opUpdate($id, $values)
    {

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->data->beforeUpdate($values, $keyArray) === false) {
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
        $this->data->afterUpdate($values, $keyArray);
        $this->commit();

        return '1';
    }
    protected function opDelete($id)
    {
        
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined');
        }

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->data->beforeDelete($keyArray) === false) {
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
        $this->data->afterDelete($keyArray);
        $this->commit();

        return '1';
    }
}
