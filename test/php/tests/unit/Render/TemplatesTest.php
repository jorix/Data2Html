<?php
use Data2Html\Render\Templates;

use Codeception\Scenario;

class TemplatesTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;
    
    // Tests
    public function test_renderHtml()
    {
        $ref = new \ReflectionClass('\\Data2Html\\Render\\Templates');
        //test renderHtml
        $renderHtml = $ref->getMethod('renderHtml');
        $renderHtml->setAccessible(true);
        
        $this->assertEquals('>xy<', 
            $renderHtml->invoke(null, '>$${a}<', ['a' => 'xy']),
            'Simple replace'
        );
        $this->assertEquals('<span some ="null">', 
            $renderHtml->invoke(null, '<span some = "$${a}">', ['a' => 'null']),
            'Replace a scalar on attribute'
        );
        $this->assertEquals('<span some ="{\'is\':\'null\'}">', 
            $renderHtml->invoke(null, '<span some = "$${a}">', ['a' => ['is' => 'null']]),
            'Replace a array on attribute'
        );
        $this->assertEquals('<span >', 
            $renderHtml->invoke(null, '<span some = "$${a}">', ['a' => null]),
            'Remove attribute if is empty'
        );
        $this->assertEquals('-<span>yes</span>-', 
            $renderHtml->invoke(null, '-$${a?[[<span>$${a}</span>]]:[[<no>]]}-', ['a' => 'yes']),
            'Conditional html'
        );
        $this->assertEquals('-<no>-', 
            $renderHtml->invoke(null, '-$${a?[[<span>$${a}</span>]]:[[<no>]]}-', ['a' => null]),
            'Conditional html'
        );
    }
    
    public function test_renderJs()
    {
        $ref = new \ReflectionClass('\\Data2Html\\Render\\Templates');
        //test renderJs
        $renderJs = $ref->getMethod('renderJs');
        $renderJs->setAccessible(true);
        
        $this->assertEquals('xx = "xy";', 
            $renderJs->invoke(null, 'xx = $${a};', ['a' => 'xy']),
            'Simple replace as string'
        );
        $this->assertEquals('xx = 123;', 
            $renderJs->invoke(null, 'xx = $${a};', ['a' => 123]),
            'Simple replace as number'
        );
        $this->assertEquals('xx = {"is":"null"};', 
            $renderJs->invoke(null, 'xx = $${a};', ['a' => ['is' => 'null']]),
            'Simple replace as array'
        );
        $this->assertEquals('var xx = "data and more text";', 
            $renderJs->invoke(null, 'var xx = "$${a} and more text";', ['a' => 'data']),
            'Replace on start string'
        );
        $this->assertEquals('var xx = "#data-id";', 
            $renderJs->invoke(null, 'var xx = "#$${a}-id";', ['a' => 'data']),
            'Replace on start string as css id'
        );
        $this->assertEquals('var xx = 0;', 
            $renderJs->invoke(null, 'var xx = $${a?[[$${a}]]:[[false]]};', ['a' => 0]),
            'Conditional js'
        );
        $this->assertEquals('var xx = false;', 
            $renderJs->invoke(null, 'var xx = $${a?[[$${a}]]:[[false]]};', ['a' => null]),
            'Conditional js'
        );
    }
}
