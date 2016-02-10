<?php
class Data2Html_Utils
{
    public static function readFileJson($fileName)
    {    
        $configObj = json_decode(file_get_contents($fileName), true);
        $configVals = new Data2Html_Values($configObj);
        return $configVals->getValues();
    }
    /**
     * Supports any encoding
     * Not only utf-8, as official json_encode does.
     *
     * Based on original 'php2js' function of Dmitry Koterov
     *
     * @static
     *
     * @param mixed $a
     *
     * @return string
     */
    public static function jsonEncode($a, $level = 0)
    {
        $indent = ' ';
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
                if (substr($a, 0, 4) === '<?js' &&
                    substr($a, -2, 2) === '?>') {
                    return substr($a, 5, -2);
                }

                return '"'.str_replace(
                    array('\\',   '/',   "\n",  "\t",  "\r",  "\b",  "\f",  '"'),
                    array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'),
                    $a).'"';
        }
/*    case "array"
    case "object"
    case "resource"
    case     "unknown type" */
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
                $result[] = self::jsonEncode($v, $level + 1);
            }

            return
                "\n".str_repeat($indent, $level).
                '[ '.implode(', ', $result).' ]';
        } else {
            foreach ($a as $k => $v) {
                $result[] =
                    "\n".str_repeat($indent, $level + 1).
                    self::jsonEncode($k).': '.
                    self::jsonEncode($v, $level + 2);
            }
            if ($level > 0) {
                return
                    "\n".str_repeat($indent, $level).
                    '{ '.implode(', ', $result).' }';
            } else {
                return'{ '.implode(', ', $result)." }\n";
            }
        }
    }

    public static function phpEncode($a, $level = 0)
    {
        $indent = '  ';
        static $jsonReplaces = array(
            array('\\',   '/',   "\n",  "\t",  "\r",  "\b",  "\f",  '"'),
            array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"'),
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
                    substr($a, 0, 6) === '<?code' &&
                    substr($a, -2, 2) === '?>') {
                return substr($a, 7, -2);
            }

            return '"'.str_replace($jsonReplaces[0], $jsonReplaces[1], $a).'"';
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
                $result[] = self::phpEncode($v, $level + 1);
            }

            return
                "\n".str_repeat($indent, $level).
                'array( '.implode(', ', $result).' )';
        } else {
            foreach ($a as $k => $v) {
                $result[] =
                    "\n".str_repeat($indent, $level + 1).
                    self::phpEncode($k).' => '.
                    self::phpEncode($v, $level + 2);
            }
            if ($level > 0) {
                return
                    "\n".str_repeat($indent, $level).
                    'array( '.implode(', ', $result).' )';
            } else {
                return 'array( '.implode(', ', $result)." );\n";
            }
        }
    }
}
