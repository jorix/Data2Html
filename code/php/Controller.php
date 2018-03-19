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
                        parse_str(str_replace('{and}', '&', $val), $reqArr);
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
        $playerNames = Data2Html_Handler::parseRequest($request);
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                if (isset($playerNames['form'])) {
                    $lkForm = $model->getForm($playerNames['form']);
                    $lkForm->createLink();
                    return $this->opReadForm($lkForm, $r->getItem('d2h_keys'));
                } elseif (isset($playerNames['grid'])) {
                    $lkGrid = $model->getGrid($playerNames['grid']);
                    $lkGrid->createLink();
                    
                    // Prepare sql
                    $sqlObj = new Data2Html_Controller_SqlSelect(
                        $this->db,
                        $lkGrid->getColumnsSet()
                    );
                    $sqlObj->addFilter(
                        $lkGrid->getFilter(),
                        $r->getArray('d2h_filter')
                    );
                    $sqlObj->addSort($r->getString('d2h_sort'));
                    
                    // Response
                    $page = $r->getCollection('d2h_page', array());
                    return $this->opRead(
                        $sqlObj->getSelect(),
                        $lkGrid->getColumnsSet(),
                        $page->getInteger('pageStart', 1),
                        $page->getInteger('pageSize', 0)
                    );
                }
            case 'insert':
                $lkForm = $model->getForm($playerNames['form']);
                $values = $postData['d2h_data'];
                $newId = $this->opInsert($lkForm, $values);
                
                // Get new keys
                $lkForm->createLink();
                $lkItems = $lkForm->getLinkedItems();
                $keyNames = $lkForm->getLinkedKeys();
                $keys = [];
                foreach($keyNames as $k => $v) {
                    if (Data2Html_Value::getItem($lkItems, [$k, 'key']) === 'autoKey') {
                        $keys[] = $newId + 0;
                    } else {
                        $keys[] = $values[$k];
                    }
                }
                
                // Response record
                $response['keys'] = $keys;
                break;

            case 'update':
                $data = $postData['d2h_data'];
                $keys = $data['[keys]'];
                unset($data['[keys]']);
                $this->opUpdate($model->getForm($playerNames['form']), $data, $keys);
                break;

            case 'delete':
                $data = $postData['d2h_data'];
                $keys = $data['[keys]'];
                unset($data['[keys]']);
                $this->opDelete($model->getForm($playerNames['form']), $data, $keys);
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
    protected function opReadForm($lkForm, $keys)
    {
        // Prepare sql
        $sqlObj = new Data2Html_Controller_SqlSelect($this->db, $lkForm);
        $sqlObj->addFilterByKeys($keys);
        
        // Response
        return $this->opRead($sqlObj->getSelect(), $lkForm, 1, 1);
    }
    
    
    /**
     * Execute a query and return the array result.
     */
    protected function opRead($query, $lkSet, $pageStart = 1, $pageSize = 0)
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
                $v = $this->db->toValue($v, $dbTypes[$k]);
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
        $this->db->closeQuery($result);
        
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
            'pageSize' => $pageSize
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

    protected function opInsert($set, &$values)
    {
        $newId = null;
        
        // Transaction
        $this->db->startTransaction();
        try {
            $result = $set->dbInsert($this->db, $values, $newId);
        } catch (Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 Database error');
            die(Data2Html_Value::toJson(
                Data2Html_Exception::toArray($e, $this->debug)
            ));
        }
        if ($result === false) {
            $this->db->rollback();
            return false;
        }
        $this->db->commit();

        return $newId;
    }
    
    protected function opUpdate($set, &$values, $keys)
    {
        // Transaction
        $this->db->startTransaction();
        try {
            $result = $set->dbUpdate($this->db, $values, $keys);
        } catch (Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 Database error');
            die(Data2Html_Value::toJson(
                Data2Html_Exception::toArray($e, $this->debug)
            ));
        }
        if ($result === false) {
            $this->db->rollback();
            return false;
        }
        $this->db->commit();

        return $result;
    }
    
    protected function opDelete($set, &$values, $keys)
    {
        $sqlObj = new Data2Html_Controller_SqlEdit($this->db, $set);
        $sqlObj->checkSingleRow($keys);

        if ($this->model->beforeDelete($this->db, $values, $keys) === false) {
            exit;
        }
        $sql = $sqlObj->getDelete();
        
        // Transaction
        $this->db->startTransaction();
        try {
            $result = $this->db->execute($sql);
        } catch (Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 Database error');
            die(Data2Html_Value::toJson(
                Data2Html_Exception::toArray($e, $this->debug)
            ));
        }
        $this->model->afterDelete($this->db, $values, $keys);
        $this->db->commit();

        return $result;
    }
}
