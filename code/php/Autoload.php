<?php
// https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-4-autoloader-examples.md
namespace Data2Html;
class Autoload
{
    protected static $codeFolder;
    
    /**
     * Register autoload
     */
    public static function start()
    {
        if (version_compare(PHP_VERSION, '5.4.0', '<')) {
            die('<b>At least PHP 5.4 or PHP7 is required to run Data2Html</b>');
        }
        self::$codeFolder = __DIR__ . DIRECTORY_SEPARATOR;
        spl_autoload_register('self::load');
    }
    
    public static function getCodeFolder()
    {
        return self::$codeFolder;
    }
    
    /**
     * Loads the class file for a given class name.
     *
     * @param string $class A class name.
     */
    protected static function load($class)
    {
        $classX = $class; 
        $class = str_replace('_', '\\', $class);
        
        $prefix = 'Data2Html\\';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $file = substr($class, $len);
        $file = str_replace('\\', '/', $file).'.php';
        if (!self::requireFile(self::$codeFolder . $file)) {
            throw new \Exception(
                "->autoload({$classX}): File \"{$file}\" does not exist in \"" . 
                self::$codeFolder .
                "\""
            );
        }
    }
    
    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file Name of the file to require.
     * @return bool True if file exists.
     */
    protected static function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        } else {
            return false;
        }
    }
}