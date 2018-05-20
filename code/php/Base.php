<?php
namespace Data2Html;

trait Base {   
    /**
     * private construct, generally defined by using class
     */
    //private function __construct() {}
    private static $instance = null;

    public static function dump($a) {
        if (!Data2Html_Config::debug()) {
            echo "Debug mode is not activated, activate it to make a dump!";
            return;
        }
        echo "<h2>Dump of: " . __CLASS__ . "</h2>\n<pre>";
        echo var_dump($a);
        echo "</pre><hr>\n";
    }
   
    public function error($message, $data = null)
    {
        throw new ExceptionData($message, $data);
    }
    
    public static function create() {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }
   
    public function __clone() {
        trigger_error('Cloning '.__CLASS__.' is not allowed.',E_USER_ERROR);
    }
   
    public function __wakeup() {
        trigger_error('Unserializing '.__CLASS__.' is not allowed.',E_USER_ERROR);
    }
}
