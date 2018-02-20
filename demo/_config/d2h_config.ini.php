;<?php die('nothing;'); ?>
[config]
    ; debug = On -> Server shows exceptions, SQL code, and more!
    debug = On
    ; Paths are refereed to folder configuration of this file
    controllerUrl =     ../_controller/_controller.php
    modelFolder =       ../_models
    templateFolder =    ../../code/templates
[db]
    db_class = Pdo
    include = 'd2h_config_db.ini'
