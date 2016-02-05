<?php
    require_once("../code/php/Data2Html.php");
    $data = Data2Html::create(__FILE__, 'aixada_', 'models');
    $data->run();
