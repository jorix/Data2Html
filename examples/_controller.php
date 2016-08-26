<?php
    require_once("../code/php/Data2Html.php");
    $data = Data2Html::create(__FILE__, 'models');
    $data->manage($_REQUEST);
