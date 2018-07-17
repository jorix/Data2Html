<?php
namespace Data2Html\Data;

use Data2Html\Config;
use Data2Html\Data\To;

class Response
{
    public static function json($obj)
    {
        $debug = Config::debug();
        if ($debug && isset($_REQUEST['debug'])) {
            echo "<pre>\n" . To::json($obj, $debug) . "\n</pre>\n";
        } else {
            header('Content-type: application/responseJson; charset=utf-8;');
            echo To::json($obj, $debug);
        }
    }

    public static function js($src)
    {
        $debug = Config::debug();
        if ($debug && isset($_REQUEST['debug'])) {
            echo "<pre>\n" . $src . "\n</pre>\n";
        } else {
            header('Content-type: application/responseJson; charset=utf-8;');
            echo $src;
        }
    }
}
