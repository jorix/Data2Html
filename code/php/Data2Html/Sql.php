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
        $filterReq = array()
        //$orderBy = null
    )
    {
        $def = new Data2Html_Values();
        $dbfs = array();
        foreach ($colDefs as $k=>$v) {
            $def->set($v);
            $name = $def->getString('name', $k);
            $dbName = $def->getString('db', $name, true);
            if ($dbName !== null) {
                if ($name === $dbName) {
                    array_push($dbfs, $dbName);
                } else {
                    array_push($dbfs, $dbName.' '.$name);
                }
            }
        }
        if (count($dbfs) === 0) {
            throw new Exception("No dada base fields defined.");
        }
        $query = 'select ' . implode(',', $dbfs);
        $query .= " from {$table}";
        if ($filterDefs) {
            $where = $this->getWhere($filterDefs, $filterReq);
            if ($where) {
                $query .= " where {$where}";
            }
        }
        if (false) { //$orderBy) {
            $query .= " order by {$orderBy}";
        }
        return $query;
    }
    public function getWhere($filterDefs, $request)
    {
        $requestValues = new Data2Html_Values($request);
        if ($filterDefs) {
            $c = array();
            $def = new Data2Html_Values();
            foreach ($filterDefs as $k=>$v) {
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
                
                $type = $def->getString('type', 'string');
                // forced value
                $r = $def->toSql($this->db, 'value', $type, null);
                if ($r === null) {
                    // requested value
                    $r = $requestValues->toSql($this->db, $fName, $type, null);
                }
                if ($r !== null) {
                    array_push($c, "{$dbName} {$dbCheck} {$r}");
                }
            }
            if (count($c)) {
                return implode(' and ', $c);
            } else {
                return null;
            }
        }
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