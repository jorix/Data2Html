<?php

class Data2Html_Sql
{
    protected $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function getSelect(
        $table, 
        $colDefs, 
        $filterDefs = null,
        $filterReq = array(),
        $sortColReq = null
    )
    {
        $def = new Data2Html_Collection();
        $dbfs = array();
        foreach ($colDefs as $k=>$v) {
            $def->set($v);
            $name = $def->getString('name', $k);
            $dbName = $def->getString('db', $name);
            if ($dbName !== null) {
                if ($name === $dbName) {
                    array_push($dbfs, $dbName);
                } else {
                    array_push($dbfs, $dbName.' '.$name); // db-field with alias
                }
            }
        }
        if (count($dbfs) === 0) {
            throw new Exception("No data base fields defined.");
        }
        $query = 'select ' . implode(',', $dbfs);
        $query .= " from {$table}";
        $where = $this->getWhere($filterDefs, $filterReq);
        if ($where) {
            $query .= " where {$where}";
        }
        $orderBy = $this->getOrderBy($colDefs, $sortColReq);
        if ($orderBy) {
            $query .= " order by {$orderBy}";
        }
        return $query;
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
        $def = new Data2Html_Collection();
        foreach ($filterDefs as $k => $v) {
            $def->set($v);
            $fName = $def->getString('name'); 
            $fCheck = $def->getString('check');
            $dbName = $def->getString('db'); 
            if (
                $dbName === null ||
                $fCheck === null
            ) {
                continue;
            }
            $type = $def->getString('type', 'string');
            // forced value
            $r = $def->toSql($this->db, 'value', $type, null);
            if ($r === null) {
                // requested value
                $r = $requestValues->toSql($this->db, $fName, $type, null);
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
                        "getWhere(): Check '{$fCheck}' on item {$k}=>'{$fName}' is not supported."
                    );
                    break;
                }
                array_push($c, "{$dbName} {$dbCheck} {$r}");
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