<?php
namespace Data2Html;

use Data2Html\Data\To;

trait Debug {   
    public function dump($a) {
        if (!Config::debug()) {
            echo "Debug mode is not activated, activate it to make a dump!";
            return;
        }
        echo "<h2>Dump on: " . get_class($this) . "</h2>\n<pre>";
        echo To::php($this->__debugInfo());
        echo "</pre><hr>\n";
    }
    
    public function __debugInfo()
    {
        return $this;
    }
}
