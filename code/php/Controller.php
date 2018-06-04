<?php
namespace Data2Html;

use Data2Html\DebugException;
use Data2Html\Controller\SqlSelect;
use Data2Html\Data\Lot;
use Data2Html\Data\To;

class Controller
{
    use \Data2Html\Debug;
    
    protected $model;
    protected $db;
    public function __construct($model)
    {
        // Data base
        $dbConfig = \Data2Html_Config::getSection('db');
        $db_class = 'Data2Html_Db_' . $dbConfig['db_class'];
        $this->db = new $db_class($dbConfig);

        $this->model = $model;
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
                throw new \Exception(
                    "Server method {$serverMethod} is not supported."
                );
        }
        return $this->oper($request, $postData);
    }
    
    protected function oper($request, $postData)
    {
        $model = $this->model;
        $r = new Lot($postData);
        $oper = $r->getString('d2h_oper', '');
        $playerNames = \Data2Html_Handler::parseRequest($request);
        $response = array();
        switch ($oper) {
            case '':
            case 'read':
                if (isset($playerNames['element'])) {
                    $lkForm = $model->getLinkedElement($playerNames['element']);
                    return $this->opReadForm($lkForm, $r->get('d2h_keys'));
                } elseif (isset($playerNames['grid'])) {
                    $lkGrid = $model->getLinkedGrid($playerNames['grid']);

                    // Prepare sql
                    $sqlObj = new SqlSelect(
                        $this->db,
                        $lkGrid->getColumnsSet()
                    );
                    $sqlObj->addFilter(
                        $lkGrid->getFilter(),
                        $r->getArray('d2h_filter')
                    );
                    $sqlObj->addSort($r->getString('d2h_sort'));
                    
                    // Response
                    $page = $r->getLot('d2h_page', array());
                    return $this->opRead(
                        $sqlObj->getSelect(),
                        $lkGrid->getColumnsSet(),
                        $page->getInteger('pageStart', 1),
                        $page->getInteger('pageSize', 0)
                    );
                }
            case 'insert':
                $val = new \Data2Html_Controller_Validate('ca');
                $lkElem = $model->getLinkedElement($playerNames['element']);
                $postValues = Lot::getItem('d2h_data', $postData);
                $validation = $val->validateData(
                    $postValues,
                    \Data2Html_Model_Set::getVisualItems($lkElem->getLinkedItems())
                );
                if (count($validation['user-errors']) > 0) {
                    header('HTTP/1.0 401 Validation errors');
                    die(To::json($validation));
                }
                $values = $validation['data'];
                $newId = $this->opInsert($lkElem, $values);
                
                // Get new keys
                $lkItems = $lkElem->getLinkedItems();
                $keyNames = $lkElem->getLinkedKeys();
                $keys = [];
                foreach($keyNames as $k => $v) {
                    if (Lot::getItem([$k, 'key'], $lkItems) === 'autoKey') {
                        $keys[] = $newId + 0;
                    } else {
                        $keys[] = $values[$k];
                    }
                }
                
                // Response record
                $response['keys'] = $keys;
                break;

            case 'update':
                $val = new \Data2Html_Controller_Validate('ca');
                $lkElem = $model->getLinkedElement($playerNames['element']);
                $postValues = Lot::getItem('d2h_data', $postData);
                $keys = Lot::getItem('[keys]', $postValues);
                $validation = $val->validateData(
                    $postValues,
                    \Data2Html_Model_Set::getVisualItems($lkElem->getLinkedItems())
                );
                if (count($validation['user-errors']) > 0) {
                    header('HTTP/1.0 401 Validation errors');
                    die(To::json($validation));
                }
                $this->opUpdate($lkElem, $validation['data'], $keys);
                break;

            case 'delete':
                $postValues = Lot::getItem('d2h_data', $postData);
                $keys = Lot::getItem('[keys]', $postValues);
                unset($postValues['[keys]']);
                $this->opDelete($model->getLinkedElement($playerNames['element']), $postValues, $keys);
                break;

            default:
                throw new DebugException("Oper '{$oper}' is not defined");
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
        $sqlObj = new SqlSelect($this->db, $lkForm);
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
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        };
        $itemDx = new Lot();
        $lkColumns = $lkSet->getLinkedItems();
        $resTypes = array();
        $dbTypes = array();
        $values = array();
        $teplateItems = array();
        $addValue = function($depth, $keyItem, $lkItem)
                    use(&$addValue, $lkColumns, &$values, &$teplateItems) {
            if ($depth > 10) {
                throw new DebugException(
                    "Possible circular reference in \"{$keyItem}\" teplateItems.",
                    $lkItem
                );
            }
            $matches = Lot::getItem('teplateItems', $lkItem);
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
                            throw new DebugException("Reference \"{$ref}\" is neither db field nor value.", [
                                'dbRow' => $dbRow,
                                'valueRow' => $valueRow
                            ]);
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
        if (Config::debug()) {
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

    protected function opInsert($lkSet, &$values)
    {
        $newId = null;
        
        // Transaction
        $this->db->startTransaction();
        try {
            $result = $lkSet->dbInsert($this->db, $values, $newId);
        } catch (\Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 Database error');
            die(To::json(DebugException::toArray($e)));
        }
        if ($result === false) {
            $this->db->rollback();
            return false;
        }
        $this->db->commit();

        return $newId;
    }
    
    protected function opUpdate($lkSet, &$values, $keys)
    {
        // Transaction
        $this->db->startTransaction();
        try {
            $result = $lkSet->dbUpdate($this->db, $values, $keys);
        } catch (\Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 Database error');
            die(To::json(DebugException::toArray($e)));
        }
        if ($result === false) {
            $this->db->rollback();
            return false;
        }
        $this->db->commit();

        return $result;
    }
    
    protected function opDelete($lkSet, &$values, $keys)
    {
        // Transaction
        $this->db->startTransaction();
        try {
            $result = $lkSet->dbDelete($this->db, $values, $keys);
        } catch (\Exception $e) {
            $this->db->rollback();
            header('HTTP/1.0 401 Database error');
            die(To::json(DebugException::toArray($e)));
        }
        if ($result === false) {
            $this->db->rollback();
            return false;
        }
        $this->db->commit();

        return $result;
    }
}
