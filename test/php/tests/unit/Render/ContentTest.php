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
            $renderHtml->invoke(null, ['a' => 'xy'], '>$${a}<'),
            'Simple replace'
        );
        $this->assertEquals('<span some ="null">', 
            $renderHtml->invoke(null, ['a' => 'null'], '<span some = "$${a}">'),
            'Replace a scalar on attribute (NOTE: spaces affter `=` are removed)'
        );
        $this->assertEquals('<span some="0">', 
            $renderHtml->invoke(null, ['a' => 0], '<span some="$${a}">'),
            'Replace 0 on attribute'
        );
        $this->assertEquals('<span some="false">', 
            $renderHtml->invoke(null, ['a' => false], '<span some="$${a}">'),
            'Replace false on attribute'
        );
        $this->assertEquals("<span some =\"{'is':'null'}\">", 
            $renderHtml->invoke(null, ['a' => ['is' => 'null']], '<span some = "$${a}">'),
            'Replace a array as pseudo-json on attribute'
        );
        $this->assertEquals('<span >', 
            $renderHtml->invoke(null, ['a' => ''], '<span some = "$${a}">'),
            'Remove attribute if is empty'
        );
        $this->assertEquals('<span >', 
            $renderHtml->invoke(null, ['a' => null], '<span some = "$${a}">'),
            'Remove attribute if is null'
        );
        $this->assertEquals('-<span>yes</span>-', 
            $renderHtml->invoke(null, ['a' => 'yes'], '-$${a?[[<span>$${a}</span>]]:[[<no>]]}-'),
            'Conditional html'
        );
        $this->assertEquals('-<no>-', 
            $renderHtml->invoke(null, ['a' => null], '-$${a?[[<span>$${a}</span>]]:[[<no>]]}-'),
            'Conditional with null replacement on html uses else content'
        ); 
        $htmlLf = '$${_level-0?[[
            <td class="class">body</td>
        ]]:[[
            <span>body</span>
        ]]}';
        $this->assertEquals('<td class="class">body</td>', 
            trim($renderHtml->invoke(null, ['_level-0' => true], $htmlLf)),
            'Conditional html whith line feed whith true'
        );
        $this->assertEquals('<span>body</span>', 
            trim($renderHtml->invoke(null, ['_level-0' => false], $htmlLf)),
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
            $renderJs->invoke(null, ['a' => 'xy'], 'xx = $${a};'),
            'Simple replace as string'
        );
        $this->assertEquals('xx = 123;', 
            $renderJs->invoke(null, ['a' => 123], 'xx = $${a};'),
            'Simple replace as number'
        );
        $this->assertEquals('xx = {"is":"null"};', 
            $renderJs->invoke(null, ['a' => ['is' => 'null']], 'xx = $${a};'),
            'Simple replace as array'
        );
        $this->assertEquals('var xx = "data and more text";', 
            $renderJs->invoke(null, ['a' => 'data'], 'var xx = "$${a} and more text";'),
            'Replace on start string'
        );
        $this->assertEquals('var xx = "#data-id";', 
            $renderJs->invoke(null, ['a' => 'data'], 'var xx = "#$${a}-id";'),
            'Replace on start string as css id'
        );
        $this->assertEquals('var xx = 0;', 
            $renderJs->invoke(null, ['a' => 0], 'var xx = $${a?[[$${a}]]:[[false]]};'),
            'Conditional js'
        );
        $this->assertEquals('var xx = false;', 
            $renderJs->invoke(null, ['a' => null], 'var xx = $${a?[[$${a}]]:[[false]]};'),
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
                ' $${a} $${require Alpha} $${b} $${require epsilon, Alpha, beta} $${c} ',
                &$requires
            ]),
            'Correct requires are obtained'
        );
        $this->assertEquals(
            ['omega' => true, 'Alpha' => true, 'epsilon' => true, 'beta' => true],
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
