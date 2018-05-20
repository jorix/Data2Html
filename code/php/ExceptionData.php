<?php
namespace Data2Html;

use Data2Html\Data\To;

class ExceptionData extends \Exception
{
    protected $data;

    public function __construct($message, $data = array(), $code = 0)
    {
        $this->data = $data;
        if (!is_int($code)) {
            $this->data['error_code'] = $code;
            $code = 0;
        }
        return parent::__construct($message, $code);
    }

    public function getData()
    {
        return $this->data;
    }
    
    public static function toArray($exception, $debug)
    {
        $response = array();
        if ($debug) {
            // Error
            $response['error'] = $exception->getMessage();
            if ($exception->getCode()) {
                $response['error-code'] = $exception->getCode();
            }
            // Exception to debug
            $exeptionData = array();
            $exeptionData['fileLine'] = $exception->getFile().
                ' [ line: '.$exception->getLine().' ]';
            $exeptionData['trace'] = explode("\n", $exception->getTraceAsString());
            if ($exception instanceof \Data2Html_Exception || 
                $exception instanceof self
            ) {
                $debugData = $exception->getData();
                // Error code non numeric from debug-data
                if (isset($debugData['error_code'])) {
                    $response['error-code'] = $debugData['error_code'];
                    unset($debugData['error-code']);
                }
                $exeptionData['debug-data'] = $debugData;
            }
            // set exception
            $response['exception'] = $exeptionData;
        } else {
            $response['error'] =
                'An unexpected error has stopped this task on the server.';
        }
        return $response;
    }
    public static function toHtml($exception, $debug)
    {
        $exData = self::toArray($exception, $debug);
        $html = '<h3>Error: <span style="color:red">'.
                    $exData['error'].'</span></h3>';
        if (isset($exData['exception'])) {
            $html .= '<div style="margin-left:1em">Exception:<pre>' .
                // Break a html comments to preserve the <pre> block (they may
                // appear in some arguments calls)
                str_replace(
                    "<!--",
                    '</!--',
                    To::json($exData['exception'], $debug)
                ) .
                '</pre></div>';
        }
        return $html;
    }
}
