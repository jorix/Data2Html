<!DOCTYPE html>
<?php
    require_once '_start.php';
    $lang= 'ca';
?>
<html lang="<?=$lang?>">
<head>
	<meta charset="UTF-8">
	<title>title</title>
   
</head>
<body>
    <?php 
        $lang = new Data2Html_Lang($lang);
        $lang->load('templates', Data2Html_Config::getForlder('templateFolder'));
        $lang->dump();
        echo $lang->_('lastor') . '<hr>';
        echo $lang->_('templates/forms/page/nitros') . '<hr>';
        echo $lang->_(['templates/forms','page','nitros']) . '<hr>';
                
        echo $lang->from('lastor') . '<hr>';
        echo $lang->from('templates/forms/page/nitros') . '<hr>';
        echo $lang->from(['templates/forms','page','nitros']) . '<hr>';
    ?>
</body>
</html>
