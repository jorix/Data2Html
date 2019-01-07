<?php
namespace Data2Html;

use Data2Html\Data\To;

trait Debug {   
    public function dump($value = '{$this}') {
        if (!Config::debug()) {
            echo "Debug mode is not activated, activate it to make a dump!";
            return;
        }
        if ($value === '{$this}') {
            echo "<h2>Dump class: " . get_class($this) . "</h2>\n<pre>";
            echo To::php($this->__debugInfo());
        } else {
            echo "<h2>Dump value in: " . 
                get_class($this) . "</h2>\n<pre>";
            echo To::php($value);
        }
        echo "</pre><hr>\n";
    }
    
    public function __debugInfo()
    {
        return $this;
    }
}
