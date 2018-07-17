<?php
// Don't use AspectMock with a sintax error!!!!
    //include __DIR__.'/../../vendor/autoload.php'; // composer autoload
    // $kernel = \AspectMock\Kernel::getInstance();
    // $kernel->init([
        // 'debug' => true,
        // 'includePaths' => [__DIR__.'/../src']
    // ]);
    // $kernel->loadFile('../../code/php/Autoload.php'); // path to Data2Html autoloader
    // Data2Html\Autoload::start();

    require_once "../../code/php/Autoload.php";
    Data2Html\Autoload::start();
