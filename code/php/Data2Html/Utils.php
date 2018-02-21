<?php
class Data2Html_Utils
{
    public static function toCleanName($str, $delimiter = '-') {
        //test: echo Data2Html_Utils::toCleanName('Xús_i[ sin("lint CC") ]+3');
        $str = strtolower(trim($str, " '\"_|+-,.[]()"));
        $str = str_replace("'", '"', $str); // To protect apostrophes to not 
            // confuse with accented letters converted to ASCII//TRANSLIT, á='a
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace("/[^ \"\_|+-,\.\[\]\(\)a-zA-Z0-9]/", '', $str);
        $str = preg_replace("/[ \"\_|+-,\.\[\]\(\)]+/", $delimiter, $str);
        return $str;
    }

    public static function toCleanFilePath($fileName, $ds = null) {
        $fileName = str_replace(
            array('\\', '/./', '//'),
            array('/', '/', '/'),
            $fileName
        );
        $path = explode('/', $fileName);
        $cleanFile = '';
        for ($i = 0; $i + 1 < count($path); $i++) {
            if ($path[$i + 1] === '..') {
                array_splice($path, $i, 2);
                if($i > 1) {
                    $i -= 2;
                }
            } else if ($path[$i + 1] === '.') {
                array_splice($path, $i + 1, 1);
            }
        }
        return implode(($ds ? $ds : DIRECTORY_SEPARATOR), $path);
    }

    public static function toCleanFolderPath($folderName, $ds = null)
    {
        $folder = self::toCleanFilePath($folderName, $ds);
        if (strpos('/\\', substr($folder, -1, 1)) === false) {
            return $folder .= ($ds ? $ds : DIRECTORY_SEPARATOR);
        } else {
            return $folder;
        }
    }
    
    public static function readFileJson($fileName, $culprit = 'Data2Html_Utils')
    {
        if (!file_exists($fileName)) {
            throw new Exception("{$culprit}: The \"{$fileName}\" file does not exist.");
        }
        $content = self::readWrappedFile($fileName, $culprit);
        if ($content === null) {
            throw new Exception("{$culprit}: Error parsing json file: \"{$fileName}\"");
        }
        return json_decode($content, true);
    }
    
    public static function readFilePhp($fileName, $culprit = 'Data2Html_Utils')
    {
        if (!file_exists($fileName)) {
            throw new Exception("{$culprit}: The \"{$fileName}\" file does not exist.");
        }
        require $fileName;
        if (!isset($return)) {
            throw new Exception("{$culprit}: Error parsing phpReturn file: \"{$fileName}\"");
        }
        return $return;
    }
    
    public static function readWrappedFile($fileName, $culprit = 'Data2Html_Utils')
    {
        if (!file_exists($fileName)) {
            throw new Exception("{$culprit}: The \"{$fileName}\" file does not exist.");
        }        
        $content = file_get_contents($fileName);
        $pathObj = self::parseWrappedPath($fileName);
        if ($pathObj['wrap'] === '.php') {
            $phpEnd = strpos($content, "?>\n");
            if ($phpEnd === false) {
                $phpEnd = strpos($content, "?>\r");
            }
            if ($phpEnd !== false) {
                $content = substr($content, $phpEnd + 3);
            }
        }
        return $content;
    }
    
    public static function parseWrappedPath($fileName, $culprit = 'Data2Html_Utils')
    {
        $pathObj = pathinfo($fileName);
        if (isset($pathObj['extension'])) {
            $pathObj['extension'] = '.' . strtolower($pathObj['extension']);
        } else {
            $pathObj['extension'] = '';
        }
        if ($pathObj['dirname']) {
            $pathObj['dirname'] .= DIRECTORY_SEPARATOR;
        }
        $pathObj['wrap'] = '';
        
        if ($pathObj['extension'] === '.php') {
            $pathObj2 = self::parseWrappedPath(
                $pathObj['dirname'] . $pathObj['filename']
            );
            if ($pathObj2['extension'] && strpos('.html.js.json', $pathObj2['extension']) !== false) {
                $pathObj2['wrap'] = $pathObj['extension'];
                $pathObj = $pathObj2;
            }
        }
        return $pathObj;
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

    public static function dump($title, $a) {
        if (!Data2Html_Config::debug()) {
            echo "Debug mode is not activated, activate it to make a dump!";
            return;
        }
        echo "<h2>Dump of: {$title}</h2>\n<pre>";
        echo self::toPhp($a);
        echo "</pre><hr>\n";
    }

    public static function toPhp($a, $level = 0)
    {
        $indent = '    ';
        static $replaces = array(
            array('\\',   "\n",  "\t",  "\r",  "\b",  "\f",  "'"),
            array('\\\\', '\\n', '\\t', '\\r', '\\b', '\\f', "\\'"),
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
                    self::toPhp($v, $level + 1);
            }
            return 
                '[ ' . implode(', ', $result) .
                "\n" . str_repeat($indent, $level) . ']';
        } else {
            foreach ($a as $k => $v) {
                $result[] =
                    "\n".str_repeat($indent, $level + 1).
                    self::toPhp($k).' => ' . self::toPhp($v, $level + 1);
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
