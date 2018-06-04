<?php
use Data2Html\Render\Branch;

use Codeception\Scenario;

class BranchTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    // Tests
    public function testBranch()
    {
        // NOTE: $this->tester->expectException not fails if no exception is throw, oops!
        try {
            $fail = false;
            new Branch(null);
        } catch (\Exception $e) { $fail = true; }
        $this->assertTrue($fail, "Exception is throw when construct argument is not a array");
            
        // Dummy branch
        $br = new Branch(['a' => [
            'b' => [
                'c' => 'file',
                'd' => []
            ]
        ]]);
        $this->specify("Retrieve a existing branch.", function() use ($br) {
            // Expend response as sub-branch
            $rJson = '{"keys":["a","b"],"tree":{"c":"file","d":[]}}'; 
            $this->assertEquals($rJson,
                json_encode($br->getBranch(['a', 'b'],true)->__debugInfo()),
                'Get nested branch.'
            );
            $this->assertEquals($rJson,
                json_encode($br->getBranch('a')->getBranch('b')->__debugInfo()),
                'Get recursive branch.'
            );
        });

        $this->specify("Retrieve a not existing branch.", function() use ($br) {
            try {
                $fail = false;
                $br->getBranch('?');
            } catch (\Exception $e) { $fail = true; }
            $this->assertTrue($fail, "Exception is throw");
            
            $this->assertNull(
                $br->getBranch('?', false),
                'Not existing branch if is not required is null.'
            );
        });
    }
}
