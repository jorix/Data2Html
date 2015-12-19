<?php
/**
 * Sample PostgreSQL driver
 * It's just an example - use PDO if you can
 */

class jqGrid_DB_Pgsql extends jqGrid_DB {
    protected $db_type = 'postgresql';

	protected function link($parameters) {
		// Open link
        try {
            $link = pg_connect($parameters['connection']);
            if(!$link) {
                throw new jqGrid_Exception_DB(pg_last_error());
            }
        } catch(Exception $e) {
            throw new jqGrid_Exception_DB($e->getMessage(), null);
        }
        $this->link = $link;
    }
    public function query($sql) {
        $result = pg_query($this->link, $sql);
        if(!$result) {
            throw new jqGrid_Exception_DB(pg_last_error(), array('query' => $query));
        }
        return $result;
    }

    public function fetch($result) {
        return pg_fetch_assoc($result);
    }
	public function insert($tblName, array $row) {
		$result = $this->insert_($tblName, $row, ' RETURNING *');
		return array_shift($this->fetch($result));
	}
    public function quote($val) {
        if(is_null($val)) {
            return $val;
        }
        return "'" . pg_escape_string($this->link(), $val) . "'";
    }

    public function rowCount($result)
    {
        return pg_affected_rows($result);
    }
}