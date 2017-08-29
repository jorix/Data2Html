<?php
class Data2Html_Controller
{
    protected $model;
    protected $db;
    protected $debug = false;
    public function __construct($model)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Controler for \"{$model->getModelName()}\"";
        
        // Data base
        $dbConfig = Data2Html_Config::getSection('db');
        $db_class = 'Data2Html_Db_' . $dbConfig['db_class'];
        $this->db = new $db_class($dbConfig);

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

        $postData = array();
        $serverMethod = $_SERVER['REQUEST_METHOD'];
        switch ($serverMethod) {
            case 'GET':
                foreach($request as $key => $val) {
                    if (is_array($val)) {
                        $postData[$key] = $val;
                    } elseif (strpos($val, '=') !== false) {
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
        return $this->oper($request, $postData);
    }
    protected function oper($request, $postData)
    {
        $model = $this->model;
        $r = new Data2Html_Collection($postData);
        $oper = $r->getString('d2h_oper', '');
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                $payerNames = Data2Html_Handler::parseRequest($request);
                if (isset($payerNames['form'])) {
                    $lkForm = $model->getForm($payerNames['form']);
                    $lkForm->createLink();
                    
                    // Make sql
                    $sqlObj = 
                        new Data2Html_Controller_SqlSelect($this->db, $lkForm);
                    $sqlObj->addFilterByKeys($r->getItem('d2h_keys'));
                    $sql = $sqlObj->getSelect();
                    
                    // Response
                    return $this->getDbData($sql, $lkForm, 1, 1);
                } elseif (isset($payerNames['grid'])) {
                    $lkGrid = $model->getGrid($payerNames['grid']);
                    $lkGrid->createLink();
                    
                    // Make sql
                    $sqlObj = new Data2Html_Controller_SqlSelect(
                        $this->db,
                        $lkGrid->getColumnsSet()
                    );
                    $sqlObj->addFilter(
                        $lkGrid->getFilter(),
                        $r->getArray('d2h_filter')
                    );
                    $sqlObj->addSort($r->getString('d2h_sort'));
                    $sql = $sqlObj->getSelect();
                    
                    // Response
                    $page = $r->getCollection('d2h_page', array());
                    return $this->getDbData(
                        $sql,
                        $lkGrid->getColumnsSet(),
                        $page->getInteger('pageStart', 1),
                        $page->getInteger('pageSize', 0)
                    );
                }
            case 'insert':
                $response['new_id'] = $this->opInsert($model);
                break;

            case 'update':
                $data = $postData['d2h_data'];
                $keys = $data['[keys]'];
                unset($data['[keys]']);
                $this->opUpdate($keys, $data);
                break;

            case 'delete':
                $this->opDelete($id);
                break;

            default:
                throw new Exception("Oper '{$oper}' is not defined");
                break;
        }
        $response['success'] = 1;
        return $response;
    }
    
    /**
     * Execute a query and return the array result.
     */
    protected function getDbData($query, $lkSet, $pageStart = 1, $pageSize = 0)
    {
        try {
            $pageSize = intval($pageSize);
            $pageStart = intval($pageStart);
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        };
        $itemDx = new Data2Html_Collection();
        $lkColumns = $lkSet->getLinkedItems();
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
        $keyNames = array_keys($lkSet->getLinkedKeys());
        $rows = array();
        $result = $this->db->queryPage($query, $pageStart, $pageSize);
        while ($dbRow = $this->db->fetch($result)) {
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
            $dataKeys = array();
            foreach ($keyNames as $k) {
                $dataKeys[] = $dbRow[$k];
            }
            $resRow['[keys]'] = $dataKeys;
            $rows[] = $resRow;
        }
        $response = array();
        if ($this->debug) {
            $response['debug'] = array(
                'sql' => explode("\n", $query),
                'keys' => $lkSet->getLinkedKeys(),
                'values' => $values,
                'teplateItems' => $teplateItems
            );
        }
        $response += array(
            'pageStart' => $pageStart,
            'pageSize' => $pageSize,
            'cols' => array_keys($resTypes)
        );
        if (true) {
            $response['rows'] = $rows;
        } else {
            $response += array(
                'dataCols' => array_keys($resTypes),
                'rowsAsArray' => 'TODO'
            );
        }
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
        $this->db->startTransaction();
        try {
            $new_id = $this->db->insert($this->model->getTableName(), $values, true);
        } catch (Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->model->afterInsert($values, $new_id);
        $this->db->commit();

        return $new_id;
    }
    protected function opUpdate($keys, $values)
    {

        $keyArray = explode(',', $keys);
        if ($this->model->beforeUpdate($values, $keyArray) === false) {
            exit;
        }
        // Transaction
        $this->db->startTransaction();
        try {
            $this->db->update($this->model->getTableName(), $values, $keyArray);
        } catch (Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->model->afterUpdate($values, $keyArray);
        $this->db->commit();

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
        $this->db->startTransaction();
        try {
            $this->db->delete(
                $this->model->getTableName(),
                $this->whereSql($keyArray)
            );
        } catch (Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 '.$e->getMessage());
            die($e->getMessage());
        }
        $this->model->afterDelete($keyArray);
        $this->db->commit();

        return '1';
    }
}
