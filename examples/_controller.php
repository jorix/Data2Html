<?php
    require_once("../code/php/Data2Html/Autoload.php");
    Data2Html_Autoload::start();
    
    $data = Data2Html_Model::manage(__FILE__, 'models', $_REQUEST);
