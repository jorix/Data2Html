<?php
class Data2Html_Autoload
{
    protected static $root_path;
    
    /**
     * Register autoload
     */
    public static function start()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            trigger_error('At least PHP 5.3 is required to run Data2Html', E_USER_ERROR);
        }
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