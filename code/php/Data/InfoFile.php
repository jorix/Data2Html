<?php
namespace Data2Html\Data;

class InfoFile
{
    public static function toCleanFilePath($fileName, $ds = null) {
        $fileName = str_replace(
            array('\\', '/./', '//'),
            array('/', '/', '/'),
            $fileName
        );
        $fileName = preg_replace('/^\.\//i', '', $fileName);
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
    
    public static function readJson($fileName)
    {
        if (!file_exists($fileName)) {
            throw new \Exception("File \"{$fileName}\" does not exist.");
        }
        $content = self::readWrappedFile($fileName);
        if ($content === null) {
            throw new \Exception("Error parsing json file: \"{$fileName}\"");
        }
        return json_decode($content, true);
    }
    
    public static function readPhp($fileName)
    {
        if (!file_exists($fileName)) {
            throw new \Exception("File \"{$fileName}\" does not exist.");
        }
        $response = require $fileName;
        if ($response === 1) {
            throw new \Exception("Error parsing php file: \"{$fileName}\"");
        }
        return $response;
    }
    
    public static function readWrappedFile($fileName)
    {
        if (!file_exists($fileName)) {
            throw new \Exception("File \"{$fileName}\" does not exist.");
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
    
    public static function parseWrappedPath($fileName)
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
        $pathObj[0] = $fileName;
        return $pathObj;
    }
}
