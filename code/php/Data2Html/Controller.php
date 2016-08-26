<?php
class Data2Html_Controller
{
    protected $data;
    protected $debug = false;
    public function __construct(&$data)
    {
        $this->debug = Data2Html_Config::debug();
        $this->data = $data;
    }
    public function manage($modelName = null)
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
        $dbConfig = Data2Html_Config_Db::getSection('db');
        $db_class = 'Data2Html_Db_' . $dbConfig['db_class'];
        $db = new $db_class($dbConfig);
        $postData = file_get_contents("php://input");
        $request = json_decode($postData, true);
        return $this->oper($db, $modelName, $request);
    }
    protected function oper($db, $modelName, $request)
    {
        $data = $this->data;
        $r = new Data2Html_Collection($request);
        $oper = $r->getString('d2h_oper', '');
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                $page = $r->getCollection('d2h_page', array());
                $table = $data->table;
                $gridName = Data2Html::getGridNameByModel($modelName);
                $linkedGrid = $data->getLinkedGrid($gridName);
                $sortReq = $r->getString('d2h_sort');
                $sqlObj = new Data2Html_SqlGenerator($db);
                $sql = $sqlObj->getSelect(
                    $linkedGrid,
                    $r->getArray('d2h_filter', array()),
                    $sortReq
                );
                $ra = $db->getQueryArray(
                    $sql,
                    $linkedGrid['columns'],
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
