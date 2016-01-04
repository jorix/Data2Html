<?php

class Data2Html_Values
{
    protected $values = null;
    public function __construct(&$values = array())
    {
        $this->values = &$values;
    }
    
    public function set(&$values)
    {
        $this->values = &$values;
    }
    
    public function get($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            return $default;
        }
        return $this->values[$itemKey];
    }
    
    public function getByType($itemKey, $type, $default = null)
    {
        switch ($type) {
            case 'number':
            case 'currency':            
                return $this->getNumber($itemKey, $default);
            case 'integer':
                return $this->getInteger($itemKey, $default);
            case 'string':
                return $this->getString($itemKey, $default);
            case 'date':
                return $this->getDate($itemKey, $default);
            default:
                throw new Exception(
                    "getByType(): type '{$type}' used to get '{$itemKey}' is not defined."
                );
        }
    }
    public function toSql($db, $itemKey, $type, $default = 'null')
    {
        switch ($type) {
            case 'number':
            case 'currency':            
                return $this->getNumber($itemKey, $default);
            case 'integer':
                return $this->getInteger($itemKey, $default);
            case 'string':
                $r = $this->getString($itemKey);
                if ($r === null) {
                    return $default;
                } else {
                    return $db->toSql($r);
                }
            case 'date':
                $r = $this->getDate($itemKey, $default);
                if ($r === null) {
                    return $default;
                } else {
                    return "'{$r}'";
                }
            default:
                throw new Exception(
                    "toSql(): type '{$type}' used to get '{$itemKey}' is not defined."
                );
        }
    }
    public function getString($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            return  is_null($default) ? null : strval($default);
        }
        $val = $this->values[$itemKey];
        return strval($val);
    }
    
    public function getNumber($itemKey, $default = null)
    {
        $val = $this->get($itemKey, $default);
        if (!is_numeric($val)) {
            return;
        }

        return $val + 0;
    }
    
    public function getInteger($itemKey, $default = null)
    {
        $val = $this->getNumber($itemKey, $default);
        if (!is_int($val)) {
            return
                is_numeric($default) && is_int($default + 0) ?
                    intval($default) : null
            ;
        }

        return $val;
    }

    public function getDate(
        $itemKey,
        $default = null,
        $input_format = 'Y-m-d H:i:s'
    ) {
        $val = $this->get($itemKey, $default);
        if ($val) {
            $d = date_parse_from_format($input_format, $val);
        }
        if (!$val || $d['error_count'] !== 0) {
            if ($default === null) {
                return null;
            }
            $d = date_parse_from_format('Y-m-d', $default);
            if ($d['error_count'] !== 0) {
                return null;
            }
        }
        $date = new DateTime();
        $date->setDate($d['year'], $d['month'], $d['day']);
        $date->setTime($d['hour'], $d['minute'], $d['second']);
        return $date;
        //return $date_o->format('Y-m-d');
    }
}
