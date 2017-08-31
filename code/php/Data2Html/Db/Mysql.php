<?php
/**
 * The recommended PDO driver for jqGridPHP.
 */
class Data2Html_Db_Pdo extends Data2Html_Db
{
    public function __construct($parameters, $options = array())
    {
        parent::__construct($parameters, $options);
        $dsn = $parameters['dsn'];
        $this->db_type = substr($dsn, 0, strpos($dsn, ':') + 1);
    }

    protected function link($parameters)
    {
        // Open link
        try {
            $link = new PDO(
                $parameters['dsn'],
                $parameters['user'],
                $parameters['pass'],
                (isset($parameters['options']) ? $parameters['options'] : array())
            );
            $link->setAttribute(PDO::ATTR_ERRMODE, 2);
        } catch (PDOException $e) {
            throw new Exception($e->getMessage(), $e->getCode());
        }
        $this->link = $link;
    }

    public function query($sql)
    {
        try {
            return $this->link->query($sql);
        } catch (PDOException $e) {
            throw new Data2Html_Exception(
                $e->getMessage(),
                array(
                    'sql' => explode("\n", $sql)
                ),
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
        $result = $this->query($sql);
        return $result->rowCount();
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
