<!DOCTYPE html>
<html ng-app="d2h_App" ng-app lang="ca">
<head>
    <meta charset="utf-8">
    <title>Dump model</title>
</head>
<body>
    <div class="container">
    <?php
        require_once("../code/php/Data2Html/Autoload.php");
        Data2Html_Autoload::start();
    
        try {
            $data = Data2Html_Model::create('_controller.php', 'models');
        } catch(Exception $e) {
            echo '<h3>Error: <span style="color:red">' . $e->getMessage() .
            '</span></h3>';
            die();
        }
        if (!$data->debug) {
            echo '<h2 style="color:red">Debug mode is required!</h2>';
        } else {
            try {
                $data->parse();

                echo "<h2>getColDs():</h2>\n<pre>";
                //echo Data2Html_Utils::toPhp($data->getColDs());
                echo "</pre><hr>\n";
                
                $gridName = Data2Html::getGridNameByModel($_REQUEST['model']);

                echo "<h2>getGridsDs():</h2>\n<pre>";
                //echo Data2Html_Utils::toPhp($data->getGrid($gridName));
                echo "</pre><hr>\n";
                
                echo "<h2>Linked getLinkedGrid('{$gridName}'):</h2>\n<pre>";
                echo Data2Html_Utils::toPhp($data->getLinkedGrid($gridName));
                echo "</pre><hr>\n";

            } catch(Exception $e) {
                // Message to developer from exception
                echo Data2Html_Exception::toHtml($e, $data->debug);
                
                echo "<hr><h2>->getColDs()</h2>\n<pre>";
                echo Data2Html_Utils::toPhp($data->getColDs());
                echo "</pre>\n";
            }
        }
    ?>
    </div>
</body>
</html>
