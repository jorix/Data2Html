<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump template</title>
</head>
<body>
    <div class="container">
<?php
    require_once '../_start.php';

    try {
        $templates = new \Data2Html\Render\Branch($_REQUEST['template']);
        $templates->dump();
        \Data2Html\Render\FileContents::dump();
    } catch(Exception $e) {
        echo \Data2Html\DebugException::toHtml($e);
    }
?>
    </div>
</body>
</html>
