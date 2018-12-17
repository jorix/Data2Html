<?php
namespace Data2Html\Data;

use Data2Html\Data\DateTime;

class Parse
{
    public static function value($value, $type, $default = null, $strict = false)
    {
        switch ($type) {
            case 'number':
            case 'currency':
                return Parse::number($value, $default, $strict);
            case 'integer':
                return Parse::integer($value, $default, $strict);
            case 'boolean':
                return Parse::boolean($value, $default, $strict);
            case 'string':
                return Parse::string($value, $default, $strict);
            case 'date':
            case 'datetime':
                return Parse::date($value, $default, $strict);
            case '[integer]':
                return Parse::integerArray($value, $default, $strict);
            case '[string]':
                return Parse::stringArray($value, $default, $strict);
            case 'array':
                if (!is_array($value)) {
                    if ($strict) {
                        throw new \Exception("Value `{$value}` is not `{$type}`.");
                    }
                    return $default;
                }
                return $value;
            case 'function':
                if (!is_callable($value)) {
                    if ($strict) {
                        throw new \Exception("Value `{$value}` is not `{$type}`.");
                    }
                    return $default;
                }
                return $value;
            default:
                throw new \Exception("Type `{$type}` is not defined.");
        }    
    }
    
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
        $strict = false
    ) {
        $input_format = '';
        if (is_string($value)) {
            switch (strlen($value)) {
                case 10:
                    $input_format = 'Y-m-d';
                    break;
                case 16:
                    $input_format = 'Y-m-d H:i';
                    break;
                case 19:
                    $input_format = 'Y-m-d H:i:s';
                    break;
                case 21:
                case 22:
                case 23:
                case 24:
                case 25:
                case 26:
                    $input_format = 'Y-m-d H:i:s.u';
                    break;
                
            }
        }
        if (!$input_format) {
            if ($strict) {
                throw new DebugException(
                    "Is not a valid format date.", [
                        'value' => $value
                    ]
                );
            }
            if ($default === null) {
                return null;
            }
            return self::date($default, null, $strict);
        }
        
        $d = date_parse_from_format($input_format, $value);
        if ($d['error_count'] !== 0) {
            if ($strict) {
                throw new DebugException(
                    "Value '{$value}' is not a date.", [
                        'format' => $input_format,
                        'date_parse_from_format' => $d
                    ]
                );
            }
            if ($default === null) {
                return null;
            }
            return self::date($default, null, $strict);
        }
        return DateTime::createFromFormat($input_format, $value);
    }

        
    public static function integerArray($value, $default = null, $strict = false)
    {
        if (is_string($value)) {
            $value = array_map('trim', explode(',', trim($value, '[]')));
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
        
    public static function stringArray($value, $default = null, $strict = false)
    {
        $trimCustom = function($a) {
            trim($a, " \t\n\r\0\x0B\"'");
        };
        if (is_string($value)) {
            $value = array_map($trimCustom, explode(',', trim($value, '[]')));
        } elseif (!is_array($value)) {
            $value = [$value];
        }
        $response = [];
        foreach ($value as $v) {
            $vv = self::string($v, null, $strict);
            if ($vv === null) {
                $response =  $default;
                break;
            }
            $response[] = $vv;
        }
        return $response;
    }
    
}
