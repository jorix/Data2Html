<?php
use Data2Html\Render\Content;

use Codeception\Scenario;

class ContentTest extends \Codeception\Test\Unit
{
     // Tests
    public function testObject()
    {
        $contentEmpty = new Content();
        $this->assertEquals('', $contentEmpty->get(), 'Empty content');
        
        $contentHtml = new Content(['html' => '<b>$${B}-html</b>'], ['B' => 'Strong']);
        $this->assertEquals('<b>Strong-html</b>', $contentHtml->get(), 'Simple html replace');
        
        $contentMix = new Content(
            ['html' => '<b>$${B}-mix</b>', 'js' => ' var bb = "#$${B}";'],
            ['B' => 'Strong']
        );
        $jsExpected =' var bb = "#Strong";';
        $this->assertEquals(
            "<b>Strong-mix</b>\n<script>\n{$jsExpected}\n</script>\n",
            $contentMix->get(), 
            'Replace html and js'
        );
        
        $contentMix->add(['html' => '<b>$${B}-more</b>'], ['B' => 'blue']);
        $this->assertEquals(
            "<b>Strong-mix</b><b>blue-more</b>\n<script>\n{$jsExpected}\n</script>\n",
            $contentMix->get(), 
            'Add more html to mix'
        );
        $this->assertEquals("<b>Strong-mix</b><b>blue-more</b>",
            $contentMix->get('html'), 
            'Get only html from mix'
        );
        $this->assertEquals($jsExpected, $contentMix->get('js'), 'Get only js from mix');
        
        $contentContent = new Content([
                'html' => '$${require a}<b>$${test-body}-html</b>',
                'js' => '// \'$${A}\'$${require void-ok}'
            ], [
                'A' => 'Alpha',
                'test-body' => new Content([
                        'html' => '<span>$${C}</span>',
                        'js' => '// "$${Z}"$${require x, js}'
                    ], [
                        'C' => 'Text',
                        'Z' => 'Omega'
                ])
        ]);
        $this->assertEquals(
            "<b><span>Text</span>-html</b>\n<script>\n// 'Alpha'\n// \"Omega\"\n</script>\n", 
            $contentContent->get(), 'Nested html+js replace'
        );
        $req = $contentContent->get('require');
        sort($req);
        $this->assertEquals(
            ['a', 'js', 'void-ok', 'x'], 
            $req, 
            'Nested requires replace'
        );
    }
    
    public function testRenderHtml()
    {
        $ref = new \ReflectionClass('\\Data2Html\\Render\\Content');
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
            'Conditional with null replacement on html uses else content'
        ); 
        $htmlLf = '$${_level-0?[[
            <td class="class">body</td>
        ]]:[[
            <span>body</span>
        ]]}';
        $this->assertEquals('<td class="class">body</td>', 
            trim($renderHtml->invoke(null, $htmlLf, ['_level-0' => true])),
            'Conditional html whith line feed whith true'
        );
        $this->assertEquals('<span>body</span>', 
            trim($renderHtml->invoke(null, $htmlLf, ['_level-0' => false])),
            'Conditional html whith line feed whith false'
        );
    }
    
    public function testRenderJs()
    {
        $ref = new \ReflectionClass('\\Data2Html\\Render\\Content');
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
            'Conditional with null replacement on js uses else content'
        );
    }
    
    public function testExtractRequire()
    {
        $ref = new \ReflectionClass('\\Data2Html\\Render\\Content');
        //test renderJs
        $extractSource = $ref->getMethod('extractSource');
        $extractSource->setAccessible(true);
        
        $requires = ['omega' => true];
        $this->assertEquals(' $${a}  $${b}  $${c} ', 
            $extractSource->invokeArgs(null, [
                'require',
                ' $${a} $${require Alpha} $${b} $${require epsilon, alpha, beta} $${c} ',
                &$requires
            ]),
            'Correct requires are obtained'
        );
        $this->assertEquals(
            ['omega' => true, 'alpha' => true, 'epsilon' => true, 'beta' => true],
            $requires,
            'Correct requires are obtained and all names are in lower case'
        );
    }
    
    public function testExtractScripts()
    {
        $ref = new \ReflectionClass('\\Data2Html\\Render\\Content');
        //test renderJs
        $extractScripts = $ref->getMethod('extractScripts');
        $extractScripts->setAccessible(true);
        
        $this->assertEquals(['if (a<b) if (a>b)', 'html'], 
            $extractScripts->invoke(null, 'ht<script >if (a<b) if (a>b)</script>ml'),
            'Correct js scripts are obtained'
        );
    }
}
