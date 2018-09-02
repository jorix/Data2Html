<?php
namespace Data2Html;

use Data2Html\Config;
use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Data\To;
use Data2Html\Model\Set;
use Data2Html\Controller\SqlSelect;
use Data2Html\Controller\Validate;

use Data2Html\Handler;

class Controller
{
    use \Data2Html\Debug;
    
    protected $model;
    protected $db;
    public function __construct($model)
    {
        // Data base
        $this->db = Db::create(Config::getSection('db'));

        $this->model = $model;
    }
    
    public function manage($request)
    {
        $postData = [];
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
        $playerNames = Handler::parseRequest($request);
        $response = [];
        switch ($oper) {
            case '':
            case 'read':
                if (isset($playerNames['block'])) {
                    $lkForm = $model->getLinkedBlock($playerNames['block']);
                    return $this->opReadForm($lkForm, $r->get('d2h_keys'));
                } elseif (isset($playerNames['grid'])) {
                    $lkGrid = $model->getLinkedGrid($playerNames['grid']);

                    // Prepare sql
                    $sqlObj = new SqlSelect(
                        $this->db,
                        $lkGrid->getColumns()
                    );
                    $sqlObj->addFilter(
                        $lkGrid->getFilter(),
                        $r->getArray('d2h_filter')
                    );
                    $sqlObj->addSort($r->getString('d2h_sort'));
                    
                    // Response
                    $page = $r->getLot('d2h_page', []);
                    return $this->opRead(
                        $sqlObj->getSelect(),
                        $lkGrid->getColumns(),
                        $page->getInteger('pageStart', 1),
                        $page->getInteger('pageSize', 0)
                    );
                }
            case 'insert':
                $val = new Validate('ca');
                $lkElem = $model->getLinkedBlock($playerNames['block']);
                $postValues = Lot::getItem('d2h_data', $postData);
                $validation = $val->validateData(
                    $postValues,
                    Set::getVisualItems($lkElem->getLinkedItems())
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
                $val = new Validate('ca');
                $lkElem = $model->getLinkedBlock($playerNames['block']);
                $postValues = Lot::getItem('d2h_data', $postData);
                $keys = Lot::getItem('[keys]', $postValues);
                $validation = $val->validateData(
                    $postValues,
                    Set::getVisualItems($lkElem->getLinkedItems())
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
                $this->opDelete($model->getLinkedBlock($playerNames['block']), $postValues, $keys);
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
        $resTypes = [];
        $dbTypes = [];
        $values = [];
        $valuePatterns = [];
        $useList = [];
        $addValue = function($depth, $keyItem, $lkItem)
                    use(&$addValue, $lkColumns, &$values, &$valuePatterns) {
            if ($depth > 10) {
                throw new DebugException(
                    "Possible circular reference in \"{$keyItem}\" valuePatterns.",
                    $lkItem
                );
            }
            $patterns = Lot::getItem('value-patterns', $lkItem);
            if ($patterns) {
                foreach ($patterns as $v) {
                    $ref = $v['final-base'];
                    $matchItem = $lkColumns[$ref];
                    if (array_key_exists('value', $matchItem) && 
                        !array_key_exists($ref, $values)
                    ) {
                        $addValue($depth+1, $ref, $matchItem);
                    }
                }
                $valuePatterns[$keyItem] = $patterns;
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
            $finalList = $itemDx->getString('link-list');
            if ($finalList) {
                $useList[$k] = [
                    'base' => $finalList,
                    'list' => Lot::getItem([$finalList, 'list'], $lkColumns)
                ];
            }
            if (array_key_exists('value', $v)) {
                $addValue(0, $k, $v);
            }
        }
        // Read rs
        $keyNames = array_keys($lkSet->getLinkedKeys());
        $rows = [];
        $result = $this->db->queryPage($query, $pageStart, $pageSize);
        while ($dbRow = $this->db->fetch($result)) {
            foreach ($dbRow as $k => &$v) {
                $v = $this->db->toValue($v, $dbTypes[$k]);
            }
            unset($v);
            
            $valueRow = [];
            foreach ($values as $k => $v) {
                $valueRow[$k] = $values[$k];
                if (array_key_exists($k, $valuePatterns)) {
                    $allIsNull = true;
                    foreach ($valuePatterns[$k] as $kk => $vv) {
                        $ref = $vv['final-base'];
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
            $resRow = [];
            foreach ($resTypes as $k => $v) {
                if (array_key_exists($k, $dbTypes)) {
                    $resRow[$k] = $dbRow[$k];
                } elseif (array_key_exists($k, $valueRow)) {
                    $resRow[$k] = $valueRow[$k];
                } 
                if (array_key_exists($k, $useList) && !isset($resRow[$k])) {
                    $useListItem = &$useList[$k];
                    $val = $dbRow[$useListItem['base']];
                    $resRow[$k] = Lot::getItem($val, $useListItem['list'], $val);
                    unset($useListItem);
                }
            }
            $dataKeys = [];
            foreach ($keyNames as $k) {
                $dataKeys[] = $dbRow[$k];
            }
            $resRow['[keys]'] = $dataKeys;
            $rows[] = $resRow;
        }
        $this->db->closeQuery($result);
        
        $response = [];
        if (Config::debug()) {
            $response['debug'] = array(
                'sql' => explode("\n", $query),
                'keys' => $lkSet->getLinkedKeys(),
                'values' => $values,
                'value-patterns' => $valuePatterns
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
    }
}
