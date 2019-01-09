<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dump model</title>
</head>
<body>
    <div class="container">
    <?php
        require_once '../_start.php';
        try {
            $doLink = (!isset($_REQUEST['link']) || $_REQUEST['link'] !== 'false');
            $pNames = \Data2Html\Model\Models::parseRequest($_REQUEST);
            
            if (array_key_exists('block', $pNames)) {
                $obj = \Data2Html\Model\Models::linkBlock($pNames['model'], $pNames['block'], $doLink);
            } else {
                $obj = \Data2Html\Model\Models::linkGrid($pNames['model'], $pNames['grid'], $doLink);
            }
            $obj->dump();
        } catch(Exception $e) {
            echo \Data2Html\DebugException::toHtml($e, true);
        }
    ?>
    </div>
</body>
</html>
