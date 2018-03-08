<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump template</title>
</head>
<body>
    <div class="container">
<?php
    require_once("../../code/php/Autoload.php");
    Data2Html_Autoload::start(__DIR__, '../_config/d2h_config.ini');

    try {
        Data2Html_Utils::dump(
            'tree',
            Data2Html_Render_fileContents::load($_REQUEST['templateName'])
        );
        Data2Html_Render_fileContents::dump();
    } catch(Exception $e) {
        echo Data2Html_Exception::toHtml($e, true);
    }
?>
    </div>
</body>
</html>
