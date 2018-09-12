<?php
namespace Data2Html;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Data\Parse;

abstract class Db
{
    use \Data2Html\Debug;
        
    /**  */
    protected $link;
    protected $dbType = 'abstract_Db';
    protected $init_query = [];

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

    public static function create($dbConfig) {
        if (!$dbConfig || !is_array($dbConfig)) {
            throw new DebugException(
                'Db config not present or not valid associative array.',
                $dbConfig
            );
        }
        $dbClass = Lot::getItem('class', $dbConfig);
        if (!$dbClass || !is_string($dbClass)) {
            throw new DebugException(
                '"class" config parameter is not present on database configuration.',
                $dbConfig
            );
        }
        $dbClass = '\\Data2Html\\Db\\' . $dbClass;
        return new $dbClass($dbConfig);
    }
    
    /**
     * @param
     */
    public function __construct($parameters)
    {
        $this->link = $this->link($parameters);
        if (count($this->init_query)) {
            $this->execute($this->init_query);
        }
        if (array_key_exists('init_query', $parameters)) {
            $this->execute($parameters['init_query']);
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
            case 'datetime':
            // $d->setTimezone(new DateTimeZone("UTC"));
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
            case 'datetime':
                $r = $this->toDate($v);
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
    
    public function getRow($query, $notFound = null)
    {
        $rs = $this->query($query);
        $row = $this->fetch($rs);
        if (!$row) {
            return $notFound;
        }
        $this->closeQuery($rs);
        return $row;
    }

    public function getValue($query, $type, $notFound = null)
    {
        $row = $this->getRow($query, $notFound);
        if (is_array($row)) {
            if (count($row) > 0) {
                $k = array_keys($row);
                $result = $row[$k[0]];
            } else {
                $result = $notFound;
            }
        } else {
            $result = $row;
        }
        return Parse::value($result, $type);
    }
    
    public function executeArray($sqlArray)
    {
        foreach ((array)$sqlArray as $q) {
            $this->execute($q);
        }
    }
}
