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
            $names = Data2Html_Handler::parseRequest($_REQUEST);
            $gridName = $names['grid'];
            $model = Data2Html_Handler::getModel($names['model']);
        } catch(Exception $e) {
            echo '<h3>Error: <span style="color:red">' . $e->getMessage() .
            '</span></h3>';
            die();
        }
        try {
            $grid = $model->getGrid($gridName);
            $grid->getLink()->dump();
            $grid->dump();
            

            // echo "<h2>getGridsDs():</h2>\n<pre>";
            // //echo Data2Html_Utils::toPhp($model->getGrid($gridName));
            // echo "</pre><hr>\n";
            
            // echo "<h2>Linked getLinkedGrid('{$gridName}'):</h2>\n<pre>";
            // echo Data2Html_Utils::toPhp($model->getLinkedGrid($gridName));
            // echo "</pre><hr>\n";

        } catch(Exception $e) {
            // Message to developer from exception
            echo Data2Html_Exception::toHtml($e, true);
            
            echo "<hr><h2>->gxxxxxx()</h2>\n<pre>";
            echo Data2Html_Utils::toPhp($model->getColDs());
            echo "</pre>\n";
        }
    ?>
    </div>
</body>
</html>
