<?php
/**
 * The recommended PDO driver for jqGridPHP.
 */
class Data2Html_Db_Pdo extends Data2Html_Db
{
    public function __construct($parameters)
    {
        parent::__construct($parameters);
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

    public function queryPage($sql, $pageStart = 0, $pageSize = 0)
    {
        if ($pageStart >= 0 && $pageSize > 0) {
            $sql .= " LIMIT {$pageSize} OFFSET {$pageStart}";
        }

        return $this->query($sql);
    }

    public function query($sql)
    {
        try {
            return $this->link->query($sql);
        } catch (PDOException $e) {
            throw new jqGrid_Exception_DB($e->getMessage(), array('query' => $sql), $e->getCode());
        };
    }

    public function fetch($result)
    {
        return $result->fetch(PDO::FETCH_ASSOC);
    }

    public function quote($val)
    {
        if (is_null($val)) {
            return;
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
