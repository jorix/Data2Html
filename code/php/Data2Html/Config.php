<?php

class Data2Html_Config
{
    protected static $fileName = 'd2h_config.ini';
    
    protected static $loaded = false;
    protected static $debug = false;
    protected static $config = null;
    
    protected static function load()
    {
        if (self::$loaded) {
            return;
        }
        $file = self::$fileName;
        $config = null;
        if (file_exists($file)) {
            $config = parse_ini_file($file, true);
        } elseif (file_exists($file . '.php')) {
            $file .= '.php';
            $config = parse_ini_file($file, true);
        } else {
            throw new Exception(
                "->Config file \"{$file}\" does not exist");
        }
        if ($config) {
            self::$config = $config;
        } else {
            self::$config = array();
        }
        self::$loaded = true;
        self::$debug = self::get('debug');
    }
    
    public static function getSection($key)
    {
        self::load();
        if (array_key_exists($key, self::$config)) {
            return self::$config[$sectionKey];
        } else {
            return array();
        }
    }
    
    public static function get($key, $default = null, $sectionKey = 'config')
    {
        self::load();
        if (array_key_exists($sectionKey, self::$config)) {
            $section = self::$config[$sectionKey];
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
    public static function debug() {
        self::load();
        return self::$debug;
    }
}
