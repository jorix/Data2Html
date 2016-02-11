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
        try {
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
            $this->oper($db, $model, $request);
        } catch(Exception $e) {
            // Message to user
            $response = array();
            if ($e instanceof Data2Html_Exception_User) {
                header('HTTP/1.1 409 Conflict');
                $response['message'] = $e->getUserMsg();
            } else {
                header('HTTP/1.1 500 Error');
            }
            // Error message
            $errData = array();
            if ($this->debug) {
                $errData['message'] = $e->getMessage();
                $errData['code'] = $e->getCode();
                if ($e instanceof Data2Html_Exception) {
                    $errData['dh2_data'] = $e->getData();
                }
                $errData['trace'] = explode("\n", $e->getTraceAsString());
            } else {
                $errData['message'] = 'No further details of the error.';
            }
            $response['error'] = $errData;
            $this->responseJson($response);
        }
    }
    protected function oper($db, $model, $request)
    {
        $data = $this->data;
        $r = new Data2Html_Values($request);
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
    protected function responseJson($obj)
    {
        if ($this->debug && isset($_REQUEST['debug'])) {
            echo "<pre>\n".$this->data->toJson($obj)."\n</pre>\n";
        } else {
            header('Content-type: application/responseJson; charset=utf-8;');
            // Prefix `")]}',\n"` is due to security considerations, see: 
            //    * https://docs.angularjs.org/api/ng/service/$http
            echo ")]}',\n".$this->data->toJson($obj);
        }
    }
}
