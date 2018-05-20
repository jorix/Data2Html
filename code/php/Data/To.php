<?php
namespace Data2Html\Data;

class To
{
    public static function json($obj, $pretty = false)
    {
        $options = 0;
        if ($pretty) {
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
    
    protected static function php($obj)
    {
        return toPhpStep($obj);
    }
    
    protected static function toPhpStep($a, $level = 0)
    {
        $indent = '    ';
        static $replaces = array(
            array("\n",  "\t",  "\r",  "\b",  "\f",  "'"),
            array('\\n', '\\t', '\\r', '\\b', '\\f', "\\'"),
        );
        if (is_null($a)) {
            return 'null';
        }
        if ($a === false) {
            return 'false';
        }
        if ($a === true) {
            return 'true';
        }
        if (is_scalar($a)) {
            if (is_float($a)) {
                return str_replace(',', '.', strval($a));
            }
            if (is_numeric($a)) {
                return $a;
            }
            if (is_string($a) &&
                    substr($a, 0, 7) === '<?code ' &&
                    substr($a, -2, 2) === '?>') {
                return substr($a, 7, -2);
            }

            return "'".str_replace($replaces[0], $replaces[1], $a)."'";
        }
        if (is_callable($a)) {
            return 'function() { ... }';
        }
        $isList = true;
        for ($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if (key($a) !== $i) {
                $isList = false;
                break;
            }
        }
        $result = array();
        if ($isList) {
            foreach ($a as $v) {
                $result[] = 
                    "\n".str_repeat($indent, $level + 1) . 
                    self::toPhpStep($v, $level + 1);
            }
            return 
                '[ ' . implode(', ', $result) .
                "\n" . str_repeat($indent, $level) . ']';
        } else {
            foreach ($a as $k => $v) {
                $result[] =
                    "\n".str_repeat($indent, $level + 1).
                    self::toPhpStep($k).' => ' . self::toPhpStep($v, $level + 1);
            }
            if ($level > 0) {
                return 
                    '[ ' . implode(', ', $result) .
                    "\n" . str_repeat($indent, $level) . ']';
            } else {
                return '[ '.implode(', ', $result)."\n];\n";
            }
        }
    }
}
