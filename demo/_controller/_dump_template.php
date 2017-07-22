<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump template</title>
</head>
<body>
    <div class="container">
<?php
    require_once("../../code/php/Data2Html/Autoload.php");
    Data2Html_Autoload::start('../_config');

    try {
        $templateObj = new Data2Html_Render_Template(
            "../../code/templates/jquery/grid_paged.json.php"
        );
        $templateObj->dump();
    } catch(Exception $e) {
        echo Data2Html_Exception::toHtml($e, true);
    }
?>
    </div>
</body>
</html>
