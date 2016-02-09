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
        // Open db
        $c = parse_ini_file($this->fileNameConfigDb, true);
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
        $data = $this->data;
        $r = new Data2Html_Values($request);
        $oper = $r->getString('d2h_oper', '');
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                $page = $r->getArrayValues('d2h_page', array());
                $sql = $data->sql;
                if (!$sql) {
                    $sqlObj = new Data2Html_Sql($db);
                    $sql = $sqlObj->getSelect(
                        $data->table,
                        $data->colDefs,
                        $data->filterDefs,
                        $r->getArray('d2h_filter', array())
                    );
                }
                $ra = $db->getQueryArray(
                    $sql,
                    $data->colDefs,
                    $page->getInteger('pageStart', 1),
                    $page->getInteger('pageSize', 0)
                );
                if ($oper === '') {
                    echo $this->toJsonDocs($ra);
                } else {    
                    $this->responseJson($ra); 
                }
                return;
            case 'list':
                $sql = $data->sql;
                if (!$sql) {
                    $sqlObj = new Data2Html_Sql($db);
                    $sql = $sqlObj->getSelect(
                        $data->table,
                        $data->colDefs,
                        $data->filterDefs,
                        $r->getArray('d2h_filter', array())
                    );
                }
                $ra = $db->getQueryArray(
                    $sql,
                    $data->colDefs
                );
                $this->responseJson($ra);
                return;

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
        $this->responseJson($response);
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
    /**
     * Send JSON from a php object.
     *
     * @param  $obj object to send
     */
    protected function toJsonDocs($obj)
    {
        return "<pre>\n".$this->data->toJson($obj)."\n</pre>\n";
    }
    protected function responseJson($obj)
    {
        header('Content-type: application/responseJson; charset=utf-8;');
        // Prefix `")]}',\n"` is due to security considerations, see: 
        //    * https://docs.angularjs.org/api/ng/service/$http
        echo ")]}',\n".$this->data->toJson($obj);
    }
}
