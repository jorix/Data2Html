<?php
use Data2Html\Data\Lot;

use Codeception\Scenario;

class LotTest extends \Codeception\Test\Unit
{
    // Tests
    public function testGetItem()
    {
        $tree = ['a' => ['b' => ['c' => 'final']]];
        $this->assertEquals(
            ['c' => 'final'],
            Lot::getItem(['a', 'b'], $tree),
            'Get item using nested keys'
        );
    }
}
