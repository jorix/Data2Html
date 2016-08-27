<?php
class Data2Html_Autoload
{
    protected static $root_path;
    
    /**
     * Register autoload
     */
    public static function start()
    {
        self::$root_path = dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR;
        spl_autoload_register('self::autoload');
    }
    /**
     * Auto load.
     */
    protected static function autoload($class)
    {
        #Not a Data2Html_% class
        if (strpos($class, 'Data2Html_') !== 0) {
            return;
        }
        $file = str_replace('_', '/', $class).'.php';
        $phisicalFile = self::$root_path . $file;
        #Do not interfere with other autoloads
        if (file_exists($phisicalFile)) {
            require $phisicalFile;
        } else {
            throw new Exception(
                "->autoload({$class}): File \"{$file}\" does not exist");
        }
    }
}