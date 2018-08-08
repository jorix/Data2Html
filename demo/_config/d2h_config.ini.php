;<?php die('nothing;'); ?>
[config]
    ; debug = On -> Server shows exceptions, SQL code, and more!
    debug = On
    ; Paths are refereed to folder configuration of this file
    controllerUrl =     ../_controller/_controller.php
    modelFolder =       ../_models
    templateFolder[] =  ../../code/templates
    templateFolder[] =  ../custom/templates
    templateSourceName = _source
    languages[ca] = ca,es,en
    languages[es] = es,en
[db]
    include = 'd2h_config_db_mysqli.ini'
