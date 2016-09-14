<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump html</title>
</head>
<body>
    <div class="container">
<?php
    require_once("../code/php/Data2Html/Autoload.php");
    Data2Html_Autoload::start();

    $model = Data2Html_Model::render(
        '_controller.php',
        'models', 
        $_REQUEST,
        '../code/templates/angular1/grid_paged.json.php'
    );
?>
</div>
</body>
</html>
