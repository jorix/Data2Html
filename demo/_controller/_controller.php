<?php
    require_once("../../code/php/Data2Html/Autoload.php");
    Data2Html_Autoload::start(__DIR__, '../_config/d2h_config.ini');
    
    $data = Data2Html_Handler::manage($_REQUEST);
