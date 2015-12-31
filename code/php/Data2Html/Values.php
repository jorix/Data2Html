<?php
class Data2Html_Values {
    protected $values = null;
    public function __construct(&$values = array()) {
        $this->values = &$values;
    }
    public function set(&$values) {
        $this->values = &$values;
    }
    public function get($itemKey, $default=null) {
        if (!array_key_exists($itemKey, $this->values)) {
            return $default;
        }
        return $this->values[$itemKey];
    }
    public function getString($itemKey, $default=null) {
        if (!array_key_exists($itemKey, $this->values)) {
            return ( is_null($default) ? null : strval($default) );
        }
        $val = $this->values[$itemKey];
        return strval($val);
    }
    public function getNumber($itemKey, $default=null) {
        $val = $this->get($itemKey, $default);
        if (!is_numeric($val)) {
            return null;
        }
        return $val + 0;
    }
    public function getInteger($itemKey, $default=null) {
        $val = $this->getNumber($itemKey, $default);
        if (!is_int($val)) {
            return (
                is_numeric($default) && is_int($default+0) ? 
                    intval($default) : null
            );
        }
        return $val;
    }
}