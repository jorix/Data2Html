<?php
    require_once("../../code/php/Data2Html/Autoload.php");
    Data2Html_Autoload::start('../_config');
    
    $data = Data2Html_Handler::manage($_REQUEST);
