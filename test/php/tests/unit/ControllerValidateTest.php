<?php

class ControllerValidateTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    
    protected function _before()
    {
        require 'start-test.php';
    }

    protected function _after()
    {
    }

    // tests
    public function testValidateValue()
    {
        $val = new Data2Html_Controller_Validate('dummy');
        $this->assertEquals('bill', $val->validateValue('x', ['required' => true]));
        
    }
}