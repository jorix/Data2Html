<?php
class Data2Html_Autoload
{
    protected static $root_path;
    
    /**
     * Register autoload
     */
    public static function start($baseFolder, $configFile)
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            die('<b>At least PHP 5.4 or PHP7 is required to run Data2Html</b>');
        }
        self::$root_path = dirname(__FILE__) . DIRECTORY_SEPARATOR;
        spl_autoload_register('self::autoload');
        Data2Html_Config::load($baseFolder, $configFile);
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
        $file = str_replace('Data2Html_', '', $class);
        $file = str_replace('_', '/', $file).'.php';
        $phisicalFile = self::$root_path . $file;
        #Do not interfere with other autoloads
        if (file_exists($phisicalFile)) {
            require $phisicalFile;
        } else {
            throw new Exception(
                "->autoload({$class}): File \"{$file}\" does not exist in \"" . self::$root_path . "\"");
        }
    }
}