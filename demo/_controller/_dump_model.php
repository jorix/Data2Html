<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump model</title>
</head>
<body>
    <div class="container">
    <?php
        require_once '../_start.php';
    
        try {
            $payerNames = \Data2Html\Handler::parseRequest($_REQUEST);
            $doLink = (\Data2Html\Data\Lot::GetItem('link', $_REQUEST, 'true') !== 'false');
            
            $model = \Data2Html\Handler::getModel($payerNames['model']);
            // $model->dump();
            if (array_key_exists('block', $payerNames)) {
                $obj = $model->getLinkedBlock($payerNames['block'], $doLink);
            } else {
                $obj = $model->getLinkedGrid($payerNames['grid'], $doLink);
            }
            //$model->getBase()->dump();
            $obj->dump();
        } catch(Exception $e) {
            echo \Data2Html\DebugException::toHtml($e, true);
        }
    ?>
    </div>
</body>
</html>
