<!DOCTYPE html>
<?php
    require_once '_start.php';

    $render = Data2Html\Handler::createRender();
    $result = $render->render($_REQUEST, 'edit');
?>
<html lang="ca">
<head>
	<meta charset="UTF-8">
	<title>_edit</title>

    <script><?php echo \Data2Html\Lang::jsCode('ca'); ?></script>
    <?php echo $result->getRequire();?>
</head>
<body>
    <?php echo $result->get('html'); ?>
    <div class="d2h_waiting"></div>
    <script>
        <?php echo $result->get('js'); ?>
    </script>
</body>
</html>
