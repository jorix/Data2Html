<?php
namespace Data2Html\Data;

use Data2Html\Data\DateTime;

class Parse
{
        
    public static function string($value, $default = null, $strict = false)
    {
        if (is_array($value)) {
            throw new \Exception(
                "Value is not a string, is a array."
            );
        }
        if (is_object($value)) {
            throw new \Exception(
                "Value is not a string, is a object."
            );
        }
        if (is_null($value)) {
            if ($strict) {
                throw new \Exception(
                    "Value is not a string, is null."
                );
            }
            if ($default === null) {
                return null;
            }
            return self::string($default, null, $strict);
        }
        return strval($value);
    }
    
    public static function number($value, $default = null, $strict = false)
    {
        if (!is_numeric($value)) {
            if ($strict) {
                throw new \Exception(
                    "Value `{$value}`  is not a number."
                );
            }
            if ($default === null) {
                return null;
            }
            return self::number($default, null, $strict);
        }
        return $value + 0;
    }
    
    public static function integerArray($value, $default = null, $strict = false)
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', $value));
        } elseif (!is_array($value)) {
            $value = [$value];
        }
        $response = [];
        foreach ($value as $v) {
            $vv = self::integer($v, null, $strict);
            if ($vv === null) {
                $response =  $default;
                break;
            }
            $response[] = $vv;
        }
        return $response;
    }
    
    public static function integer($value, $default = null, $strict = false)
    {
        if (!is_numeric($value) || !is_int($value+0)) {
            if ($strict) {
                throw new \Exception(
                    "Value `{$value}` is not a integer."
                );
            }
            if ($default === null) {
                return null;
            }
            return self::integer($default, null, $strict);
        }
        return $value + 0;
    }
    
    public static function boolean($value, $default = null, $strict = false)
    {
        if (!is_numeric($value)) {
            if (is_string($value)) {
                if (preg_match('/^\s*true\s*$/i', $value)) {
                    $value = true;
                } elseif(preg_match('/^\s*false\s*$/i', $value)) {
                    $value = false;
                }
            }
            if (!is_bool($value)) {
                if ($strict) {
                    throw new \Exception(
                        "Value `{$value}` is not a integer."
                    );
                }
                if ($default === null) {
                    return null;
                }
                return self::boolean($default, null, $strict);
            }
        }
        return !!$value;
    }
    
    public static function date(
        $value,
        $default = null,
        $input_format = 'Y-m-d H:i:s',
        $strict = false
    ) {
        $d = date_parse_from_format($input_format, $value);
        if ($d['error_count'] !== 0) {
            if ($strict) {
                throw new \Exception(
                    "Value `{$value}` is not a date."
                );
            }
            if ($default === null) {
                return null;
            }
            return self::date($default, null, $input_format, $strict);
        }
        $date = new DateTime();
        $date->setDate($d['year'], $d['month'], $d['day']);
        $date->setTime($d['hour'], $d['minute'], $d['second']);
        return $date;
    }
}
