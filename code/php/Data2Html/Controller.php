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
    public function manage($request)
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
        return $this->oper($db, $request, json_decode($postData, true));
    }
    protected function oper($db, $request, $postData)
    {
        $data = $this->data;
        $r = new Data2Html_Collection($postData);
        $oper = $r->getString('d2h_oper', '');
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                $gridName = '';
                if (array_key_exists('model', $request)) {
                    list($modelName, $gridName) =
                        Data2Html_Model::explodeLink($request['model']);
                }
                $linkedGrid = $data->getLinkedGrid($gridName);
                $sqlObj = new Data2Html_SqlGenerator($db);
                $sql = $sqlObj->getSelect(
                    $linkedGrid,
                    $r->getArray('d2h_filter', array()),
                    $r->getString('d2h_sort')
                );
                $page = $r->getCollection('d2h_page', array());
                return $db->getQueryArray(
                    $sql,
                    $linkedGrid['columns'],
                    $page->getInteger('pageStart', 1),
                    $page->getInteger('pageSize', 0)
                );
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
