<?php
use Data2Html\Render\Branch;

use Codeception\Scenario;

class BranchTest extends \Codeception\Test\Unit
{
    use \Codeception\Specify;

    // Tests
    public function testBranch()
    {
        $this->specify("Throw error if argument is not a array.", function() {
            $this->tester->expectException(
                new \Exception(
                    'Argument $tree must be a array.'
                ), 
                function() {
                    new Branch(null);
                }
            );
            
        });
        $tt = $this->construct('Data2Html\\Render\\Branch', [
            ['a' => [
                'b' => [
                    'c' => 'file',
                    'd' => []
                ]
            ]]
        ], [
            'json' => function () { return json_encode($this->tree); }
        ]);
        $t = new Branch(['a' => [
            'b' => [
                'c' => 'file',
                'd' => []
            ]
        ]]);
        $this->assertEquals(
            '{"c":"file","d":[]}',
            json_encode($t->getBranch(['a', 'b'], $t, true)->__debugInfo()),
            'Get nested branch'
        );
        $this->assertEquals(
            '{"c":"file","d":[]}',
            json_encode($tt->getBranch(['a', 'b'], $t, true)->__debugInfo()),
            'Get nested branch brix'
        );

    }
}
