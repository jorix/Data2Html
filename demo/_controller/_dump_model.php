<!DOCTYPE html>
<html lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump model</title>
</head>
<body>
    <div class="container">
    <?php
        require_once("../../code/php/Autoload.php");
        Data2Html_Autoload::start(__DIR__, '../_config/d2h_config.ini');
        // Data2Html_Config::dump();
    
        try {
            $payerNames = Data2Html_Handler::parseRequest($_REQUEST);
            $model = Data2Html_Handler::createModel($payerNames['model']);
            if (array_key_exists('form', $payerNames)) {
                $obj = $model->getForm($payerNames['form']);
            } else {
                $obj = $model->getGrid($payerNames['grid']);
            }
            // $obj->dump();
            $obj->createLink()->dump();
            
        } catch(Exception $e) {
            echo Data2Html_Exception::toHtml($e, true);
        }
    ?>
    </div>
</body>
</html>
