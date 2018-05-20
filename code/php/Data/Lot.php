<?php
namespace Data2Html\Data;

use Data2Html\ExeptionData;
use Data2Html\Data\Parse;
use Data2Html\Data\Lot;

class Lot
{
    public static function getItem($keys, &$array, $default = null)
    {
        if (!$keys) {
            return $default;
        } elseif (!is_array($keys)) {
            if (!is_array($array)) {
                return $default;
            } elseif (array_key_exists($keys, $array)) {
                return $array[$keys];
            } else {
                return $default;
            }
        } elseif (count($keys) === 1) {
            return self::getItem($keys[0], $array, $default);
        } else {
            $key0 = array_shift($keys);
            $item0 = self::getItem($key0, $array, $default);
            return self::getItem($keys, $item0, $default);
        }
    }

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
    
    public function get($itemKey, $default = null)
    {
        if (!is_array($this->values)) {
            throw new Data2Html_Exception(
                "Data2Html_Collection: 'values' must be an array, get '{$itemKey}' is not possible.",
                $this->values
            );
        }
        if (!is_string($itemKey) && !is_numeric($itemKey)) {
            throw new Data2Html_Exception(
                "Data2Html_Collection: 'itemKey' should be either a string or an integer.",
                array(
                    'itemKey' => $itemKey,
                    '->values' => $this->values
                )
            );
        }
        if (!array_key_exists($itemKey, $this->values)) {
            $this->throwNotExist($itemKey, $default);
            $val = $default;
        } else {
            $val = $this->values[$itemKey];
        }
        return $val;
    }
    
    public function getBoolean($itemKey, $default = null)
    {
        $val = $this->get($itemKey, $default);
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return !!$val;
    }
    public function getString($itemKey, $default = null)
    {
        $val = $this->get($itemKey, $default);
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Parse::string($val, $default, $this->strict);
    }
    
    public function getNumber($itemKey, $default = null)
    {
        $val = $this->get($itemKey, $default);
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Parse::number($val, $default, $this->strict);
    }
    
    public function getInteger($itemKey, $default = null)
    {
        $val = $this->get($itemKey, $default);
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Parse::integer($val, $default, $this->strict);
    }

    public function getDate(
        $itemKey,
        $default = null,
        $input_format = 'Y-m-d H:i:s'
    ) {
        $val = $this->get($itemKey, $default);
        if (is_null($val) && is_null($default) ) {
            return null;
        }
        return Parse::date($val, $default, $input_format, $this->strict);
    }
        
    public function getArray($itemKey, $default = null)
    {
        $val = $this->get($itemKey, $default);
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

    public function getLot($itemKey, $default = null)
    {
        $val = $this->getArray($itemKey, $default);
        if (is_null($val)) {
            return null;
        } else {
            return new Lot($val, $this->required);
        }
    }

    protected function throwNotExist($itemKey, $default) 
    {
        if ($this->required && $default === null) {
            throw new ExceptionData(
                "Key '{$itemKey}' don't exist on collection.",
                array(
                    'key' => $itemKey,
                    'collection' => $this->values
                )
            );
        }
    }
}
