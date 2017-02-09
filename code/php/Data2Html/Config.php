<?php

class Data2Html_Config
{
    public static $folderName = '.';
    protected static $fileNames = array(
        'd2h_config.ini',
        'd2h_config_db.ini'
    );
    protected static $defaultSection = 'config';
    
    protected static $loaded = false;
    protected static $debug = false;
    protected static $config = array();
    
    protected static function load()
    {
        if (static::$loaded) {
            return;
        }
        foreach(static::$fileNames as $file) {
            static::$loaded = static::loadFile(
                static::$folderName . '/' . $file
            );
        }
        self::$debug = self::get('debug');
    }
    
    protected static function loadFile($file)
    {
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
            static::$config = array_replace_recursive(static::$config, $config);
        }
        return true;
    }
    
    public static function getSection($key)
    {
        static::load();
        if (array_key_exists($key, static::$config)) {
            return static::$config[$key];
        } else {
            return array();
        }
    }
    
    public static function dump()
    {
        static::load();
        if (self::$debug) {
            echo "<div style=\"margin-left:.5em\">
                <h3>Config of: \"" . static::$fileName . "\":</h3>
                <pre>" .
                Data2Html_Value::toJson(static::$config, true) .
                '</pre></div>';
        } else {
            echo '<h3 style="margin-left:.5em; color:red; test-align:center">
                Debugging mode must be enabled to can use dump() method!</h3>';
        }
    }
    
    public static function get($key, $default = null, $sectionKey = null)
    {
        static::load();
        if (!$sectionKey) {
            $sectionKey = static::$defaultSection;
        }
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
    public static function debug() {
        self::load();
        return self::$debug;
    }
}
