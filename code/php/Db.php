<?php
namespace Data2Html;

use Data2Html\Data\Parse;
use Data2Html\DebugException;

abstract class Db
{
    use \Data2Html\Debug;
        
    /**  */
    protected $link;
    protected $db_type = 'abstract_Db';
    protected $init_query = array();

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
    abstract public function dateToSql($value);
    abstract public function toDate($value);

    /**
     * @param
     */
    public function __construct($parameters)
    {
        $this->link($parameters);
        $this->executeArray($this->init_query);
        if (isset($parameters['init_query'])) {
            $this->executeArray($parameters['init_query']);
        }
    }
    
    /**
     * Return a text for a select field width alias.
     */
    public function toSql($value, $type)
    {
        if (is_null($value) || $value === '') {
            return 'null';
        }
        switch ($type) {
            case 'number':
            case 'currency':            
                $r = '' . Parse::number($value);
                break;
            case 'integer':
                $r = '' . Parse::integer($value);
                break;
            case 'boolean':
                $r = Parse::boolean($value) ? '1' : '0';
                break;
            case 'string':
                $r = $this->stringToSql(Parse::string($value));
                break;
            case 'date':
                $date = Parse::date($value, null, 'Y-m-d\TH:i:sP');
                if ($date) {
                    $r = $this->dateToSql($date);
                } else {
                    $r = 'null';
                }
                break;
            default:
                throw new \Exception("`{$type}` is not defined.");
        }
        return $r;
    }
    public function toValue($v, $type)
    {
        if (is_null($v)) {
            return null;
        }
        switch ($type) {
            case 'number':
            case 'currency':
            case 'integer':
                $r = $v + 0; // convert to number
                break;
            case 'boolean':
                $r = !!$v;
                break;
            case 'string':
                $r = $v;
                break;
            case 'date':
                // Convert date to a string as "2015-04-15T08:39:19+01:00"
                $r = $this->toDate($v)->format('Y-m-d\TH:i:sP');
                break;
            default:
                throw new \Exception("`{$type}` is not defined.");
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

    public function getValue($query, $type, $not_found = null)
    {
        $row = $this->getRow($query, $not_found);
        if (is_array($row)) {
            if (count($row) > 0) {
                $k = array_keys($row);
                $result = $row[$k[0]];
            } else {
                $result = $not_found;
            }
        } else {
            $result = $row;
        }
        switch ($type) {
            case 'number':
            case 'currency':
                return Parse:number($result);
            case 'integer':
                return Parse:integer($result);
            case 'boolean':
                return Parse:boolean($result);
            case 'string':
                return Parse:string($result);
            case 'date':
                return Parse:date($result);
            default:
                throw new \Exception("`{$type}` is not defined.");
        }
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
