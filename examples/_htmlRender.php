<?php
    require_once("../code/php/Data2Html/Autoload.php");
    Data2Html_Autoload::start();
    
    $data = Data2Html_Model::create('_controller.php', 'models');
    $data->render(
        "../code/templates/angular1/grid_paged.json.php",
        Data2Html_Array::get($_REQUEST, 'model')
    );

