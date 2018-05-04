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
            
            $model = Data2Html_Handler::getModel($payerNames['model']);
            $model->dump($payerNames);
            if (array_key_exists('element', $payerNames)) {
                $obj = $model->getLinkedElement($payerNames['element']);
            } else {
                $obj = $model->getLinkedGrid($payerNames['grid']);
            }
            // $obj->dump();
            $obj->dump();
            
        } catch(Exception $e) {
            echo Data2Html_Exception::toHtml($e, true);
        }
    ?>
    </div>
</body>
</html>
