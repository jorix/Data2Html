<?php
class Data2Html_Controller_SqlEdit
{
    protected $culprit = '';
    protected $debug = false;
    
    protected $db;
    protected $set;
    protected $result = array();

    public function __construct($db, $set) {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "SqlEdit for " . $set->getCulprit();
        
        $this->db = $db;
        $this->set = $set;
        
        $this->result['table'] = $this->set->getTableName();
    }

    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = $result;
        }
        Data2Html_Utils::dump($this->culprit, $subject);
    }
    
    /**
     *
     * @return int      Rows into table with this keys
     */
    public function checkSingleRow($keysReq)
    {
        if (!$keysReq) {
            throw new Data2Html_Exception(
                "{$this->culprit} setKeys(): Keys are required.",
                $this->result
            );
        }
        $this->result['where'] = $this->getWhereByKeys($keysReq);
        $count = $this->db->getRow(
            "select {$this->db->putAlias('rows','count(*)')}
                from {$this->result['table']}
                where {$this->result['where']}"
        );
        if (!$count) {
            throw new Data2Html_Exception(
                "{$this->culprit} checkSingleRow(): No rows with this keys.",
                array(
                    'keys' => $keysReq,
                    'result' => $this->result
                )
            );
        }
        if ($count['rows'] > 1) {
            throw new Data2Html_Exception(
                "{$this->culprit} checkSingleRow(): The keys has {$count['rows']} rows, only single row is valid.",
                array(
                    'keys' => $keysReq,
                    'result' => $this->result
                )
            );
        } 
    }
    
    protected function getWhereByKeys($keysReq)
    {   
        if (!is_array($keysReq)) {
            $keysReq = array($keysReq);
        }
        $keysDf = $this->set->getKeys();
        if (count($keysReq) !== count($keysDf)) {
            throw new Data2Html_Exception(
                "{$this->culprit} getWhereByKeys(): Requested keys not match number of keys.",
                array(
                    'keysDf' => $keysDf,
                    'keysReq' => $keysReq
                )
            );
        }
        $items = $this->set->getItems();
        $ix = 0;
        $c = array();
        foreach ($keysDf as $k => $v) {
            $db = $items[$k]['db'];
            $req = $keysReq[$ix];
            if ($req === '' || $req === null) {
                array_push($c, "{$db} is null");
            } else {
                $type = $items[$k]['type'];
                $r = Data2Html_Value::toSql($this->db, $req, $type);
                array_push($c, "{$db} = {$r}");
            }
            $ix++;
        }
        return implode(' and ', $c);
    }
    
    public function getUpdate($values)
    {
        if (!array_key_exists('where', $this->result)) {
            throw new Data2Html_Exception(
                "{$this->culprit} getUpdate(): Keys are required, use checkSingleRow() before getUpdate().",
                $this->result
            );
        }
        $assigns = array();
        $items = $this->set->getItems();
        foreach($values as $k => $v) {
            if (array_key_exists($k, $items)) {
                $item = $items[$k];
                if (array_key_exists('db', $item)) {
                    $type = Data2Html_Value::getItem($item, 'type', 'string');
                    $assigns[] =  $item['db'] . ' = ' . $this->db->toSql($v, $type);
                }
            }
        }
        if (count($assigns) === 0) {
            throw new Data2Html_Exception(
                "{$this->culprit} getUpdate(): Nothing to SET.",
                array(
                    'items' => $items,
                    'values' => $values,
                    'result' => $this->result
                )
            );
        }
        return "UPDATE {$this->result['table']}
                    SET " . implode(",\n", $assigns) . "
                    WHERE {$this->result['where']}";
    }
}
