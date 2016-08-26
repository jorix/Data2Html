<?php

class Data2Html_Config_Db extends Data2Html_Config
{
    protected static $fileName = 'd2h_config_db.ini';
    
    protected static function load()
    {
        if (self::$loaded) {
            return;
        }
        parent::get($key, $default, $sectionKey);
        self::$debug = Data2Html_Config::debug();
    }
    public static function get($key, $default = null, $sectionKey = 'db')
    {
        return parent::get($key, $default, $sectionKey);
    }
}
