<?php
/**
 * PDO
 */
namespace Data2Html\Db;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Data\Parse;

class Pdo extends \Data2Html\Db
{
    use \Data2Html\Debug;
    
    public function __construct($parameters, $options = [])
    {
        parent::__construct($parameters, $options);
        $dsn = $parameters['dsn'];
        $this->dbType = substr($dsn, 0, strpos($dsn, ':') + 1);
    }

    protected function link($parameters)
    {
        // Open link
        $pDx = new Lot($parameters);
        try {
            $link = new \PDO(
                $pDx->getString('dsn'),
                $pDx->getString('user'),
                $pDx->getString('password'),
                $pDx->get('options', [])
            );
            $link->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $link->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return $link;
    }

    public function query($sql)
    {
        try {
            return $this->link->query($sql);
        } catch (\PDOException $e) {
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
        return $result->fetch(\PDO::FETCH_ASSOC);
    }

    public function closeQuery($result) 
    {
        $result->closeCursor();
    }
    
    // Execute    
    public function execute($sql)
    {
        foreach ((array)$sql as $q) {
            $this->query($q);
        }
    }

    public function lastInsertId()
    {
        return $this->link->lastInsertId();
    }
    
    public function startTransaction()
    {
        $this->link->beginTransaction();
    }

    public function commit()
    {
        $this->link->commit();
    }

    public function rollback()
    {
        $this->link->rollback();
    }

    // Utils
    public function stringToSql($val)
    {
        return $this->link->quote($val);
    }
    
    public function dateToSql($date)
    {
        return "'" . $date->format('Y-m-d H:i:s') . "'";
    }
    
    public function toDate($val)
    {
        return Parse::date($val); // 'Y-m-d H:i:s
    }
}
