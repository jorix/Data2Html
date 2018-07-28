<?php
namespace Data2Html;

use Data2Html\Data\To;

trait DebugStatic {   
    public static function dump($value = '{$self}') {
        if (!Config::debug()) {
            echo "Debug mode is not active, activate it to make a dump!";
            return;
        }
        if ($value === '{$self}') {
            echo "<h2>Dump static class: " . get_class() . "</h2>\n<pre>";
            echo To::php(self::__debugStaticInfo());
        } else {
            echo "<h2>Dump value on: " . 
                get_class() . "</h2>\n<pre>";
            echo To::php($value);
        }
        echo "</pre><hr>\n";
    }
    
    public static function __debugStaticInfo()
    {
        return self;
    }
}
