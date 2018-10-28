<!DOCTYPE html>
<?php
    require_once 'ax_start.php';

    $render = Data2Html\Handler::createRender();
    $result = $render->render(  
        $_REQUEST + ['url' => '?' . $_SERVER['QUERY_STRING']],
        'edit'
    );
    $lang= 'ca';
?>
<html lang="<?=$lang?>">
<head>
	<meta charset="UTF-8">
	<title>ax_edit</title>

    <script src="lang.js.php?lang=<?=$lang?>"></script>
    <?php echo $result->getSource(['base' => '..', 'lang' => $lang]);?>
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
