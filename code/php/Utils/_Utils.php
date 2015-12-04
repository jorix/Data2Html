<?php

class Data2Html_Utils {
    /**
     * Supports any encoding
     * Not only utf-8, as official json_encode does
     *
     * Based on original 'php2js' function of Dmitry Koterov
     *
     * @static
     * @param  mixed $a
     * @return string
     */
    public static function jsonEncode($a = false) {
        static $jsonReplaces = array(
            array("\\", "/", "\n", "\t", "\r", "\b", "\f", '"'),
            array('\\\\', '\\/', '\\n', '\\t', '\\r', '\\b', '\\f', '\"')
        );

        if(is_null($a)) return 'null';
        if($a === false) return 'false';
        if($a === true) return 'true';

        if(is_scalar($a)) {
            if(is_float($a)) {
                $a = str_replace(",", ".", strval($a));
            }
            if (is_string($a) && 
					substr($a, 0, 4) === '<?js' &&
					substr($a, -2, 2) === '?>') {
                return substr($a, 5, -2);
            }
            return '"'.str_replace($jsonReplaces[0], $jsonReplaces[1], $a).'"';
        }
        $isList = true;
        for($i = 0, reset($a); $i < count($a); $i++, next($a)) {
            if(key($a) !== $i) {
                $isList = false;
                break;
            }
        }
		$result = array();
		if($isList) {
            foreach($a as $v) {
                $result[] = self::jsonEncode($v);
            }
            return '[ '.implode(', ', $result)." ]\n";
        } else {
            foreach($a as $k => $v) {
                $result[] = self::jsonEncode($k).': '.self::jsonEncode($v);
            }
			return '{ '.implode(', ', $result)." }\n";
        }
    }

    /**
     * Check input string to contain only english letters, numbers and unserscore
     * The list of allowed characters might be extended
     *
     * @static
     * @throws jqGrid_Exception
     * @param $val - input string
     * @param string $additional - additional allowed characters
     * @return string
     */
    public static function checkAlphanum($val) {
        static $mask = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789_';
        if($val and strspn($val, $mask . $additional) != strlen($val)){
            throw new Exception('Alphanum check failed on value: '.$val);
        }
		return $val;
    }
}