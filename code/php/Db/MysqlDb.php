<?php
/**
 * mysqli
 */
namespace Data2Html\Db;

use Data2Html\DebugException;

class MysqlDb extends \Data2Html\Db
{
    protected $dbType = 'Mysql';
    
    public function __construct($parameters, $options = [])
    {
        parent::__construct($parameters, $options);
    }

    protected function link($parameters)
    {
        // Open link
        try {
            $link = new \mysqli(
                $parameters['host'],
                $parameters['user'],
                $parameters['pass'],
                $parameters['dbname'],
            );
            if (mysqli_connect_errno())
                throw new \Exception('Unable to connect to database. ' . mysqli_connect_error());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        $this->link = $link;
    }

    public function query($sql)
    {
        try {
            return $this->link->query($sql);
        } catch (\Exception $e) {
            throw new DebugException(
                $e->getMessage(),
                ['sql' => explode("\n", $sql)],
                $e->getCode()
            );
        };
    }

    public function queryPage($sql, $pageStart = 1, $pageSize = 0)
    {
        if ($pageStart > 0) {
            $offset = $pageStart - 1;
        } else {
            $offset = 0;
        }
        if ($pageSize > 0) {
            $sql .= " LIMIT {$pageSize} OFFSET {$offset}";
        }
        return $this->query($sql);
    }
    
    public function fetch($result)
    {
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    public function closeQuery($rs) 
    {
        $rs->closeCursor();
    }
    
    // Execute    
    public function execute($sql)
    {
        try {
            $result = $this->query($sql);
            return $result->rowCount();
        } catch (\Exception $e) {
            throw new DebugException(
                $e->getMessage(),
                ['sql' => explode("\n", $sql)],
                $e->getCode()
            );
        };
    }
    
    public function startTransaction()
    {
        $this->execute("START TRANSACTION;");
    }

    public function commit()
    {
        $this->execute("COMMIT;");
    }

    public function rollback()
    {
        $this->execute("ROLLBACK;");
    }

    public function lastInsertId();
        return $this->link->lastInsertId();
    }

    // Utils
    public function stringToSql($val)
    {
        if (is_null($val)) {
            return 'null';
        }
        return $this->link->quote($val);
    }
}
