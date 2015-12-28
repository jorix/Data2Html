<?php
require_once("config_db.php");
require_once("account__.php");
$a = new aixada_account($db_driver);
$a->run();
