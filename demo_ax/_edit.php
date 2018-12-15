<!DOCTYPE html>
<?php
    // In final files use array() instead [] to be able to warn of version PHP < 5.4
    require_once '_start.php';

    $render = Data2Html\Handler::createRender();
    $result = $render->render(  
        $_REQUEST + array('url' => '?' . $_SERVER['QUERY_STRING']),
        'edit'
    );
    $lang= 'ca';
?>
<html lang="<?=$lang?>">
<head>
	<meta charset="UTF-8">
	<title>ax_edit</title>

    <script src="lang.js.php?lang=<?=$lang?>"></script>
    <?php echo $result->getSource(array('base' => '..', 'lang' => $lang));?>
</head>
<body>
    <div class="container">
        <?php echo $result->get('html'); ?>
    </div>
    <div class="d2h_waiting"></div>
    <script>
        <?php echo $result->get('js'); ?>
    </script>
</body>
</html>
