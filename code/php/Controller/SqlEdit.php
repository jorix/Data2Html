<?php
namespace Data2Html\Controller;

use Data2Html\DebugException;
use Data2Html\Data\Lot;

class SqlEdit
{   
    use \Data2Html\Debug;
    
    protected $db;
    protected $lkSet;
    protected $result = array();

    public function __construct($db, $lkSet) {        
        $this->db = $db;
        $this->lkSet = $lkSet;
        
        $this->result['table'] = $this->lkSet->getTableName();
    }
    
    /**
     *
     * @return int      Rows into table with this keys
     */
    public function checkSingleRow($keysReq)
    {
        if (!$keysReq) {
            throw new DebugException("Keys are required.", $this->result);
        }
        $this->result['where'] = $this->getWhereByKeys($keysReq);
        $count = $this->db->getValue(
            "select count(*) from {$this->result['table']}
                where {$this->result['where']}",
            'integer'
        );
        if (!$count) {
            throw new DebugException("No rows with this keys.", [
                'keys' => $keysReq,
                'result' => $this->result
            ]);
        }
        if ($count > 1) {
            throw new DebugException("The keys has {$count} rows, only single row is valid.", [
                'keys' => $keysReq,
                'result' => $this->result
            ]);
        } 
    }
    
    protected function getWhereByKeys($keysReq)
    {   
        if (!is_array($keysReq)) {
            $keysReq = array($keysReq);
        }
        $keysDf = $this->lkSet->getLinkedKeys();
        if (count($keysReq) !== count($keysDf)) {
            throw new DebugException("Requested keys not match number of keys.", [
                    'keysDf' => $keysDf,
                    'keysReq' => $keysReq
            ]);
        }
        $items = $this->lkSet->getLinkedItems();
        $ix = 0;
        $c = array();
        foreach ($keysDf as $k => $v) {
            $dbFieldName = $items[$k]['db'];
            $req = $keysReq[$ix];
            if ($req === '' || $req === null) {
                array_push($c, "{$dbFieldName} is null");
            } else {
                $type = $items[$k]['type'];
                $r = $this->db->toSql($req, $type);
                array_push($c, "{$dbFieldName} = {$r}");
            }
            $ix++;
        }
        return implode(' and ', $c);
    }
    
        
    public function getInsert($values)
    {
        if (array_key_exists('where', $this->result)) {
            throw new DebugException("Keys are not required.", $this->result);
        }
        $assigns = array();
        $names = array();
        $items = $this->lkSet->getLinkedItems();
        foreach($values as $k => $v) {
            if (array_key_exists($k, $items)) {
                $item = $items[$k];
                if (isset($item['db'])) {
                    if (Lot::getItem('key', $item) !== 'autoKey') {
                        $type = Lot::getItem('type', $item, 'string');
                        $names[] = $item['db'];
                        $assigns[] =  $this->db->toSql($v, $type);
                    }
                }
            }
        }
        if (count($assigns) === 0) {
            throw new DebugException("Nothing to SET.", [
                'items' => $items,
                'values' => $values,
                'result' => $this->result
            ]);
        }
        return "INSERT INTO {$this->result['table']}\n(". 
                implode(', ', $names) . 
            ") \nVALUES (\n" .
                implode(",\n", $assigns) .
            "\n)";
    }
    
    public function getUpdate($values)
    {
        if (!array_key_exists('where', $this->result)) {
            throw new DebugException("Keys are required using getUpdate().",
                $this->result
            );
        }
        $assigns = array();
        $items = $this->lkSet->getLinkedItems();
        foreach($values as $k => $v) {
            if (array_key_exists($k, $items)) {
                $item = $items[$k];
                if (isset($item['db'])) {
                    $type = Lot::getItem('type', $item, 'string');
                    $assigns[] =  $item['db'] . ' = ' . $this->db->toSql($v, $type);
                }
            }
        }
        if (count($assigns) === 0) {
            throw new DebugException("Nothing to SET.", [
                'items' => $items,
                'values' => $values,
                'result' => $this->result
            ]);
        }
        return "UPDATE {$this->result['table']}" .
               "\nSET\n  " . implode(",\n  ", $assigns) . 
               "\nWHERE {$this->result['where']}";
    }

    public function getDelete()
    {
        if (!array_key_exists('where', $this->result)) {
            throw new DebugException("Keys are required using getDelete().",
                $this->result
            );
        }
        return 
            "DELETE FROM {$this->result['table']} WHERE {$this->result['where']}";
    }
}
