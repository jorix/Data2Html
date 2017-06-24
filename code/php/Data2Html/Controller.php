<?php
class Data2Html_Controller
{
    protected $data;
    protected $debug = false;
    public function __construct($model)
    {
        $this->debug = Data2Html_Config::debug();
        $this->model = $data;
    }
    public function manage($request)
    {
        $dbConfig = Data2Html_Config::getSection('db');
        $db_class = 'Data2Html_Db_' . $dbConfig['db_class'];
        $db = new $db_class($dbConfig);

        $postData = array();
        $serverMethod = $_SERVER['REQUEST_METHOD'];
        switch ($serverMethod) {
            case 'GET':
                foreach($request as $key => $val) {
                    if (strpos($val, '=') !== false) {
                        parse_str(str_replace('[,]', '&', $val), $reqArr);
                        $postData[$key] = $reqArr;
                    } else {
                        $postData[$key] = $val;
                    }
                }
                break;
            case 'POST':
                //$the_request = &$_POST;
                $postData = json_decode(file_get_contents("php://input"), true);
                break;
            default:
                throw new Exception(
                    "Server method {$serverMethod} is not supported."
                );
        }
        return $this->oper($db, $request, $postData);
    }
    protected function oper($db, $request, $postData)
    {
        $model = $this->data;
        $r = new Data2Html_Collection($postData);
        $oper = $r->getString('d2h_oper', '');
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                $gridName = '';
                if (array_key_exists('model', $request)) {
                    list($modelName, $gridName) =
                        Data2Html_Handler::explodeLink($request['model']);
                }
                $linkedGrid = $model->getLinkedGrid($gridName);
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
                    $linkedGrid['columnNames'],
                    $page->getInteger('pageStart', 1),
                    $page->getInteger('pageSize', 0)
                );
            case 'insert':
                $response['new_id'] = $this->opInsert($model);
                break;

            case 'update':
                $this->opUpdate($id, $model);
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
        if ($this->model->beforeInsert($values) === false) {
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
        $this->model->afterInsert($values, $new_id);
        $this->commit();

        return $new_id;
    }
    protected function opUpdate($id, $values)
    {

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->model->beforeUpdate($values, $keyArray) === false) {
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
        $this->model->afterUpdate($values, $keyArray);
        $this->commit();

        return '1';
    }
    protected function opDelete($id)
    {
        
        if (empty($this->table)) {
            throw new jqGrid_Exception('Table is not defined');
        }

        $keyArray = $this->explodePrimaryKey($id);
        if ($this->model->beforeDelete($keyArray) === false) {
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
        $this->model->afterDelete($keyArray);
        $this->commit();

        return '1';
    }
}
