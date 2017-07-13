<?php

class Data2Html_SqlGenerator
{
    protected $culprit = 'SqlGenerator';
    protected $debug = false;
    
    protected $db;

    public function __construct($db) {
        $this->debug = Data2Html_Config::debug();
        $this->db = $db;
    }

    public function dump($subject = null)
    {
        Data2Html_Utils::dump($this->culprit, $subject);
    }

    public function getSelect($lkGrid, $filterReq = null, $sortReq = null)
    {
        $lkColumns = $lkGrid->get('columns');
        $select = $this->getSelectItems($lkColumns);
        if ($select === '') {
            throw new Exception("No data base fields defined.");
        }
        $query = 'select ' . $select;
        $query .= "\n from " . $this->getFrom($lkGrid->getFromTables());
        $where = $this->getWhere($lkGrid->get('filter'), $filterReq);
        if ($where !== '') {
            $query .= "\n where {$where}";
        }
        
        if (!$sortReq) { // use default sort
            $sortReq = $lkGrid->getSort();
        }
        if ($sortReq) { 
            $orderBy = $this->getOrderBy($lkColumns, $sortReq);
            if ($orderBy !== '') {
                $query .= "\n order by {$orderBy}";
            }
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
                $onKeys = array();
                for ($i = 0; $i < count($keys); $i++) {
                    // TODO 'fromField' as multi key
                    array_push($onKeys, "{$v['fromField']} = {$keys[$i]['refDb']}");
                }
                $from .= "\n   on " . implode("\n   and ", $onKeys);
            }
        }
        return $from;
    }
    
    protected function getWhere($filter, $request)
    {
        if (!$request) {
            return '';
        }
        
        if (!$filter) {
            $filter = array();
        }
        $c = array();
        $itemDx = new Data2Html_Collection();
        foreach ($request as $k => $v) {
            if (!array_key_exists($k, $filter)) {
                throw new Data2Html_Exception(
                    "{$this->culprit} getWhere(): Requested filter field '{$k}' not found on filter definition.",
                    $filter
                );
            }
            $itemDx->set($filter[$k]);
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
                        'filter' => $filter
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