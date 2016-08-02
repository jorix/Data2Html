<?php

class Data2Html_Sql
{
    protected $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getSelect(
        $grid,
        $filterReq = array(),
        $sortReq = null
    ) {
        $gridDx = new Data2Html_Collection($grid);
        $colDefs = $gridDx->getArray('columns');
        $filterDx = $gridDx->getCollection('filter', false);
        if ($filterDx) {
            $filterDefs = $filterDx->getArray('fields');
        } else {
            $filterDefs = null;
        }

        $select = $this->getSelectText($colDefs);
        if (count($select) === '') {
            throw new Exception("No data base fields defined.");
        }
        $query = 'select ' . $select;
        $query .= "\n from {$this->getFrom($gridDx)}";
        $where = $this->getWhere($filterDefs, $filterReq);
        if ($where !== '') {
            $query .= " where {$where}";
        }
        if (!$sortReq) { // use default sort
            $sortReq = $gridDx->getString('sort', '');
        }
        $orderBy = $this->getOrderBy($colDefs, $sortReq);
        if ($orderBy !== '') {
            $query .= " order by {$orderBy}";
        }
        return $query;
    }

    protected function getSelectText($pFields)
    {
        $textFields = array();
        $itemDx = new Data2Html_Collection();
        foreach ($pFields as $k=>$v) {
            $itemDx->set($v);
            $fieldDb = $itemDx->getString('db');
            if ($fieldDb !== null) {
                if ($fieldDb === $k) {
                    array_push($textFields, $fieldDb);
                } else { // db-field with alias
                    array_push($textFields,  $fieldDb . ' ' . $k);
                }
            }
        }
        return implode(', ', $textFields);
    }

    protected function getFrom($gridDx)
    {
        $joins = $gridDx->getArray('joins');
        if ($joins) {
            $from = '';
            foreach ($joins as $v) {
                if(!$v['fromTable']) {
                    $from .= "\n {$v['toTable']} {$v['toAlias']}";
                } else {
                    $from .= "\n left join {$v['toTable']} {$v['toAlias']}";
                    $from .= "\n on {$v['fromDbKeys']} = {$v['toDbKeys']}";
                }
            }
        } else {            
            $from =$gridDx->getArray('table');
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
            $ascending = false;
        } else {
            $nakedColName = $colNameRequest;
            $ascending = true;
        }
        $orderByDef = Data2Html_Array::get(
            $colDefs,
            array($nakedColName, 'orderBy')
        );
        $c = array();
        if ($orderByDef) {
            foreach ($orderByDef as $v) {
                if (substr($v, 0, 1) === '!') {
                    $dbField = substr($v, 1);
                    $dbAscen = !$ascending;
                } else {
                    $dbField = $v;
                    $dbAscen = $ascending;
                }
                array_push($c, $dbField . ($dbAscen ? ' ASC' : ' DESC'));
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
            $r = $itemDx->toSql($this->db, 'value', $type, null);
            if ($r === null) {
                // requested value
                $r = $requestValues->toSql($this->db, $k, $type, null);
            }
            if ($r !== null) {
                switch ($fCheck) {
                case 'EQ':
                    $dbCheck = '=';
                    break;
                case 'LK':
                    $dbCheck = 'like';
                    break;
                default:
                    throw new Exception(
                        "getWhere(): Check '{$fCheck}' on item '{$k}'=>'{$fieldDb}' is not supported."
                    );
                    break;
                }
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
        if ($orderBy !== null) {
            if ($posOrdrBy === 0) {
                $posOrderBy = $posEnd;
            }
            if ($ordreBy === '') {
                $sqlUp = substr($sqlUp, 1, $posOrder - 1) . " ORDER BY " . $orderBy & substr($sqlUp, $posFi);
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