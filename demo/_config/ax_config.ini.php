;<?php die('nothing;'); ?>
[config]
    ; debug = On -> Server shows exceptions, SQL code, and more!
    debug = On
    ; Paths are refereed to folder configuration of this file
    controllerUrl =     ../_controller/ax_controller.php
    modelFolder =       ../ax_models
    templateFolder[] =  ../../code/templates
    templateFolder[] =  ../custom/templates
    templateSourceName = _source
    languages[ca] = ca,es,en
    languages[es] = es,en
[db]
    include = 'ax_aixada_db_mysqli.ini'
