<?php

class Data2Html_Value
{
    public static function toSql($db, $value, $type, $strict = false)
    {
        if ($value === null) {
            return 'null';
        }
        switch ($type) {
            case 'number':
            case 'currency':            
                $r = ''.self::parseNumber($value, $strict);
                break;
            case 'integer':
            case 'boolean':
                $r = ''.self::parseInteger($value, $strict);
                break;
            case 'string':
            case 'email':
            case 'url':
                $r = $db->stringToSql(self::parseString($value, $strict));
                break;
            case 'date':
                $r = "'".self::parseDate($value, $strict)."'";
                break;
            default:
                throw new Exception(
                    "`{$type}` is not defined."
                );
        }
        return $r;
    }
    
    public static function parseString($value, $strict = false)
    {
        if (is_null($value)) {
            if ($strict) {
                throw new Exception(
                    "Value is nor a string, is null."
                );
            }
            return null;
        }
        return strval($value);
    }
    
    public static function parseNumber($value, $strict = false)
    {
        if (!is_numeric($value)) {
            if ($strict) {
                throw new Exception(
                    "Value `{$value}`  is not a number."
                );
            }
            return null;
        }
        return $value + 0;
    }
    
    public static function parseInteger($value, $strict = false)
    {
        if (!is_numeric($value) || !is_int($value+0)) {
            if ($strict) {
                throw new Exception(
                    "Value `{$value}` is not a integer."
                );
            }
            return null;
        }
        return $value + 0;
    }

    public static function parseDate(
        $value,
        $input_format = 'Y-m-d H:i:s',
        $strict = false
    ) {
        $d = date_parse_from_format($input_format, $value);
        if ($d['error_count'] !== 0) {
            if ($strict) {
                throw new Exception(
                    "Value `{$value}` is not a date."
                );
            }
            return null;
        }
        $date = new DateTime();
        $date->setDate($d['year'], $d['month'], $d['day']);
        $date->setTime($d['hour'], $d['minute'], $d['second']);
        return $date;
    }
}