<?php
namespace Data2Html\Render;

use Data2Html\DebugException;
use Data2Html\Config;
use Data2Html\Render\Branch;
use Data2Html\Render\Content;

class Dependencies
{
    use \Data2Html\Debug;
    
    protected $templateSource = null;
    protected $usedSources = null;
    protected $finaSources = null;
    
    public function __construct()
    {
        $this->templateSource = new Branch(Config::get('templateSourceName'));
    }
    
    public function getSource(Content $sources)
    {
        $items = array_merge(
            $sources->get('include'),
            $sources->get('require')
        );
        $finalSources = $this->sort($items);
        $finalHtml = new Content();
        
        foreach($finalSources as $v) {
            if (Config::debug()) {
                $finalHtml->add([
                    'html' => "\n<!-- sourceName: \"{$v}\" -->"
                ], []);
            }
            $finalHtml->add(
                $this->templateSource->getItem($v),
                ['base' => 'vise'],
                false
            );
        }
        if (Config::debug()) {
            $finalHtml->add([
                'html' => "\n<!-- end of sources -->\n"
            ], []);
        }
        return $finalHtml->get('html');
    }
    
    protected function sort($items)
    {
        $this->usedSources = [];
        $this->finaSources = [];
        foreach($items as $v) {
            $this->addItem($v);
        }
        return array_keys($this->finaSources);
    }
    
    protected function addItem($name)
    {    
        if (!array_key_exists($name, $this->usedSources)) {
            $this->usedSources[$name] = true;
            $item = $this->templateSource->getItem($name);
            if (!$item) {
                throw new DebugException(
                    "Source name \"{$name}\" not exist on configured 'templateSourceName'."
                );
            }
            if (array_key_exists('require', $item)) {
                $requires = (array)$item['require'];
                foreach($requires as $v) {
                    $this->addItem($v);
                }
            }
            if (array_key_exists('include', $item)) {
                $includes = (array)$item['include'];
                foreach($includes as $v) {
                    $this->addItem($v);
                }
            }
        }
        $this->finaSources[$name] = true; 
    }
}
