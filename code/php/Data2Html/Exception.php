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
}