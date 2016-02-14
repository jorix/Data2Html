<?php
require_once("../code/php/Data2Html.php");
require_once("models/aixada_accounts.php");
$a = new aixada_accounts();
$a->manage('d2h_config_db.ini');
