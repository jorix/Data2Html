<?php
    require_once("../code/php/Data2Html/Autoload.php");
    Data2Html_Autoload::start();

    $model = Data2Html_Model::render(
        '_controller.php',
        'models', 
        $_REQUEST,
        '../code/templates/angular1/grid_paged.json.php'
    );
