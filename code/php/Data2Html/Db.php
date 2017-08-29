<?php

abstract class Data2Html_Db
{
    /**  */
    protected $link;
    protected $db_type = 'abstract_Db';
    protected $init_query = array();
    protected $debug = false;

    /**
     * Establish connection to database.
     *
     * @abstract
     */
    abstract protected function link($parameters);

    /**
     * Execute SQL-query.
     *
     * @abstract
     *
     * @param $query
     *
     * @return resource
     */
    abstract public function queryPage($sql, $pageStart = 1, $pageSize = 0);
    abstract public function query($query);
    abstract public function startTransaction();
    abstract public function commit();
    abstract public function rollback();

    /**
     * Fetch-assoc one row.
     *
     * @abstract
     *
     * @param resource $result
     *
     * @return array
     */
    abstract public function fetch($result);

    /**
     * 
     *
     * @abstract
     *
     * @param mixed $value
     *
     * @return string
     */
    abstract public function stringToSql($value);

    /**
     * Like PDO::rowCount and *_affected_rows.
     *
     * @abstract
     *
     * @param resource $result
     *
     * @return int
     */
    abstract public function rowCount($result);

    /**
     * @param jqGridLoader $loader
     */
    public function __construct($parameters)
    {
        $this->debug = Data2Html_Config::debug();
        $this->link($parameters);
        $this->queries($this->init_query);
        if (isset($parameters['init_query'])) {
            $this->queries($parameters['init_query']);
        }
    }

    /**
     * Execute a list of queries.
     */
    public function queries($queries)
    {
        foreach ($queries as $q) {
            $this->query($q);
        }
    }
    
    /**
     * Return a text for a select field width alias.
     */
    public function putAlias($alias, $fieldName)
    {
        return $fieldName . ' ' . $alias;
    }
    
    /**
     * INSERT query wrapper.
     *
     * @param string $tblName - table name
     * @param array  $ins     - key => value pairs
     *
     * @return the last insert id
     */
    abstract public function insert($tblName, array $row);

    protected function insert_($tblName, array $row)
    {
        $row = $this->cleanArray($row, $types);
        $q = "INSERT INTO {$tblName} (".
            implode(', ', array_keys($row)).
        ') VALUES ('.
            implode(', ', $row).
        ')';

        return $this->query($q);
    }

    /**
     * UPDATE query wrapper
     * Be careful with string $cond - it is not clean!
     *
     * @param string $tblName   - table name
     * @param array  $upd       - key => value pairs
     * @param mixed  $cond      - key => value pairs, integer (for id=) or string
     * @param bool   $row_count - if true - return row count (affected_rows)
     *
     * @return mixed
     */
    public function update($tblName, array $data, $keys, $row_count = false)
    {
        $upd = $this->cleanArray($upd);

        $set = array();
        $where = array();

        #Build 'set'
        foreach ($upd as $k => $v) {
            $set[] = $k.'='.$v;
        }

        #Build 'where'
        if (is_numeric($cond)) {
            //simple id=

            $where[] = 'id='.intval($cond);
        } elseif (is_array($cond)) {
            $cond = $this->cleanArray($cond);

            foreach ($cond as $k => $v) {
                if ($v === 'NULL') {
                    $where[] = $k.' IS NULL';
                } else {
                    $where[] = $k.'='.$v;
                }
            }
        }

        #Execute
        $q = "UPDATE {$tblName} SET " .
            implode(', ', $set) . 
            ' WHERE '.($where ? implode(' AND ', $where) : $cond);
echo $q;
return;
        $result = $this->query($q);

        if ($row_count) {
            return $this->rowCount($result);
        }

        return $result;
    }

    /**
     * DELETE query wrapper.
     *
     * @param string $tblName - table name
     * @param mixed  $cond    - key => value pairs, integer (for id=) or string
     *
     * @return resource
     */
    public function delete($tblName, $cond)
    {
        $where = array();

        #Build 'where'
        if (is_numeric($cond)) {
            $where[] = 'id='.intval($cond);
        } elseif (is_array($cond)) {
            $cond = $this->cleanArray($cond);

            foreach ($cond as $k => $v) {
                if ($v === 'NULL') {
                    $where[] = $k.' IS NULL';
                } else {
                    $where[] = $k.'='.$v;
                }
            }
        }

        $q = "DELETE FROM $tblName WHERE ".($where ? implode(' AND ', $where) : $cond);

        $result = $this->query($q);

        return $result;
    }
}
