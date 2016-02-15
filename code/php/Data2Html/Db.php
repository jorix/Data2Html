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
    public function __construct($parameters, $options = array())
    {
        if (isset($options['debug'])) {
            $this->debug = $options['debug'];
        }
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
     * Execute a query and return the array result.
     */
    public function getQueryArray($query, $fieldDefs, $pageStart = 1, $pageSize = 0)
    {
        try {
            $pageSize = intval($pageSize);
            $pageStart = intval($pageStart);
        } catch (Exception $e) {
            throw new Exception($e->getMessage()); //, array('query' => $sql), $e->getCode());
        };
        $dvDefs = new Data2Html_Collection($fieldDefs);
        $dvItem = new Data2Html_Collection();
        $types = array();
        $values = array();
        $fielsNames = array();
        $serverMatches = array();
        foreach ($fieldDefs as $k => $v) {
            $dvItem->set($v);
            $name = $dvItem->getString('name', $k);
            $types[$name] = $dvItem->getString('type');
            $fielsNames[$k] = $name;
            $value = $dvItem->getString('value');
            if ($value) {
                $values[$name] = $value;
                $matches = $dvItem->getArray('serverMatches');
                if ($matches) {
                    $serverMatches[$name] = $matches;
                }
            }
        }
        // Read rs
        $rows = array();
        $result = $this->queryPage($query, $pageStart, $pageSize);
        while ($r = $this->fetch($result)) {
            foreach ($r as $k => &$v) {
                switch ($types[$k]) {
                case 'integer':
                case 'number':
                case 'boolean':
                case 'currency':
                    $v = $v + 0; // convert to number
                    break;
                case 'date';
                    $date = DateTime::createFromFormat('Y-m-d H:i:s', $v);
                    // convert to a string as "2015-04-15T08:39:19+01:00"
                    $v = date('c', $date->getTimestamp());
                    unset($date);
                    break;
                }
            }
            $r += $values;
            foreach ($serverMatches as $mk => $mv) {
                $marches = $serverMatches[$mk];
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $r[$mk] = str_replace(
                        $matches[0][$i],
                        $r[$fielsNames[$matches[1][$i]]],
                        $r[$mk]
                    );
                }
            }
            $rows[] = $r;
        }
        $response = array(
            'pageStart' => $pageStart,
            'pageSize' => $pageSize,
            'types' => $types,
            'rows' => $rows
        );
        if ($this->debug) {
            $response['sql'] = $query;
            $response['serverMatches'] = $serverMatches;
        }
        return $response;
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

    protected function insert_($tblName, array $row, $post_sql = '')
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
    public function update($tblName, array $upd, $cond, $row_count = false)
    {
        $tblName = jqGrid_Utils::checkAlphanum($tblName);
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
        $q = "UPDATE $tblName SET ".implode(', ', $set).' WHERE '.($where ? implode(' AND ', $where) : $cond);

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
        $tblName = jqGrid_Utils::checkAlphanum($tblName);
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
