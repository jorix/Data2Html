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

    public function getSelect(
        $lkGrid,
        $filterReq = array(),
        $sortReq = null
    ) {
        $select = $this->getSelectText($lkGrid->get('columns'));
        if ($select === '') {
            throw new Exception("No data base fields defined.");
        }
        $query = 'select ' . $select;
        $query .= "\n from {$this->getFrom($lkGrid->getFromTables())}";
        echo '<pre>' . $query . '</pre>';
        die($query);
        $where = $this->getWhere($filterDefs, $filterReq);
        if ($where !== '') {
            $query .= "\n where {$where}";
        }
        if (!$sortReq) { // use default sort
            $sortReq = $gridDx->getString('sort', '');
        }
        $orderBy = $this->getOrderBy($colDefs, $sortReq);
        if ($orderBy !== '') {
            $query .= "\n order by {$orderBy}";
        }
        return $query;
    }

    public function dump($subject = null)
    {
        Data2Html_Utils::dump($this->culprit, $subject);
    }
    
    protected function getSelectText($lkFields)
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
                $fromKeys = $joins[$v['fromAlias']]['keys'];
                $onKeys = array();
                for ($i = 0; $i < count($keys); $i++) {
                    array_push($onKeys, "{$fromKeys[$i]['refDb']} = {$keys[$i]['refDb']}");
                }
                $from .= "\n   on " . implode("\n   and ", $onKeys);
            }
        }
        return $from;
    }
    
    protected function getOrderBy($colDefs, $colNameRequest)
    {
        if (!$colNameRequest) {
            return '';
        }
        if (substr($colNameRequest, 0, 1) === '!') {
            $nakedColName = substr($colNameRequest, 1);
            $reverse = true;
        } else {
            $nakedColName = $colNameRequest;
            $reverse = false;
        }
        $sortByDef = Data2Html_Value::getItem(
            $colDefs,
            array($nakedColName, 'sortBy')
        );
        $c = array();
        if ($sortByDef) {
            foreach ($sortByDef as $k => $v) {
                if ($reverse) {
                    if ($v === 'asc') {
                        $v = 'desc';
                    } else {
                        $v = 'asc';
                    }
                }
                array_push($c, $k . ' ' . ($v) );
            }    
        }
        return implode(', ', $c);
    }

    protected function getWhere($filterDefs, $request)
    {
        if (!$filterDefs) {
            return '';
        }
        $requestValues = new Data2Html_Collection($request);
        $c = array();
        $itemDx = new Data2Html_Collection();
        foreach ($filterDefs as $k => $v) {
            $itemDx->set($v);
            $fCheck = $itemDx->getString('check');
            $fieldDb = $itemDx->getString('db'); 
            if (
                $fieldDb === null ||
                $fCheck === null
            ) {
                continue;
            }
            $type = $itemDx->getString('type', 'string');
            // forced value
            $rr = $itemDx->getValue('value', $type);
            if ($rr === null) {
                // requested value
                $rr = $requestValues->getValue($k, $type);
            }
            if ($rr !== null) {
                switch ($fCheck) {
                case 'EQ':
                    $dbCheck = '=';
                    break;
                case 'LK':
                    $dbCheck = 'like';
                    if (strpos($rr, '%') === false) {
                        $rr = '%' . $rr . '%';
                    }
                    break;
                default:
                    throw new Exception(
                        "getWhere(): Check '{$fCheck}' on item '{$k}'=>'{$fieldDb}' is not supported."
                    );
                    break;
                }
                $r = Data2Html_Value::toSql($this->db, $rr, $type);
                array_push($c, "{$fieldDb} {$dbCheck} {$r}");
            }
        }
        return implode(' and ', $c);
    }

    public function parseSelect($sql) {
        $sql = trim(str_replace(
            array("\t", "\r", "\n"),
            array(' ',' ',' '),
            $sql
        ));
        $sqlUp = strtoupper($sql);
        // Is select?
        if (substr($sqlUp, 0, 7) !== "SELECT ") {
            throw new Exception('putWhere(): \$sql is not a `SELECT`.');
        }
        $pos = 6; // After the SELECT_
        
        $posFrom = strpos(sqlUp, " FROM ", $pos);
        
        // Where
        $posWhere = strpos(sqlUp, " WHERE ", $pos);
        if ($posWhere !== false) {
            // Sub queries
            $pos = getPosSubSelect($posWhere + 7, $sqlUp);
        }
        
        // Group by
        $posGroup = strpos($sqlUp, " GROUP BY ", $pos);
        if ($posGroup !== false) {
            $pos = $posGroup + 10;
        }
        
        // Having
        $posHaving = strpos($sqlUp, " HAVING ", $pos);
        if ($posHaving !== false) {
            $pos = getPosSubSelect($posHaving + 8, sqlUp);
        }

        // Order by
        $posOrderBy = strpos($sqlUp, " ORDER BY ", $pos);

        // end
        $posEnd = strpos($sqlUp, ";", $pos);
        if ($posEnd === false) {
            $posEnd = count($sqlUp);
        }
        
        // pos of parts
        $endFrom = $posEnd;
        $endWhere = $posEnd;
        $endGroup = $posEnd;
        $endHaving = $posEnd;
        $endOrderBy = $posEnd;
        if ($posOrderBy !== false ) {
            $endFrom = $posOrderBy;
            $endWhere = $posOrderBy;
            $endGroup = $posOrderBy;
            $endHaving = $posOrderBy;
        }
        if ($posHaving !== false ) {
            $endFrom = $posHaving;
            $endWhere = $posHaving;
            $endGroup = $posHaving;
        } 
        if ($posGroup !== false) {
            $endFrom = $posGroup;
            $endWhere = $posGroup;
        } 
        if ($posWhere !== false) {
            $endFrom = $posWhere;
        }
        $pos = 6;
        $selectPart = substr($sql, $pos, $posFrom - $pos);
        $pos = $posFrom + 6;
        $fromPart = substr($sql, $pos, $endForm - $pos);
        

        // Insert order by
        if ($sortBy !== null) {
            if ($posOrdrBy === 0) {
                $posOrderBy = $posEnd;
            }
            if ($ordreBy === '') {
                $sqlUp = substr($sqlUp, 1, $posOrder - 1) . " ORDER BY " . $sortBy & substr($sqlUp, $posFi);
            } else {
                $sqlUp = substr($sqlUp, 1, $posOrder - 1) . substr($sqlUp, $posFi);
            }
        }
        
        // Insert or remove HAVING
        if ($posGroup !== false && $having !== null) {
            if ($posOrderBy !== false) {
                $endHaving = $posOrderBy;
            } else {
                $endHaving = $endSql;
            }
            if ($posHaving === false) {
                $posHaving = $endHaving;
            }
            if ($having === '') { // remove
                $sql = substr($sql, 0, $posHaving - 1) . substr($sql, $endHaving);
            } else {
                $sql = substr($sql, 0, $posHaving - 1) .
                    " HAVING " . $having .
                    substr($sql, $endHaving);
            }
        }
    }

    protected function getPosSubSelect($posStart, $sqlUp) 
    {
        while (true) {
            $posSubSel = strpos($sqlUp, "SELECT ", $pos);
            if ($posSubSel === false) {
                break;
            }
            $aux = substr($sqlUp, $posSubSel - 1, 1);
            if ($aux === ' ' || $aux === '(') {
                $pos = $posSubSel + 7;
                $textStart = '';
                $level = 1;
                while (true) {
                    if ($pos > count($sqlUp)-1) {
                        throw new Exception(
                            'getPosSubSelect(): Sub $sql `SELECT` is not valid.'
                        );
                    } 
                    $aux = substr($sqlUp, $pos, 1);
                    $pos++;
                    if ($textStart !== '') {
                        if ($textStart === $aux) {
                            $textStart = '';
                        } 
                    } elseif ($aux = '"' || $aux = "'") {
                        $textStart = $aux;
                    } elseif ($aux = "(") {
                        $level++;
                    } elseif ($aux = ")") {
                        $level--;
                        if ($level === 0) {
                            break;
                        }
                    }
                }
            }
        }
        return $pos;
    }
}