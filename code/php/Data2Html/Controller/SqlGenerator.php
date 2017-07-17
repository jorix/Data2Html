<?php

class Data2Html_Controller_SqlGenerator
{
    protected $culprit = 'SqlGenerator';
    protected $debug = false;
    
    protected $db;
    protected $linkedSet;
    protected $result = array();

    public function __construct($db, $linkedSet) {
        $this->debug = Data2Html_Config::debug();
        
        $this->db = $db;
        $this->linkedSet = $linkedSet;
        
        $lkColumns = $linkedSet->getLinkedItems();
        
        $this->result['select'] = $this->getSelectItems($lkColumns);
        if ($this->result['select'] === '') {
            throw new Exception("No data base fields defined.");
        }
        $this->result['form'] =
            $this->getFrom($linkedSet->getLinkedFrom());
    }

    public function dump($subject = null)
    {
        Data2Html_Utils::dump($this->culprit, $subject);
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
        $query .= "\n from " . $this->result['form'];
        
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
            $refDb = Data2Html_Value::getItem($v, 'refDb');
            if ($refDb !== null) {
                array_push($textFields,  $this->db->putAlias($k, $refDb));
            }
        }
        return implode(', ', $textFields);
    }

    protected function getFrom($joins)
    {
        $from = '';
        foreach ($joins as $v) {
            if($v['from'] === 'T0') {
                $from .= $this->db->putAlias($v['alias'], $v['table']);
            } else {
                $from .= "\n left join " . $this->db->putAlias($v['alias'], $v['table']);
                $keys = $v['keys'];
                $keyBases = array_keys($keys);
                $onKeys = array();
                for ($i = 0; $i < count($keyBases); $i++) {
                    // TODO 'fromField' as multi key
                    array_push($onKeys, "{$v['fromField']} = {$keys[$keyBases[$i]]['refDb']}");
                }
                $from .= "\n   on " . implode("\n   and ", $onKeys);
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
            throw new Data2Html_Exception(
                "{$this->culprit} getWhereByKeys(): Requested keys not match number of keys.",
                array(
                    'keys' => $keys,
                    'request' => $request
                )
            );
        }
        $lkColumns = $this->linkedSet->getLinkedItems();
        $baseKeys = array_keys($keys);
        $ix = 0;
        $c = array();
        foreach ($request as $v) {
            $baseName = $baseKeys[$ix];
            $refDb = $keys[$baseName]['refDb'];
            if ($v === '' || $v === null) {
                array_push($c, "{$refDb} is null");
            } else {
                $type = $lkColumns[$baseName]['type'];
                $r = Data2Html_Value::toSql($this->db, $v, $type);
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
        $itemDx = new Data2Html_Collection();
        foreach ($request as $k => $v) {
            if ($v === '' || $v === null) {
                continue;
            }
            if (!array_key_exists($k, $filterItems)) {
                throw new Data2Html_Exception(
                    "{$this->culprit} getWhere(): Requested filter field '{$k}' not found on filter definition.",
                    $filterItems
                );
            }
            $itemDx->set($filterItems[$k]);
            $refDb = $itemDx->getString('refDb');
            $check = $itemDx->getString('check');
            $type = $itemDx->getString('type', 'string');
            if (
                $refDb === null ||
                $check === null ||
                $type === null
            ) {
                continue;
            }
            switch ($check) {
                case 'EQ':
                    $dbCheck = '=';
                    break;
                case 'LK':
                    $dbCheck = 'like';
                    if (strpos($v, '%') === false) {
                        $v = '%' . $v . '%';
                    }
                    break;
                default:
                throw new Data2Html_Exception(
                    "{$this->culprit} getWhere(): Check '{$check}' on '{$k}' not supported.",
                    array(
                        'request' => $request,
                        'filterItems' => $filterItems
                    )
                );    
            }
            $r = Data2Html_Value::toSql($this->db, $v, $type);
            array_push($c, "{$refDb} {$dbCheck} {$r}");
        }
        return implode(' and ', $c);
    }

    protected function getOrderBy($columns, $colNameRequest)
    {
        if (!$colNameRequest) {
            return '';
        }
        if (substr($colNameRequest, 0, 1) === '!') {
            $baseName = substr($colNameRequest, 1);
            $order = -1;
        } else {
            $baseName = $colNameRequest;
            $order = 1;
        }
        $sortBy = Data2Html_Value::getItem($columns, array($baseName, 'sortBy', 'items'));
        if (!$sortBy) {
            throw new Data2Html_Exception(
                "{$this->culprit} getOrderBy(): Requested sort field '{$baseName}' not found or don't have sortBy .",
                $columns
            );
        }
        $c = array();
        foreach ($sortBy as $v) {
            $item = $v['refDb'];
            if ($v['order'] * $order < 0) {
                $item .= ' desc';
            }
            array_push($c, $item);
        }
        return implode(', ', $c);
    }

}