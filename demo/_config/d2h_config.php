<?php 
return [
    'config' => [
        // debug = true -> Server shows exceptions, SQL code, and more!
        'debug' => true,
        // Paths are refereed to folder configuration of this file
        'controllerUrl' =>    '../_controller/_controller.php',
        'modelFolder' =>      '../_models',
        'templateFolder' =>  ['../../code/templates', '../custom/templates'],
        'template-require' => '_require',
        'languages' => [
            'ca' => 'ca,es,en',
            'es' => 'es,en'
        ],
    ],
    'db' => ['include' => 'employeers_mysqli.ini.php']
];
