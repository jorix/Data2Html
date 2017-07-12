<?php
class Data2Html_Controller
{
    protected $model;
    protected $debug = false;
    public function __construct($model)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Controler for \"{$model->getModelName()}\"";
        
        $this->model = $model;
    }

    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = array(
            );
        }
        Data2Html_Utils::dump($this->culprit, $subject);
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
        $model = $this->model;
        $r = new Data2Html_Collection($postData);
        $oper = $r->getString('d2h_oper', '');
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                $payerNames = Data2Html_Handler::parseRequest($request);
                $gridName = $payerNames['grid'];
                $linkedGrid = $model->getGrid($gridName)->getLink();
                
                $sqlObj = new Data2Html_SqlGenerator($db);
                $sql = $sqlObj->getSelect(
                    $linkedGrid,
                    $r->getArray('d2h_filter', array()),
                    $r->getString('d2h_sort')
                );
                $page = $r->getCollection('d2h_page', array());
                return $this->getDbData(
                    $db,
                    $sql,
                    $linkedGrid,
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
    
    /**
     * Execute a query and return the array result.
     */
    public function getDbData($db, $query, $lkGrid, $pageStart = 1, $pageSize = 0)
    {
        try {
            $pageSize = intval($pageSize);
            $pageStart = intval($pageStart);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        };
        $itemDx = new Data2Html_Collection();
        $lkColumns = $lkGrid->get('columns');
        $resTypes = array();
        $dbTypes = array();
        $values = array();
        $teplateItems = array();
        $addValue = function($depth, $keyItem, $lkItem)
                    use(&$addValue, $lkColumns, &$values, &$teplateItems) {
            if ($depth > 10) {
                throw new Data2Html_Exception(
                    "{$this->culprit} getDbData::\$addValue(): Possible circular reference in \"{$keyItem}\" teplateItems.",
                    $lkItem
                );
            }
            $matches = Data2Html_Value::getItem($lkItem, 'teplateItems');
            if ($matches) {
                foreach ($matches as $v) {
                    $ref = $v['ref'];
                    $matchItem = $lkColumns[$ref];
                    if (array_key_exists('value', $matchItem) && 
                        !array_key_exists($ref, $values)
                    ) {
                        $addValue($depth+1, $ref, $matchItem);
                    }
                }
                $teplateItems[$keyItem] = $matches;
            }
            $values[$keyItem] = $lkItem['value'];
        };
        foreach ($lkColumns as $k => $v) {
            $itemDx->set($v);
            $type = $itemDx->getString('type', 'string');
            $types[$k] = $type;
            if (!$itemDx->getString('virtual')) {
                $resTypes[$k] = $type;
            }
            if ($itemDx->getString('db')) {
                $dbTypes[$k] = $type;
            }
            if (array_key_exists('value', $v)) {
                $addValue(0, $k, $v);
            }
        }
        // Read rs
        $rows = array();
        $result = $db->queryPage($query, $pageStart, $pageSize);
        while ($dbRow = $db->fetch($result)) {
            foreach ($dbRow as $k => &$v) {
                switch ($dbTypes[$k]) {
                case 'integer':
                case 'number':
                case 'boolean':
                case 'currency':
                    if (!is_null($v)) {
                        $v = $v + 0; // convert to number
                    }
                    break;
                case 'date';
                    if (!is_null($v)) {
                        $date = DateTime::createFromFormat('Y-m-d H:i:s', $v);
                        // convert to a string as "2015-04-15T08:39:19+01:00"
                        $v = date('c', $date->getTimestamp());
                    }
                    unset($date);
                    break;
                }
            }
            unset($v);
            
            $valueRow = array();
            foreach ($values as $k => $v) {
                $valueRow[$k] = $values[$k];
                if (array_key_exists($k, $teplateItems)) {
                    $allIsNull = true;
                    foreach ($teplateItems[$k] as $kk => $vv) {
                        $ref = $vv['ref'];
                        if (array_key_exists($ref, $dbRow)) {
                            $value = $dbRow[$ref];
                        } elseif (array_key_exists($ref, $valueRow)) {
                            $value = $valueRow[$ref];
                        } else {
                            throw new Data2Html_Exception(
                                "{$this->culprit} getDbData(): Reference \"{$ref}\" is neither db field nor value.",
                                array('dbRow' => $dbRow, 'valueRow' => $valueRow)
                            );
                        }
                        $valueRow[$k] = str_replace($kk, $value, $valueRow[$k]);
                        $allIsNull = $allIsNull && is_null($value);
                    }
                    if ($allIsNull) {
                        $valueRow[$k] = null;
                    }
                }
            }
            $resRow = array();
            foreach ($resTypes as $k => $v) {
                if (array_key_exists($k, $dbTypes)) {
                    $resRow[$k] = $dbRow[$k];
                } elseif (array_key_exists($k, $valueRow)) {
                    $resRow[$k] = $valueRow[$k];
                }
            }
            $rows[] = $resRow;
        }
        $response = array();
        if ($this->debug) {
            $response += array(
                'sql' => explode("\n", $query),
                'values' => $values,
                'teplateItems' => $teplateItems
            );
        }
        $response += array(
            'pageStart' => $pageStart,
            'pageSize' => $pageSize,
            'dataTypes' => $resTypes,
            'rows' => $rows
        );
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
