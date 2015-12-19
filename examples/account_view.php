<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ca" lang="ca">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>title</title>
   
    <link rel="stylesheet" type="text/css"   media="screen" href="css/aixada_main.css" />	
    <link  href="../code/external/jquery/css/jqueryui.css" rel="stylesheet" type="text/css" media="screen" />
    <script src="../code/external/jquery/js/jquery.js"></script>
    <!-- script src="../code/external/jqueryui/js/jqueryui.js"></script -->
    <script src="../code/js/Data2Html.js" ></script>
</head>
<body><div id="wrap">
<?php
    $tpl = file_get_contents("account_view_tpl.html");
    require_once("config_db.php");
    require_once("account__.php");
    $a = new aixada_account($db_driver);
    echo $a->renderHtmlTable($tpl);
?>
</div></body>
</html>