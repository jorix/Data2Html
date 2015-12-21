<?php
require_once("config_db.php");
require_once("account__.php");
$a = new aixada_account($db_driver);
$a->run();
$a->getDataArray_Query("select * from aixada_account where account_id=-4 order by id desc",1,200);
