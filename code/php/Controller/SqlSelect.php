<?php
namespace Data2Html\Controller;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Db;
use Data2Html\Model\Join\LinkedSet;

class SqlSelect
{
    use \Data2Html\Debug;
    
    protected $db;
    protected $linkedSet;
    protected $result = array();

    public function __construct(Db $db, LinkedSet $linkedSet) {
        $this->db = $db;
        $this->linkedSet = $linkedSet;
        
        $lkColumns = $linkedSet->getLinkedItems();
        
        $this->result['select'] = $this->getSelectItems($lkColumns);
        if ($this->result['select'] === '') {
            throw new \Exception("No data base fields defined.");
        }
        $this->result['from'] = $this->getFrom($linkedSet->getLinkedFrom());
    }
    
    public function addFilter($filter, $filterReq = null)
    {
        if ($filter) {
            $this->result['where'] = $this->getWhere($filter->getLinkedItems(), $filterReq);
        }
    }
        
    public function addFilterByKeys($keysReq = null)
    {
        if ($keysReq) {
            $this->result['where'] = $this->getWhereByKeys($keysReq);
        }
    }    
    public function addSort($sortReq = null)
    {
        if ($sortReq === 'undefined') {
            $sortReq = null;
        }
        if (!$sortReq) { // use default sort
            $sortReq = $this->linkedSet->getSort();
        }
        if ($sortReq) { 
            $this->result['order_by'] = $this->getOrderBy(
                $this->linkedSet->getLinkedItems(), $sortReq
            );
        }
    }
    
    public function getSelect()
    {
        $query = 'select ' . $this->result['select'];
        $query .= "\n from " . $this->result['from'];
        
        if (isset($this->result['where']) && $this->result['where'] !== '') {
            $query .= "\n where {$this->result['where']}";
        }
        if (isset($this->result['order_by']) && $this->result['order_by'] !== '') {
            $query .= "\n order by {$this->result['order_by']}";
        }
        return $query;
    }
    
    protected function getSelectItems($lkFields)
    {
        $textFields = array();
        foreach ($lkFields as $k => $v) {
            $refDb = Lot::getItem('final-db', $v);
            if ($refDb) {
                array_push($textFields,  $this->db->putAlias($k, $refDb));
            }
        }
        return implode(', ', $textFields);
    }

    protected function getFrom($tableSources)
    {
        $from = '';
        foreach ($tableSources as $k => $v) {
            if($k === 'T0') {
                $from .= $this->db->putAlias($k, $v['table']);
            } else {
                $keys = $v['keys'];
                if ($keys) {
                    $fromFinalDb = $v['from-final-db'];
                    $i = 0;
                    $onKeys = [];
                    foreach ($keys as $vv) {
                        $onKeys[] = $fromFinalDb[$i] . " = " . $vv['final-db'];
                        $i++;
                    }
                    $from .= 
                        "\n left join " . $this->db->putAlias($k, $v['table']) . 
                        "\n   on " . implode("\n   and ", $onKeys);
                }
            }
        }
        return $from;
    }
    
    protected function getWhereByKeys($request)
    {   
        if (!is_array($request)) {
            $request = array($request);
        }
        $keys = $this->linkedSet->getLinkedKeys();
        if (count($request) !== count($keys)) {
            throw new DebugException("Requested keys not match number of keys.", [
                'keys' => $keys,
                'request' => $request
            ]);
        }
        $lkColumns = $this->linkedSet->getLinkedItems();
        $baseKeys = array_keys($keys);
        $ix = 0;
        $c = array();
        foreach ($request as $v) {
            $baseName = $baseKeys[$ix];
            $refDb = $keys[$baseName]['final-db'];
            if ($v === '' || $v === null) {
                array_push($c, "{$refDb} is null");
            } else {
                if (!array_key_exists('type', $lkColumns[$baseName])) {
                    throw new DebugException("Requested key withot type.", [
                        'baseName' => $baseName,
                        'item' => $lkColumns[$baseName]
                    ]);
                }
                $type = $lkColumns[$baseName]['type'];
                $r = $this->db->toSql($v, $type);
                array_push($c, "{$refDb} = {$r}");
            }
            $ix++;
        }
        return implode(' and ', $c);
    }
    
    protected function getWhere($filterItems, $request)
    {
        if (!$request) {
            return '';
        }
        
        if (!$filterItems) {
            $filterItems = array();
        }
        $c = array();
        $itemDx = new Lot();
        foreach ($request as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            if (!array_key_exists($k, $filterItems)) {
                throw new DebugException("Requested filter field '{$k}' not found on filter definition.",
                    $filterItems
                );
            }
            $itemDx->set($filterItems[$k]);
            $refDb = $itemDx->getString('final-db');
            $check = $itemDx->getString('check');
            $type = $itemDx->getString('type', 'string');
            if (
                $refDb === null ||
                $check === null ||
                $type === null
            ) {
                continue;
            }
            $dbCheckEnd = '';
            switch ($check) {
                case 'LE':
                    $dbCheck = '<=';
                    break;
                case 'GE':
                    $dbCheck = '>=';
                    break;
                case 'EQ':
                    $dbCheck = '=';
                    break;
                case 'SK': // like start
                    $dbCheck = 'like';
                    if (strpos($v, '%') === false) {
                        $v = $v . '%';
                    }
                    break;
                case 'LK': // like contains
                    $dbCheck = 'like';
                    if (strpos($v, '%') === false) {
                        $v = '%' . $v . '%';
                    }
                    break;
                case 'IN':
                    $dbCheck = 'in(';
                    $dbCheckEnd = ')';
                    break;
                default:
                throw new DebugException("Check '{$check}' on '{$k}' not supported.",
                    array(
                        'request' => $request,
                        'filterItems' => $filterItems
                    )
                );    
            }
            $r = $this->db->toSql($v, $type);
            array_push($c, "{$refDb} {$dbCheck} {$r} {$dbCheckEnd}");
        }
        return implode(' and ', $c);
    }

    protected function getOrderBy($columns, $colNameRequest)
    {
        if (!$colNameRequest) {
            return '';
        }
        switch (substr($colNameRequest, 0, 1)) {
            case '!':  case '-':  case '>':
                $baseName = substr($colNameRequest, 1);
                $order = -1;
                break;
            case '+': case '<':
                $baseName = substr($colNameRequest, 1);
                $order = 1;
                break;
            default:
                $baseName = $colNameRequest;
                $order = 1;
        }
        $sortBy = Lot::getItem([$baseName, 'sortBy', 'items'], $columns);
        if (!$sortBy) {
            throw new DebugException("Requested sort field '{$baseName}' not found or don't have sortBy .",
                $columns
            );
        }
        $c = array();
        foreach ($sortBy as $v) {
            $item = $v['final-db'];
            if ($v['order'] * $order < 0) {
                $item .= ' desc';
            }
            array_push($c, $item);
        }
        return implode(', ', $c);
    }

}