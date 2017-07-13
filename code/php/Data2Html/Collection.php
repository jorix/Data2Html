<?php

class Data2Html_Collection
{
    protected $values = null;
    protected $strict = false;
    protected $required = false;
    public function __construct(&$values = array(), $required = false)
    {
        $this->set($values);
        $this->required = $required;
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

    public function getBoolean($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            $this->throwNotExist($itemKey, $default);
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
        }
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return !!$val;
    }
    public function getString($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            $this->throwNotExist($itemKey, $default);
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
        }
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Data2Html_Value::parseString($val, $this->strict);
    }
    
    public function getNumber($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            $this->throwNotExist($itemKey, $default);
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
        }
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Data2Html_Value::parseNumber($val, $this->strict);
    }
    
    public function getInteger($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            $this->throwNotExist($itemKey, $default);
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
            
        }
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Data2Html_Value::parseInteger($val, $this->strict);
    }

    public function getDate(
        $itemKey,
        $default = null,
        $input_format = 'Y-m-d H:i:s'
    ) {
        if (!array_key_exists($itemKey, $this->values)) {
            $this->throwNotExist($itemKey, $default);
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
        }
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Data2Html_Value::parseDate($val, $input_format, $this->strict);
    }
        
    public function getArray($itemKey, $default = null)
    {
        if (!array_key_exists($itemKey, $this->values)) {
            $this->throwNotExist($itemKey, $default);
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
        }
        if (is_null($val) && is_null($default) ) {
            return null;
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

    public function getCollection($itemKey, $default = null)
    {
        $val = $this->getArray($itemKey, $default);
        if (is_null($val)) {
            if (is_array($default)) {
                return new Data2Html_Collection($val, $default);
            } else {
                return null;
            }
        } else {
            return new Data2Html_Collection($val, $this->required);
        }
    }
    protected function throwNotExist($itemKey, $default) 
    {
        if ($this->required && $default === null) {
            throw new Data2Html_Exception(
                "Key '{$itemKey}' don't exist on collection.",
                array(
                    'key' => $itemKey,
                    'collection' => $this->values
                )
            );
        }
    }
}
