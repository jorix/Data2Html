<?php

class Data2Html_Array
{
    public static function get($array, $keys)
    {
        if (!$keys) {
            return null;
        }
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        $aux = $array;
        foreach ($keys as $v) {
            if (!is_array($aux)) {
                return null;
            }
            if (!array_key_exists($v, $aux)) {
                return null;
            }
            $aux = $aux[$v];
        }
        return $aux;
    }
 }
 