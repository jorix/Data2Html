<?php

class Data2Html_Collection
{
    protected $values = null;
    protected $strict = false;
    public function __construct(&$values = array(), $strict = false)
    {
        $this->set($values);
        $this->strict = $strict;
    }

    public function set(&$values)
    {
        if (!$values) {
            $this->values = array();
        } elseif (is_object($values)) {
            // $this->values = get_object_vars($values);
            $array = array();
            foreach ($values as $k => $v) {
                $array[$k] = $v;
            }
            $this->values = $array;
        } else {
            $this->values = &$values;
        }
    }
    public function getValues() 
    {
        return $this->values;
    }
    public function getValue($itemKey, $type, $default = null)
    {
        switch ($type) {
            case 'number':
            case 'currency':            
                return $this->getNumber($itemKey, $default);
            case 'integer':
                return $this->getInteger($itemKey, $default);
            case 'boolean':
                return $this->getBoolean($itemKey, $default);
            case 'string':
            case 'email':
            case 'url':
                return $this->getString($itemKey, $default);
            case 'date':
                return $this->getDate($itemKey, $default);
            case 'array':
                return $this->getArray($itemKey, $default);
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
            case 'boolean':
                $r = $this->getInteger($itemKey);
                break;
            case 'string':
            case 'email':
            case 'url':
                $r = $this->getString($itemKey);
                if ($r !== null) {
                    $r = $db->stringToSql($r);
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
                    "Type `{$type}` is not supported."
                );
                break;
        }
        return is_null($r) ? $default : $r;
    }

    public function getBoolean($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            return is_null($default) ? null : !!$default;
        }
        $val = $this->values[$itemKey];
        if (is_null($val)) {
            return null;
        }
        return !!$val;
    }
    
    public function getString($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            return is_null($default) ? null : strval($default);
        }
        $val = $this->values[$itemKey];
        if (is_null($val)) {
            return null;
        }
        return Data2Html_Value::parseString($val, $this->strict);
    }
    
    public function getNumber($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val)) {
                return null;
            }
        }
        return Data2Html_Value::parseNumber($val, $this->strict);
    }
    
    public function getInteger($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val)) {
                return null;
            }
        }
        return Data2Html_Value::parseInteger($val, $this->strict);
    }

    public function getDate(
        $itemKey,
        $default = null,
        $input_format = 'Y-m-d H:i:s'
    ) {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val)) {
                return null;
            }
        }
        return Data2Html_Value::parseDate($val, $input_format, $this->strict);
    }
        
    public function getArray($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            if (is_null($default)) {
                return null;
            }
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            if (is_null($val)) {
                return null;
            }
        }
        if (!is_array($val) && !is_object($val)) {
            if ($this->strict) {
                throw new Exception(
                    "getArray(): The '{$itemKey}' is not a array."
                );
            }
            return null;
        }
        return $val;
    }
    public function getArrayValues($itemKey, $default = null)
    {
        $val = $this->getArray($itemKey, $default);
        if (is_null($val)) {
            return null;
        } else {
            return new Data2Html_Collection($val);
        }
    }
}
