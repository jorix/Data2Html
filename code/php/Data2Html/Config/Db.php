<?php

class Data2Html_Config_Db extends Data2Html_Config
{
    protected static $fileName = 'd2h_config_db.ini';
    protected static $defaultSection = 'db';
    protected static $db_loaded = false;
    
    protected static function load()
    {
        if (static::$db_loaded) {
            return;
        }
        static::$db_loaded = static::loadFile(static::$fileName);
    }
}
