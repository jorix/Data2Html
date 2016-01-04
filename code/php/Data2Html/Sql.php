<?php

class Data2Html_Sql
{
    protected $db;
    public function __construct($db) {
        $this->db = $db;
    }
    public function getSelect($table, $colsDefs, $filterDefs = null, $orderBy = null)
    {
        $def = new Data2Html_Values();
        $dbfs = array();
        foreach ($colsDefs as $k=>$v) {
            $def->set($v);
            $dbName = $def->get('db', $k); // Here don't use getString
            if ($dbName !== null) {
                array_push($dbfs, $dbName);
            }
        }
        if (count($dbfs) === 0) {
            throw new Exception("No dada base fields defined.");
        }
        $query = 'select ' . implode(',', $dbfs);
        $query .= " from {$table}";
        if ($filterDefs) {
            $where = $this->getWhere($filterDefs, array());
            if ($where !== '') {
                $query .= " where {$where}";
            }
        }
        if ($orderBy) {
            $query .= " order by {$orderBy}";
        }
        return $query;
    }
    public function getWhere($filterDefs, $requestValues)
    {
        if ($filterDefs) {
            $c = array();
            $def = new Data2Html_Values();
            foreach ($filterDefs as $k=>$v) {
                $def->set($v);
                $fName = $def->getString('name');
                $type = $def->getString('type');
                $fCheck = $def->getString('check');
                if (
                    $type === null ||
                    $fName === null ||
                    $fCheck === null
                ) {
                    throw new Exception(
                        "getWhere(): Item {$k}=>'{$fName}' requires 'type' 'name' and 'check'."
                    );
                }
                switch ($fCheck) {
                case 'EQ':
                    $dbCheck = '=';
                    break;
                default:
                    throw new Exception(
                        "getWhere(): Check '{$fCheck}' on item {$k}=>'{$fName}' is not supported."
                    );
                    break;
                }
                $dbName = $def->getString('db', $fName); 
                // forced value
                $r = $def->toSql($this->db, 'value', $type, null);
                if ($r === null) {
                    // requested value
                    $r = $requestValues.toSql(
                        $this->db,
                        $fName.'_'.$fCheck,
                        $type
                    );
                }
                if ($r !== null) {
                    array_push($c, "{$dbName} {$dbCheck} {$r}");
                }
            }
            if (count($c)) {
                return implode(' and ', $c);
            }
        }
    }
}