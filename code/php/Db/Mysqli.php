<?php
/**
 * mysqli
 */
namespace Data2Html\Db;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Data\Parse;

class Mysqli extends \Data2Html\Db
{
    protected $dbType = 'Mysqli';

    protected function link($parameters)
    {
        // Open link
        $pDx = new Lot($parameters);
        try {
            $link = new \mysqli(
                $pDx->getString('host'),
                $pDx->getString('user'),
                $pDx->getString('password'),
                $pDx->getString('database')
            );
            if (\mysqli_connect_errno())
                throw new \Exception('Unable to connect to database. ' . \mysqli_connect_error());
        } catch (\Exception $e) {
            throw new \Exception($e->getMessage(), $e->getCode());
        }
        return $link;
    }

    public function query($sql)
    {
        try {
            $result = $this->link->query($sql);
            if ($result === false) {
                throw new DebugException(
                    $this->link->errno . '-' . $this->link->error,
                    ['sql' => explode("\n", $sql)]
                );
            }
            return $result;
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
        return $result->fetch_assoc();
    }

    public function closeQuery($result) 
    {
        $result->close();
    }
    
    // Execute    
    public function execute($sql)
    {
        foreach ((array)$sql as $q) {
            $result = $this->link->query($q);
            if($result instanceof \mysqli_result) {
                $result->close();
            }
        }
    }
    
    public function lastInsertId()
    {
        return $this->link->insert_id;
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

    // Utils
    public function stringToSql($val)
    {
        return "'" . $this->link->real_escape_string($val) . "'";
    }
    
    public function dateToSql($date)
    {
        return "'" . $date->format('Y-m-d H:i:s') . "'";
    }
    
    public function toDate($val)
    {
        return Parse::date($val); // 'Y-m-d H:i:s'
    }
}
