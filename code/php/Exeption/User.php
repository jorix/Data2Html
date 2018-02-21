<?php

class Data2Html_Exception_User extends Data2Html_Exception
{
    public function __construct($userMsg, $data = array(), $code = 0)
    {
        return parent::__construct($userMsg, $code);
    }

    public function getUserMsg()
    {
        return $this->getMessage();
    }
}