<?php

class Data2Html_Exception extends Exception
{
    protected $data;

    public function __construct($message, $data = array(), $code = 0)
    {
        if (!is_int($code)) {
            array_push($data, array('code'=>$code));
            $code = 0;
        }
        $this->data = $data;
        return parent::__construct($message, $code);
    }

    public function getData()
    {
        return $this->data;
    }
    public function __toString () 
    {
        $string = parent::__toString();
        $data = Data2Html_Utils::jsonEncode($this->data);
        // TODO: fix this
        return "Data2Html_Exception(data):<pre>\n{$data}\n</pre>{$string}";
    }
}