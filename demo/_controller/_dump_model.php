<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump model</title>
</head>
<body>
    <div class="container">
    <?php
        require_once("../../code/php/Data2Html/Autoload.php");
        Data2Html_Autoload::start('../_config');
    
        try {
            $payerNames = Data2Html_Handler::parseRequest($_REQUEST);
            $model = Data2Html_Handler::createModel($payerNames['model']);
            if (array_key_exists('form', $payerNames)) {
                $lkObj = $model->getForm($payerNames['form']);
            } else {
                $lkObj = $model->getGrid($payerNames['grid']);
            }
            
            //$lkObj->dump();
            $lkObj->createLink()->dump();
            
        } catch(Exception $e) {
            echo Data2Html_Exception::toHtml($e, true);
        }
    ?>
    </div>
</body>
</html>
