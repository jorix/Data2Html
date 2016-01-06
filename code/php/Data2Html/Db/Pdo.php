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

    public function query($sql)
    {
        try {
            return $this->link->query($sql);
        } catch (PDOException $e) {
            throw new Data2Html_Exception(
                $e->getMessage(),
                array(
                    'sql' => $sql
                ),
                $e->getCode()
            );
        };
    }

    public function fetch($result)
    {
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    public function toSql($val)
    {
        if (is_null($val)) {
            return 'null';
        }
        return $this->link->quote($val);
    }

    public function rowCount($result)
    {
        return $result->rowCount();
    }

    public function insert($tblName, array $row)
    {
        $this->insert_($tblName, $row);

        return $this->link->lastInsertId();
    }
}
