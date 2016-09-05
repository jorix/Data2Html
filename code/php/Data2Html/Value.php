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
    public static function toJson($obj, $pretty = false)
    {
        $options = 0;
        if ($pretty && version_compare(PHP_VERSION, '5.4.0', '>=')) {
            $options |= JSON_PRETTY_PRINT;
        }
        $result = json_encode($obj, $options);
        $json_error = json_last_error();
        if ($json_error !== JSON_ERROR_NONE) {
            switch ($json_error) {
                case JSON_ERROR_NONE:
                    $textError = 'No errors';
                    break;
                case JSON_ERROR_DEPTH:
                    $textError = 'Maximum stack depth exceeded';
                    break;
                case JSON_ERROR_STATE_MISMATCH:
                    $textError = 'Underflow or the modes mismatch';
                    break;
                case JSON_ERROR_CTRL_CHAR:
                    $textError = 'Unexpected control character found';
                    break;
                case JSON_ERROR_SYNTAX:
                    $textError = 'Syntax error, malformed JSON';
                    break;
                case JSON_ERROR_UTF8:
                    $textError = 'Malformed UTF-8 characters, possibly incorrectly encoded';
                    break;
                default:
                    $textError = 'Unknown error';
                    break;
            }
            throw new Exception('JSON Error: ' . $json_error . ' - '. $textError);
        }   
        return $result;
    }
    
    public static function parseString($value, $strict = false)
    {
        if (is_array($value)) {
            throw new Exception(
                "Value is not a string, is a array."
            );
        }
        if (is_object($value)) {
            throw new Exception(
                "Value is not a string, is a object."
            );
        }
        if (is_null($value)) {
            if ($strict) {
                throw new Exception(
                    "Value is not a string, is null."
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
