<?php

class Data2Html_Config
{
    protected static $debug = false;
    protected static $config = array();
    protected static $configPath = null;
    protected static $configFolder = null;
    
    public static function load($basePath, $fileName)
    {
        if (self::$configFolder) {
            return;
        }
        self::$configPath = Data2Html_Utils::toCleanFilePath(dirname($fileName), '/' ) . '/' ;
        self::$configFolder = Data2Html_Utils::toCleanFolderPath(
            $basePath . '/' . dirname($fileName)
        );
        self::loadFile($fileName);
        self::$debug = self::get('debug');
    }
    
    public static function dump($subject = null)
    {
        if(!$subject) {
            $subject = array(
                'configPath' => self::$configPath,
                'configFolder' => self::$configFolder,
                'controllerUrl' => self::getPath('controllerUrl'),
                'templateFolder' => self::getForlder('templateFolder'),
                'config' => self::$config
            );
        }
        Data2Html_Utils::dump("Data2Html_Config", $subject);
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
            return Data2Html_Utils::toCleanFilePath(self::$configPath . $val, '/');
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
                    $response[] = Data2Html_Utils::toCleanFolderPath(self::$configFolder . $v);
                }
                return $response;
            } else {
                return Data2Html_Utils::toCleanFolderPath(self::$configFolder . $val);
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
        if (file_exists($file)) {
            $config = parse_ini_file($file, true);
        } elseif (file_exists($file . '.php')) {
            $file .= '.php';
            $config = parse_ini_file($file, true);
        } else {
            throw new Exception(
                "Data2Html_Config: File \"{$file}\" does not exist");
        }
        if ($config) {
            static::$config = array_replace_recursive(static::$config, $config);
        }
        
        // Includes
        foreach ($config as $v) {
            foreach ($v as $kk => $vv) {
                if($kk === 'include' || $kk === 'includes') {
                    foreach ((array)$vv as $vvv) {
                        self::loadFile(self::$configFolder . $vvv);
                    }
                }
            }
        }
    }
}
