<?php

class Data2Html_Exception extends Exception
{
    protected $data;

    public function __construct($message, $data = array(), $code = 0)
    {
        if (!is_int($code)) {
            $data['error_code'] = $code;
            $code = 0;
        }
        $this->data = $data;
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
                $response['error'] .= ' [ code: '.$exception->getCode().' ]';
            }
            // Exception to debug
            $exeptionData = array();
            $exeptionData['fileLine'] = $exception->getFile().
                ' [ line: '.$exception->getLine().' ]';
            $exeptionData['trace'] = explode("\n", $exception->getTraceAsString());
            if ($exception instanceof Data2Html_Exception) {
                $exeptionData['data'] = $exception->getData();
            }
            $response['exception'] = $exeptionData;
        } else {
            $response['error'] =
                'An unexpected error has stopped the execution on the server.';
        }
        return $response;
    }
    public static function toHtml($exception, $debug)
    {
        $exData = Data2Html_Exception::toArray($exception, $debug);
        $html = '<h3>Error: <span style="color:red">'.
                    $exData['error'].'</span></h3>';
        if (isset($exData['exception'])) {
            $html .= '<div style="margin-left:1em">Exception:<pre>' .
                Data2Html_Value::toJson($exData['exception'], $debug) .
                '</pre></div>';
        }
        return $html;
    }
}
