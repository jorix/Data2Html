<?php

abstract class Data2Html_Db
{
    /**  */
    protected $link;
    protected $db_type = 'abstract_Db';
    protected $init_query = array();
    protected $debug = false;

    /**
     * Establish connection to database and set it into $this->link
     *
     * @param $parameters array    Connection parameters.
     */
    abstract protected function link($parameters);
    
    /**
     * Executes a sql 'select'
     *
     * @param $sql string       Select sentence.
     *
     * @return resultSet        Result set
     */
    abstract public function query($query);

    /**
     * Executes a paged sql 'select'
     *
     * @param $sql string       Select sentence.
     * @param $pageStart int    Index of the start row of the page.
     * @param $pageSize int     Number of rows on a page to return.
     *
     * @return resultSet
     */
    abstract public function queryPage($sql, $pageStart = 1, $pageSize = 0);

    /**
     * Fetch one row of a result set as associative array.
     *
     * @param $result resultSet
     *
     * @return array|null    A associative array, null if no more records.
     */
    abstract public function fetch($result);
    
    /**
     * Close a resultSet
     *
     * @param $rs resultSet
     */
    abstract public function closeQuery($rs);
    
    /**
     * Executes a sql sentence
     *
     * @param $sql string   Sql sentence.
     *
     * @return int          Affected rows
     */
    abstract public function execute($sql);
    abstract public function lastInsertId();
    
    abstract public function startTransaction();
    abstract public function commit();
    abstract public function rollback();

    /**
     * Escape a string to use as into a sql sentence as string
     *
     * @param mixed $value
     *
     * @return string
     */
    abstract public function stringToSql($value);

    /**
     * @param jqGridLoader $loader
     */
    public function __construct($parameters)
    {
        $this->debug = Data2Html_Config::debug();
        $this->link($parameters);
        $this->executeArray($this->init_query);
        if (isset($parameters['init_query'])) {
            $this->executeArray($parameters['init_query']);
        }
    }
    
    /**
     * Return a text for a select field width alias.
     */
    public function toSql($value, $type, $strict = false)
    {
        if (is_null($value) || $value === '') {
            return 'null';
        }
        switch ($type) {
            case 'number':
            case 'currency':            
                $r = '' . Data2Html_Value::parseNumber($value, $strict);
                break;
            case 'integer':
            case 'boolean':
                $r = '' . Data2Html_Value::parseInteger($value, $strict);
                break;
            case 'string':
                $r = $this->stringToSql(
                    Data2Html_Value::parseString($value, $strict)
                );
                break;
            case 'date':
                $r = "'" . Data2Html_Value::parseDate($value, $strict)."'";
                break;
            default:
                throw new Exception(
                    "`{$type}` is not defined."
                );
        }
        return $r;
    }
    
    public function putAlias($alias, $fieldName)
    {
        return $fieldName . ' ' . $alias;
    }
    
    public function getRow($query, $not_found = null)
    {
        $rs = $this->query($query);
        $row = $rs->fetch();
        if (!$row) {
            return $not_found;
        }
        $this->closeQuery($rs);
        return $row;
    }
    
    public function executeArray($sqlArray)
    {
        $results = array();
        foreach ($sqlArray as $q) {
            $results[] = $this->execute($q);
        }
        return $results;
    }
}
