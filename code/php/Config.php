<?php
namespace Data2Html;

use Data2Html\Data\InfoFile;

class Config
{
    protected static $config = array();
    protected static $debug = false;
    protected static $configPath = null;
    protected static $configFolder = null;
    
    public static function load($basePath, $fileName)
    {
        if (self::$configFolder) {
            return;
        }
        self::$configPath = InfoFile::toCleanFilePath(dirname($fileName), '/' ) . '/' ;
        self::$configFolder = InfoFile::toCleanFolderPath(
            $basePath . '/' . dirname($fileName)
        );
        self::loadFile($basePath . '/' . $fileName);
        self::$debug = self::get('debug');
    }
    
    public static function debug() {
        return self::$debug;
    }
    
    public static function getSection($key)
    {
        if (array_key_exists($key, static::$config)) {
            return static::$config[$key];
        } else {
            return array();
        }
    }
       
    public static function getPath($key, $default = null, $sectionKey = 'config')
    {
        $val = self::get($key, $default, $sectionKey);
        if ($val) {
            return InfoFile::toCleanFilePath(self::$configPath . $val, '/');
        } else {
            return $val;
        }
    }
    
    public static function getForlder($key, $default = null, $sectionKey = 'config')
    {
        $val = self::get($key, $default, $sectionKey);
        if ($val) {
            if (is_array($val)) {
                $response = [];
                foreach ($val as $v) {
                    $response[] = InfoFile::toCleanFolderPath(self::$configFolder . $v);
                }
                return $response;
            } else {
                return InfoFile::toCleanFolderPath(self::$configFolder . $val);
            }
        } else {
            return $val;
        }
    }
    
    public static function get($key, $default = null, $sectionKey = 'config')
    {
        if (array_key_exists($sectionKey, static::$config)) {
            $section = static::$config[$sectionKey];
            if (array_key_exists($key, $section)) {
                $val = $section[$key];
            } else {
                $val = $default;
            }
        } else {
            $val = $default;
        }
        return $val;
    }
    
    private static function loadFile($file)
    {
        $config = null;
        $fileType = InfoFile::parseWrappedPath($file)['extension'];
        switch ($fileType) {
            case '.json':
                $config = InfoFile::readJson($file);
                break;
            case '.php':
                $config = InfoFile::readPhp($file);
                break;
            case '.ini':
                $config = InfoFile::readIni($file);
                break;
            default:
                throw new \Exception("File type \"{$fileType}\" is not supported (file: \"{$file}\" )");
                break;
        }
        if ($config) {
            static::$config = array_replace_recursive(static::$config, $config);
        }
        
        // Includes
        foreach ($config as $v) {
            foreach ($v as $kk => $vv) {
                if($kk === 'include') {
                    foreach ((array)$vv as $vvv) {
                        self::loadFile(self::$configFolder . $vvv);
                    }
                }
            }
        }
    }
}
