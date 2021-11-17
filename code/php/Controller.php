<?php
namespace Data2Html;

use Data2Html\Config;
use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Data\To;
use Data2Html\Data\Parse;
use Data2Html\Model\Set;
use Data2Html\Model\Models;
use Data2Html\Controller\SqlSelect;
use Data2Html\Controller\Validate;

class Controller
{
    use \Data2Html\Debug;
    
    protected $db;
    public function __construct()
    {
        // Data base
        $this->db = Db::create(Config::getSection('db'));
    }
    
    public function manage($request)
    {
        $postData = [];
        $serverMethod = $_SERVER['REQUEST_METHOD'];
        switch ($serverMethod) {
            case 'GET':
                break;
            case 'POST':
                $postData = json_decode(file_get_contents("php://input"), true);
                break;
            default:
                throw new \Exception(
                    "Server method {$serverMethod} is not supported."
                );
        }
        return $this->action(Lot::getItem('action', $request, ''), $request, $postData);
    }
    
    protected function action($action, $request, $postData)
    {
        $pNames = Models::parseRequest($request);
        self::extractValue('model', $request);
        self::extractValue('grid', $request);
        self::extractValue('block', $request);
        self::extractValue('debug', $request);
        self::extractValue('_', $request);
        
        $response = [];
        switch ($action) {
            case '':
                if (isset($pNames['block'])) {
                    return $this->opReadBlock(
                        Models::linkBlock($pNames['model'], $pNames['block']),
                        self::extractValue('_keys_', $request, 'array')
                    );
                } elseif (isset($pNames['grid'])) {
                    return $this->opReadGrid(
                        Models::linkGrid($pNames['model'], $pNames['grid']),
                        $request
                    );
                }
            case 'insert':
                $lkBlock = Models::linkBlock($pNames['model'], $pNames['block']);
                $val = new Validate('ca');
                $validation = $val->validateData(
                    $postData,
                    Set::getVisualItems($lkBlock->getLinkedItems())
                );
                if (count($validation['user-errors']) > 0) {
                    if (!Config::debug()) {
                        ob_clean();
                    }
                    header('HTTP/1.0 401 Validation errors');
                    exit(To::json($validation));
                }
                return $this->opInsert($lkBlock, $validation['data']);
                
            case 'update':
                $keys = self::extractValue('_keys_', $postData, 'array');
                $lkBlock = Models::linkBlock($pNames['model'], $pNames['block']);
                $val = new Validate('ca');
                $validation = $val->validateData(
                    $postData,
                    Set::getVisualItems($lkBlock->getLinkedItems())
                );
                if (count($validation['user-errors']) > 0) {
                    if (!Config::debug()) {
                        ob_clean();
                    }
                    header('HTTP/1.0 401 Validation errors');
                    exit(To::json($validation));
                }
                return $this->opUpdate($lkBlock, $validation['data'], $keys);
                
            case 'delete':
                $keys = self::extractValue('_keys_', $postData, 'array');
                return  $this->opDelete(
                     Models::linkBlock($pNames['model'], $pNames['block']),
                    $postData,
                    $keys
                );
                
            default:
                throw new DebugException("Action '{$action}' is not defined");
        }
    }
    
    protected static function extractValue($key, &$array, $type = '', $default = null)
    {
        if (array_key_exists($key, $array)) {
            if ($type) {
                $response = Parse::value($array[$key], $type, $default, true);
            } else {
                $response = $array[$key];
            }
        } else {
            $response = $default;
        }
        return $response;
    }
    
    /**
     * Execute a query and return the array result.
     */
    protected function opReadGrid($lkGrid, $request)
    {
        $lkSet = $lkGrid->getLinkedColumns();
        
        // Create a sql select
        $sqlObj = new SqlSelect($this->db, $lkSet);
        // Extract Sort to add to sql
        $sqlObj->sortByName(self::extractValue('sort', $request, 'string'));
        // Filter (the rest) to sql
        $sqlObj->filterByRequest($lkGrid->getFilter(), $request);
        
        // Response
        return $this->opRead(
            $sqlObj->getSelect(),
            $lkSet,
            self::extractValue('pageStart', $request, 'integer', 1),
            self::extractValue('pageSize', $request, 'integer', 0)
        );
    }

    /**
     * Execute a query and return the array result.
     */
    protected function opReadBlock($lkBlock, $keys)
    {
        $sqlObj = new SqlSelect($this->db, $lkBlock);
        $sqlObj->filterByKeys($keys);
        
        // Response
        $result = $this->opRead($sqlObj->getSelect(), $lkBlock, 1, 1);
        if (count($result['rows']) !== 1 || $result['moreRows']) {
            throw new DebugException("Read block must select one row!", [
                'keys' => $keys,
                'result' => $result
            ]);
        } 
        return $result;
    }
    
    /**
     * Execute a query and return the array result.
     */
    protected function opReadBridge($lkGrid, $values)
    {
        $lkSet = $lkGrid->getLinkedColumns();
        $sqlObj = new SqlSelect($this->db, $lkSet);
        $sqlObj->filterByValues($values);
        
        // Response
        $result = $this->opRead($sqlObj->getSelect(), $lkSet);
        return $result;
    }
    
    
    /**
     * Execute a query and return the array result.
     */
    protected function opRead($query, $lkSet, $pageStart = 1, $pageSize = 0)
    {
        try {
            $pageSize = intval($pageSize);
            $pageSizePlus = $pageSize ? $pageSize + 1 : 0;
            $pageStart = intval($pageStart);
            
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        };
        $itemDx = new Lot();
        $lkColumns = $lkSet->getLinkedItems();
        $_responseItems = [];
        $_dbItems = [];
        $_listItems = [];
        $_dbMultiItems = [];
        $_bridgeItems = [];
        $_valueItems = [];
        $_valuePatterns = [];
        
        $addValue = function($depth, $keyItem, $lkItem)
                    use(&$addValue, $lkColumns, &$_valueItems, &$_valuePatterns) {
            if ($depth > 10) {
                throw new DebugException(
                    "Possible circular reference in \"{$keyItem}\" _valuePatterns.",
                    $lkItem
                );
            }
            $patterns = Lot::getItem('value-patterns', $lkItem);
            if ($patterns) {
                // Nested patters
                foreach ($patterns as $v) {
                    $ref = $v['table-item'];
                    $matchItem = $lkColumns[$ref];
                    if (array_key_exists('value', $matchItem) && 
                        !array_key_exists($ref, $_valueItems)
                    ) {
                        $addValue($depth+1, $ref, $matchItem);
                    }
                }
                $_valuePatterns[$keyItem] = $patterns;
            }
            $_valueItems[$keyItem] = $lkItem['value'];
        };
        
        foreach ($lkColumns as $k => $v) {
            $itemDx->set($v);
            $type = $itemDx->getString('type', 'string');
            $types[$k] = $type;
            if (!$itemDx->getBoolean('_instrumental')) {
                $_responseItems[$k] = $type;
            }
            if ($itemDx->getString('db')) {
                $_dbItems[$k] = $type;
            }
            if (isset($v['bridge'])) {
                $_bridgeItems[$k] = $v['bridge'];
            }
            if (isset($v['list-item'])) {
                $_listItems[$k] = [
                    $v['list-item'],
                    $lkColumns[$v['list-item']]['list']
                ];
            }
            if (isset($v['db-items'])) {
                $names = [];
                foreach ($v['db-items']['items'] as $vv) {
                    $names[] = $vv['table-item'];
                }
                $_dbMultiItems[$k] = $names;
            }
            if (array_key_exists('value', $v)) {
                $addValue(0, $k, $v);
            }
        }
        // Read rs
        $keyNames = array_keys($lkSet->getLinkedKeys());
        $rows = [];
        $more = false;
        $result = $this->db->queryPage($query, $pageStart, $pageSizePlus);
        while ($dbRow = $this->db->fetch($result)) {
            if ($pageSize && count($rows) >= $pageSize) {
                $more = true;
                break;
            }
            foreach ($dbRow as $k => &$v) {
                $v = $this->db->toValue($v, $_dbItems[$k]);
            }
            unset($v);
            
            $valueRow = [];
            foreach ($_valueItems as $k => $v) {
                $valueRow[$k] = $_valueItems[$k];
                if (array_key_exists($k, $_valuePatterns)) {
                    $allIsNull = true;
                    foreach ($_valuePatterns[$k] as $kk => $vv) {
                        $ref = $vv['table-item'];
                        if (array_key_exists($ref, $dbRow)) {
                            $value = $dbRow[$ref];
                        } elseif (array_key_exists($ref, $valueRow)) {
                            $value = $valueRow[$ref];
                        } elseif (!$itemDx->getBoolean('_virtual', false)) {
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
            foreach ($_responseItems as $k => $v) {
                if (array_key_exists($k, $_dbItems)) {
                    $resRow[$k] = $dbRow[$k];
                } elseif (array_key_exists($k, $valueRow)) {
                    $resRow[$k] = $valueRow[$k];
                } elseif (array_key_exists($k, $_dbMultiItems)) {
                    $resItem = [];
                    foreach ($_dbMultiItems[$k] as $vv) {
                        $resItem[] = $dbRow[$vv];
                    }
                    $resRow[$k] = $resItem;
                } elseif (array_key_exists($k, $_bridgeItems)) {
                    // Pending to close $result only when $pageSize === 1
                    $resRow[$k] = [
                        $_bridgeItems[$k]['bridge-item'] => $dbRow[$keyNames[0]]
                    ]; 
                }
                
                if (array_key_exists($k, $_listItems) && !isset($resRow[$k])) {
                    $val = $dbRow[$_listItems[$k][0]];
                    $resRow[$k] = Lot::getItem($val, $_listItems[$k][1], $val);
                }
            }
            $dataKeys = [];
            foreach ($keyNames as $k) {
                $dataKeys[] = $dbRow[$k];
            }
            $resRow['_keys_'] = $dataKeys;
            $rows[] = $resRow;
        }
        $this->db->closeQuery($result);
        
        if ($pageSize === 1) {
            $resRow =& $rows[0];
            foreach ($_bridgeItems as $k => $v) {
                $resRow[$k] = $this->opReadBridge($v['bridge-grid'], ($resRow[$k]));
            }
            unset($resRow);
        }
        
        $response = [];
        if (Config::debug()) {
            $response['debug'] = array(
                'sql' => explode("\n", $query),
                'keys' => $lkSet->getLinkedKeys(),
                'as-values' => $_valueItems,
                'value-patterns' => $_valuePatterns
            );
        }
        $response += [
            'pageStart' => $pageStart,
            'pageSize' => $pageSize,
            'moreRows' => $more
        ];
        if (true) {
            $response['rows'] = $rows;
        } else {
            $response += [
                'dataCols' => array_keys($_responseItems),
                'rowsAsArray' => 'TODO'
            ];
        }
        return $response;
    }

    protected function opInsert($lkSet, &$values)
    {
        // Transaction
        $response = ['success' => false];
        $this->db->startTransaction();
        try {
            $response = $lkSet->dbInsert($this->db, $values);
        } catch (\Exception $e) {
            $this->db->rollback();
            if (!Config::debug()) {
                ob_clean();
            }
            header('HTTP/1.0 401 Database error');
            exit(To::json(DebugException::toArray($e)));
        }
        if ($response['success']) {
            $this->db->commit();
        } else {
            $this->db->rollback();
        }
        return $response;
    }
    
    protected function opUpdate($lkSet, &$values, $keys)
    {
        // Transaction
        $response = ['success' => false];
        $this->db->startTransaction();
        try {
            $response = $lkSet->dbUpdate($this->db, $values, $keys);
        } catch (\Exception $e) {
            $this->db->rollback();
            if (!Config::debug()) {
                ob_clean();
            }
            header('HTTP/1.0 401 Database error');
            exit(To::json(DebugException::toArray($e)));
        }
        if ($response['success']) {
            $this->db->commit();
        } else {
            $this->db->rollback();
        }
        return $response;
    }
    
    protected function opDelete($lkSet, &$values, $keys)
    {
        // Transaction
        $response = ['success' => false];
        $this->db->startTransaction();
        try {
            $response = $lkSet->dbDelete($this->db, $values, $keys);
        } catch (\Exception $e) {
            $this->db->rollback();
            if (!Config::debug()) {
                ob_clean();
            }
            header('HTTP/1.0 401 Database error');
            exit(To::json(DebugException::toArray($e)));
        }
        if ($response['success']) {
            $this->db->commit();
        } else {
            $this->db->rollback();
        }
        return $response;
    }
}
