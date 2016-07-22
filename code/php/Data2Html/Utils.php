<?php
class Data2Html_Utils
{
    public static function readFileJson($fileName)
    {
        return json_decode(file_get_contents($fileName), true);
    }
    /**
     * @param mixed $a
     *
     * @return string
     */
    public static function toJs($a, $level = 0)
    {
        $indent = '    ';
        switch (gettype($a)) {
            case 'NULL':
                return 'null';
            case 'boolean':
                return $a ? 'true' : 'false';
            case 'integer': return $a;
            case 'double':
            case 'float':
                return str_replace(',', '.', strval($a));
            case 'string':
                if (substr($a, 0, 5) === '<?js ' &&
                    substr($a, -2, 2) === '?>') {
                    return substr($a, 5, -2);
                } elseif (substr($a, 0, 7) === '<?code ' &&
                    substr($a, -2, 2) === '?>') {
                    return substr($a, 7, -2);
                }
                return '"'.str_replace(
                    array('\\',   '/',   "\n",  "\t",  "\r",  "\b",  "\f",  '"'),
                    array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'),
                    $a).'"';
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
                $result[] = self::toJs($v, $level + 1);
            }
            return "[\n" . str_repeat($indent, $level + 1) .
                    implode(
                        ",\n" . str_repeat($indent, $level + 1),
                        $result
                    ) .
                    "\n" . str_repeat($indent, $level) . ']';
        } else {
            foreach ($a as $k => $v) {
                $result[] =
                    "\n" . str_repeat($indent, $level + 1).
                    self::toJs($k).': '.
                    self::toJs($v, $level + 1);
            }
            if ($level > 0) {
                return 
                    '{ ' . implode(", ", $result) . 
                    "\n" . str_repeat($indent, $level) . '}';
            } else {
                return '{ ' . implode(', ', $result) . "\n}\n";
            }
        }
    }

    public static function toPhp($a, $level = 0)
    {
        $indent = '    ';
        static $jsonReplaces = array(
            array('\\',   '/',   "\n",  "\t",  "\r",  "\b",  "\f",  "'"),
            array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', "\\'"),
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

            return "'".str_replace($jsonReplaces[0], $jsonReplaces[1], $a)."'";
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
                    self::toPhp($v, $level + 1);
            }
            return 
                'array( ' . implode(', ', $result) .
                "\n" . str_repeat($indent, $level) . ')';
        } else {
            foreach ($a as $k => $v) {
                $result[] =
                    "\n".str_repeat($indent, $level + 1).
                    self::toPhp($k).' => ' . self::toPhp($v, $level + 1);
            }
            if ($level > 0) {
                return 
                    'array( ' . implode(', ', $result) .
                    "\n" . str_repeat($indent, $level) . ')';
            } else {
                return 'array( '.implode(', ', $result)."\n);\n";
            }
        }
    }
}
