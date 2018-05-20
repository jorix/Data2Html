<?php

    require_once "../../code/php/Autoload2.php";
    Data2Html\Autoload::start();
    // Config::load(__DIR__, 'd2h_config.ini');


echo Data2Html\Autoload::getCodeFolder();
echo Data2Html\Util::strRemoveStart("aaaBBBB","aa");
