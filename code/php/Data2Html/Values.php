<?php

class Data2Html_Values
{
    protected $values = null;
    protected $throwError = false;
    public function __construct(&$values = array(), $throwError = false)
    {
        $this->values = &$values;
        $throwError = $throwError;
    }
    
    public function set(&$values)
    {
        $this->values = &$values;
    }
    
    public function getValue($itemKey, $type, $default = null, $allowNull = false)
    {
        switch ($type) {
            case 'number':
            case 'currency':            
                return $this->getNumber($itemKey, $default, $allowNull);
            case 'integer':
                return $this->getInteger($itemKey, $default, $allowNull);
            case 'string':
                return $this->getString($itemKey, $default, $allowNull);
            case 'date':
                return $this->getDate($itemKey, $default, $allowNull);
            case 'array':
                return $this->getArray($itemKey, $default, $allowNull);
            default:
                throw new Exception(
                    "getValue(): type '{$type}' used to get '{$itemKey}' is not defined."
                );
        }
    }

    public function toSql($db, $itemKey, $type, $default = 'null')
    {
        switch ($type) {
            case 'number':
            case 'currency':            
                $r = $this->getNumber($itemKey);
                break;
            case 'integer':
                $r = $this->getInteger($itemKey);
                break;
            case 'string':
                $r = $this->getString($itemKey);
                if ($r !== null) {
                    $r = $db->toSql($r);
                }
                break;
            case 'date':
                $r = $this->getDate($itemKey);
                if ($r !== null) {
                    $r = "'{$r}'";
                }
                break;
            default:
                throw new Exception(
                    "toSql(): type '{$type}' used to get '{$itemKey}' is not defined."
                );
                break;
        }
        return is_null($r) ? $default : $r;
    }

    public function getString($itemKey, $default = null, $allowNull = false)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            return is_null($default) ? null : strval($default);
        }
        $val = $this->values[$itemKey];
        if (is_null($val) && $allowNull) {
            return null;
        }
        return strval($val);
    }
    
    public function getNumber($itemKey, $default = null, $allowNull = false)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val) && $allowNull) {
                return null;
            }
        }
        if (!is_numeric($val)) {
            if ($this->throwError) {
                throw new Exception(
                    "getNumber(): The '{$itemKey}' is not a number."
                );
            }
            return null;
        }

        return $val + 0;
    }
    
    public function getInteger($itemKey, $default = null, $allowNull = false)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val) && $allowNull) {
                return null;
            }
        }
        if (!is_numeric($val) || !is_int($val+0)) {
            if ($this->throwError) {
                throw new Exception(
                    "getInteger(): The '{$itemKey}' is not a integer."
                );
            }
            return null;
        }

        return $val + 0;
    }

    public function getDate(
        $itemKey,
        $default = null,
        $allowNull = false,
        $input_format = 'Y-m-d H:i:s'
    ) {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val) && $allowNull) {
                return null;
            }
        }
        if ($val) {
            $d = date_parse_from_format($input_format, $val);
        }
        if (!$val || $d['error_count'] !== 0) {
            if ($this->throwError) {
                throw new Exception(
                    "getDate(): The '{$itemKey}' is not a date."
                );
            }
            return null;
        }
        $date = new DateTime();
        $date->setDate($d['year'], $d['month'], $d['day']);
        $date->setTime($d['hour'], $d['minute'], $d['second']);
        return $date;
        //return $date_o->format('Y-m-d');
    }
        
    public function getArray($itemKey, $default = null, $allowNull = false)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val) && $allowNull) {
                return null;
            }
        }
        if (!is_array($val)) {
            if ($this->throwError) {
                throw new Exception(
                    "getArray(): The '{$itemKey}' is not a array."
                );
            }
            return null;
        }
        return $val;
    }
}
