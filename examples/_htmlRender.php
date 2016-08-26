<?php
    require_once("../code/php/Data2Html.php");
    $data = Data2Html::create('_controller.php', 'models');
    $data->render(
        "../code/templates/angular1/grid_paged.json.php",
        Data2Html_Array::get($_REQUEST, 'model')
    );

