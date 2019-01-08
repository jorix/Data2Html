<?php
use Data2Html\Render\Branch;

use Codeception\Scenario;

class BranchTest extends \Codeception\Test\Unit
{
    // Tests
    public function testBranch()
    {
        // Test if a exception is throw.
        try {
            $fail = false;
            new Branch(null);
        } catch (\Exception $e) { $fail = true; }
        $this->assertTrue($fail, 'Exception is throw when construct argument is not a string or array');
            
        // Dummy branch
        $br = new Branch(['a' => [
            'b' => ['c' => 'file', 'd' => []]
        ]]);
        // Retrieve a existing branch: Extend response as sub-branch
        $expected = [
            'keys' => ['a','b'],
            'tree' => ['c' => 'file', 'd' => []]
        ]; 
        $this->assertEquals($expected,
            $br->getBranch(['a', 'b'],true)->__debugInfo(),
            'Get nested branch.'
        );
        $this->assertEquals($expected,
            $br->getBranch('a')->getBranch('b')->__debugInfo(),
            'Get recursive branch.'
        );

        // Retrieve a not existing branch
        try {
            $fail = false;
            $br->getBranch('?');
        } catch (\Exception $e) { $fail = true; }
        $this->assertTrue($fail, 'Exception is throw');
        $this->assertNull(
            $br->getBranch('?', false),
            'Not existing branch if is not required is null.'
        );
    }
}
