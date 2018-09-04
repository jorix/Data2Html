<?php
namespace Data2Html\Render;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Data\To;
use Data2Html\Render\Dependencies;

class Content
{
    use \Data2Html\Debug;

    private static $renderCount = 0;
    
    protected $content;
    
    public function __construct($template = null, $replaces = [], $extractJs = true)
    {
        // Verify argument replaces
        if (!is_array($replaces)) {
            throw new DebugException("Argument \$replaces must be a array.",
                ['$replaces' => $replaces]
            );
        }
        $replaces['_renderCount'] = self::$renderCount++;
        
        if (is_callable($template)) {
        // Apply if is callable
            $response = $template($replaces);
            if (!$response instanceof Content) {
                throw new DebugException(
                    "Template as function must return a \\Data2Html\\Render\\Content.",
                    [
                        'returned' => $this->content,
                        'replaces' => $replaces
                    ]
                );
            }
            $this->content = $response->content;
        } elseif (is_null($template)) {
        // Set empty if is null
            $this->content = [];
        } elseif (!is_array($template)) {
        // Verify template is an array
            throw new DebugException(
                "Argument \$template must be a array null or a function.",
                ['$template' => $template]
            );
        } else {
        // Apply replaces on a template as array ['html' => ..., 'js' => ...]
            // Get content html+js
            $requires = self::getTemplateSource('require', $template);
            $includes = self::getTemplateSource('include', $template);
            if (array_key_exists('html', $template)) {
                $html = self::extractSource('require', $template['html'], $requires);
                $html = self::extractSource('include', $html, $includes);
            } else {
                $html = '';
            }
            if (array_key_exists('js', $template)) {
                $js = self::extractSource('require', $template['js'], $requires);
                $js = self::extractSource('include', $js, $includes);
            } else{
                if ($extractJs) {
                    list($js, $html) = self::extractScripts($html);
                } else {
                    $js = '';
                }
            }
            
            
            // Apply replaces
            $finalJs = '';
            foreach ($replaces as $k => $v) {
                if ($v instanceof self) {
                    if (array_key_exists('html', $v->content)) {
                        $replaces[$k] = $v->content['html'];
                    } 
                    if (array_key_exists('js', $v->content)) {
                        $finalJs .= "\n" . $v->content['js'] ;
                    }
                    if (array_key_exists('require', $v->content)) {
                        $requires += $v->content['require'];
                    }
                    if (array_key_exists('include', $v->content)) {
                        $includes += $v->content['include'];
                    }
                }
            }
            $this->content = [];
            if ($html) {
                $html = self::renderHtml($html, $replaces);
                $values = [];
                $html = self::extractValues($html, $values);
                $this->content['html'] = $html;
            }
            if ($js) {
                $js = self::renderJs($js, $replaces, false);
            }
            if ($js || $finalJs) {
                $this->content['js'] = $js . $finalJs;
            }
            if (count($requires) > 0) {
                $this->content['require'] = $requires;
            }
            if (count($includes) > 0) {
                $this->content['include'] = $includes;
            }
        }
        if (array_key_exists('id', $replaces)) {
            $this->content['id'] = $replaces['id'];
        }
    }
    
    public function __debugInfo()
    {
        return $this->content;
    }
    
    public function add($template = null, $replaces = null, $extractJs = true) {
        if ($template instanceof self) {
            $item = $template;
        } else {
            $item = new Content($template, $replaces, $extractJs);
        }
        foreach ($item->content as $k => $v) {
            switch ($k) {
                case 'html':
                case 'js':
                    if (array_key_exists($k, $this->content)) {
                        $this->content[$k] .= $item->content[$k];
                    } else {
                        $this->content[$k] = $item->content[$k];
                    }
                    break;
                case 'require':
                case 'include':
                    if (!array_key_exists($k, $this->content)) {
                        $this->content[$k] = [];
                    }
                    $this->content[$k] += $item->content[$k];
                    break;
            }
        }
    }
    
        
    public function repeat($valueLlist)
    {
        foreach ($this->content as $k => &$v) {
            switch ($k) {
                case 'html':
                case 'js':
                    $v = self::repeatContent($v, $valueLlist);
                    break;
            }
        }
        unset($v);
    }
    
    public function get($key = null)
    {
        switch ($key) {
            case null:
                $response = Lot::getItem('html', $this->content, '');
                $js = Lot::getItem('js', $this->content, '');
                if ($js) {
                    $response .= "\n<script>\n{$js}\n</script>\n"; 
                }
                return $response;
            case 'require':
            case 'include':
                return array_keys(Lot::getItem($key, $this->content, []));
            default:
                return Lot::getItem($key, $this->content, '');
        }
    }
   
    public function getSource($replaces)
    {
        $dependencies = new Dependencies();
        return $dependencies->getSource($this, $replaces);
    }
    
    private static function renderHtml($html, $replaces)
    {
        // Conditional: $${data-item?[[yes]]:[[no]]} or $${data-item?[[only-yes]]}
        $html = self::replaceConditional($replaces, $html);
        
        // Html attribute: <elem attr-name="$${data-item}" ...
        $html = self::replaceContent( 
            '/[a-z][\w-]*\s*=\s*\"\$\$\{([a-z_][\w\-]*)(|\s*\|\s*.*?)\}\"/i',
            $replaces,
            function($matchItem, $format, $value) { // $encodeFn
                if ($value) {
                    $posEq = strpos($matchItem, '=');
                    if (is_array($value)) {
                        $value = str_replace('"', "'", To::json($value));
                    }
                    return substr($matchItem, 0, $posEq) . '="' . 
                        htmlspecialchars(
                            $value, 
                            ENT_COMPAT | ENT_SUBSTITUTE,
                            'UTF-8'
                        ) .
                        '"';
                } else {
                    return '';
                }
            },
            $html
        );
        
        // Html: $${data-item}
        $html = self::replaceContent( 
            '/\$\$\{([a-z_][\w\-]*)(|\s*\|\s*.*?)\}/i', $replaces,
            function($matchItem, $format, $value) { // $encodeFn
                return $value;
            },
            $html
        );
        
        // remove extra blank lines.
        $html = preg_replace("/\r\n\s*\r\n/", "\r\n", $html); // Windows CrLf
        $html = preg_replace("/\n\s*\n/", "\n", $html); // linux Lf
        $html = preg_replace("/\r\s*\r/", "\r", $html); // iOs Cr
        return $html;
    }

    private static function renderJs($js, $replaces)
    {
        // Conditional: $${data-item?[[yes]]:[[no]]} or $${data-item?[[only-yes]]}
        $js = self::replaceConditional($replaces, $js);

        // Start string of css id: "#$${data-item | format}...
        $js = self::replaceContent(
            '/["\']#\$\$\{([a-z_][\w\-]*)(|\s*\|\s*.*?)\}/i',
            $replaces,
            function($matchItem, $format, $value) { // $encodeFn
                if (!is_array($value)) {
                    $v = To::json("#" . $value);
                    if (is_string($value)) {
                        // remove quotes
                        $v = substr($v, 1, -1);
                    }
                    return substr($matchItem, 0, 1) .$v;
                } else {
                    return 
                        substr($matchItem, 0, 1) . 
                        'd2h_error: this value is an array()!';
                }
            },
            $js
        );

        // Start string: "$${data-item | format}...
        $js = self::replaceContent(
            '/["\']\$\$\{([a-z_][\w\-]*)(|\s*\|\s*.*?)\}/i',
            $replaces,
            function($matchItem, $format, $value) { // $encodeFn
                if (!is_array($value)) {
                    if (is_string($value)) {
                        // remove quotes
                        $v = substr(To::json($value), 1, -1);
                    } else {
                        $v = To::json($value);
                    }
                    return substr($matchItem, 0, 1) . $v;
                } else {
                    return 
                        substr($matchItem, 0, 1) . 
                        'd2h_error: this value is an array()!';
                }
            },
            $js
        );
        // Others: $${data-item | format}...
        $js = self::replaceContent(
            '/\$\$\{([a-z_][\w\-]*)(|\s*\|\s*.*?)\}/i',
            $replaces,
            function($matchItem, $format, $value) { // $encodeFn
                if (is_array($value) && count($value) === 0) {
                    // array as js object
                    return '{}';
                } else {
                    return To::json($value);
                }
            },
            $js
        );
        $js = preg_replace("/\r\n\s*\r\n/", "\r\n", $js); // Windows CrLf
        $js = preg_replace("/\n\s*\n/", "\n", $js); // linux Lf
        $js = preg_replace("/\r\s*\r/", "\r", $js); // iOs Cr
        return $js;
    }
    
    private static function replaceContent($pattern, $replaces, $encodeFn, $content)
    {
        $matches = null;
        preg_match_all($pattern, $content, $matches);
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $k = $matches[1][$i];
            if (array_key_exists($k, $replaces)) {
                $match = $matches[0][$i];
                $encodedVal = $encodeFn($match, trim($matches[2][$i], ' |'), $replaces[$k]);
                if (is_array($encodedVal)) {
                    throw new ExceptionDebug(
                        "Value of match \"{$match}\" is array, must be a string.",
                        [
                            'value' => $encodedVal,
                            'matches' => $matches
                        ]
                    );
                }
                $content = str_replace($match, $encodedVal, $content);
            }
        }
        return $content;
    }
    
    /**
     * Replace conditional, possible patterns are:  
     *      $${data-item?[[yes]]:[[no]]} or $${data-item?[[only-yes]]}
     */
    private static function replaceConditional($replaces, $content)
    {
        $pattern = '/\$\$\{([a-z_][\w\-]*)\?\[\[(.*?)\]\](|:\[\[(.*?)\]\])\}/i';
        $matches = null;
        preg_match_all($pattern, str_replace("\n", "{_n_}", $content), $matches);
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $k = $matches[1][$i];
            $data = Lot::getItem($k, $replaces);
            if (is_string($data)) {
                $data = trim($data);
            }
            if ($data === null || 
                $data === '' ||
                $data === false || 
                ($data instanceof self && count($data->content) === 0)
            ) { // Is empty
                $value = str_replace("{_n_}", "\n", $matches[4][$i]);
            } else { // Has data
                $value = str_replace("{_n_}", "\n", $matches[2][$i]);
            }
            $content = str_replace(str_replace("{_n_}", "\n", $matches[0][$i]), $value, $content);
        }
        return $content;
    }
    
    /**
     * Extract values to &$values from a html text and return html without-it,
     * pattern are as:  
     *      $${value_name=value}
     */
    private static function extractValues($html, &$values)
    {
        $pattern = '/\$\$\{([a-z_][\w\-]*)\s*\=\s*(\w+)\w*\}/i';
        $matches = null;
        preg_match_all($pattern, $html, $matches);
        for ($i = 0, $count = count($matches[0]); $i < $count; $i++)
        {
            $values[$matches[1][$i]] = $matches[2][$i];
            $html = str_replace($matches[0][$i], '', $html);
        }
        return $html;
    }
           
    private static function extractScripts($html)
    {
        $pattern = '/<script(.*?)>(.*?)<\/script>/i';
        $matches = null;
        $script = [];
        preg_match_all($pattern, $html, $matches);
        for ($i = 0, $count = count($matches[0]); $i < $count; $i++)
        {
            $script[] = $matches[2][$i];
            $html = str_replace($matches[0][$i], '', $html);
        }
        return [implode("\n", $script), $html];
    }
    
 
    /**
     * Repeat content replacing with name as $contentName to &$subContent from a content
     * and return without-it, pattern are as:  
     *      $${repeat[[<option value="${[keys]}">${0}</option>]]}
     * Where ${[keys]} are replaced by keys of $valueList ans ${0} by item
     */
    private static function repeatContent($content, $valueList)
    {
        $pattern = '/\$\$\{repeat\[\[(.*?)\]\]}/i';
        $matches = null;
        $newSources = [];
        preg_match_all($pattern, $content, $matches);
        for ($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            // Replace al repeat
            $subContent = $matches[1][$i];
            $final = '';
            if ($valueList) {
                foreach ($valueList as $k => $v) {
                    $final .= str_replace(
                        ['${[keys]}', '${0}'],
                        [$k, $v],
                        $subContent
                    );
                }
            }
            $content = str_replace($matches[0][$i], $final, $content);
        }
        return $content;
    }
    
    /**
     * Add sources with name as $sorceName to &$sources from a content and return without-it,
     * return without-it, pattern are as:  
     *      $${source_name name} or $${source_name name1, neme2, ...}
     */
    private static function extractSource($sourceName, $content, &$sources)
    {
        $pattern = '/\$\$\{' . $sourceName . '\s+([a-z][\w\-\s,]*?)}/i';
        $matches = null;
        $newSources = [];
        preg_match_all($pattern, $content, $matches);
        for ($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $newSources[] = strtolower($matches[1][$i]);
            $content = str_replace($matches[0][$i], '', $content);
        }

        $final = array_map('trim', explode(',', implode(',', $newSources)));
        foreach ($final as $v) {
            if ($v) { // to ignore ''
                $sources[$v] = true;
            }
        }
        return $content;
    }
    
    private static function getTemplateSource($sourceName, $template)
    {
        $response = [];
        if (array_key_exists($sourceName, $template)) {
            $source = (array)$template[$sourceName];
            foreach ($source as $k => $v) {
                if (is_integer($k)) {
                    if ($v) {
                        $response[$v] = true;
                    }
                } else {
                    if ($k) {
                        $response[$k] = true;
                    }
                }
            }
        }
        return $response;
    }
}
