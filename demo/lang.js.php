<?php
    require_once("../code/php/Autoload.php");
    Data2Html_Autoload::start(__DIR__, '_config/d2h_config.ini');

    Data2Html_Utils::responseJs(
        Data2Html_Lang::jsCode(Data2Html_Value::getItem($_GET, 'lang', 'ca')),
        Data2Html_Config::debug()
    );
