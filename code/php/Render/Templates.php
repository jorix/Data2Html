<?php
namespace Data2Html\Render;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Data\To;

class Templates
{
    private static $renderCount = 0;
    
    public static function apply($template, $replaces)
    {
        $replaces['_renderCount'] = self::$renderCount++;
    
        // Verify argument replaces
        if (!is_array($replaces)) {
            throw new DebugException("Argument \$replaces must be a array.",
                ['$replaces' => $replaces]
            );
        }
        
        // Apply if is callable
        if (is_callable($template)) {
            return $template($replaces);
        } 
        
        // Verify argument template
        if (!is_array($template)) {
            throw new DebugException("Argument \$template must be a array.",
                ['$template' => $template]
            );
        }
        
        // Get content
        if (array_key_exists('html', $template)) {
            $html = FileContents::getContent($template['html']);
        } else {
            $html = '';
        }
        if (array_key_exists('js', $template)) {
            $js = FileContents::getContent($template['js']);
        } else {
            $js = '';
        }
        
        // Apply replaces
        $finalJs = '';
        $finalReplaces = [];
        foreach($replaces as $k => $v) {
            if (is_array($v) && array_key_exists('d2hToken_content', $v)) {
                if (array_key_exists('html', $v)) {
                    $html = self::renderHtml($html, [$k => $v['html']]);
                } 
                if (array_key_exists('js', $v)) {
                    $finalJs .= $v['js'] . "\n";
                }
            } else {
                $finalReplaces[$k] = $v;
            }
        }
        $result = [];
        if ($html) {
            $result['html'] = self::renderHtml($html, $finalReplaces);
        }
        if ($js) {
            $js = self::renderJs($js, $finalReplaces, false);
        }
        if ($js || $finalJs) {
            $result['js'] = $js . $finalJs;
        }
        $result['d2hToken_content'] = true;
        return $result;
    }
    
    public static function concat(&$final, $item) {
        if (!$final) {
            $final = ['html' => ''];
        }
        foreach($item as $k => $v) {
            if (array_key_exists($k, $final)) {
                $final[$k] .= $item[$k];
            } else {
                $final[$k] = $item[$k];
            }
        }
        $final['d2hToken_content'] = true;
    }
 
    // TODO: Remove comments after solve parse a empty form filter!!!!!
    // TODO: Remove this function
    public static function renderEmpty()
    {
        return ['d2hToken_content' => true];
    }
    
    private static function renderHtml($html, $replaces)
    {
        // Conditional: $${data-item?[[yes]]:[[no]]} or $${data-item?[[only-yes]]}
        $html = self::replaceConditional($replaces, $html);

        // Html attribute: <elem attr-name="$${data-item}" ...
        $html = self::replaceContent( 
            '/[a-z][\w-]*\s*=\s*\"\$\$\{([a-z][\w\-]*)(|\s*\|\s*.*?)\}\"/i',
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
            '/\$\$\{([a-z][\w\-]*)(|\s*\|\s*.*?)\}/i', $replaces,
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
            '/["\']#\$\$\{([a-z][\w\-]*)(|\s*\|\s*.*?)\}/i',
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
            '/["\']\$\$\{([a-z][\w\-]*)(|\s*\|\s*.*?)\}/i',
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
            '/\$\$\{([a-z][\w\-]*)(|\s*\|\s*.*?)\}/i',
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
    
    private static function replaceConditional($replaces, $content)
    {
        $pattern = '/\$\$\{([a-z][\w\-]*)\?\[\[(.*?)\]\](|:\[\[(.*?)\]\])\}/i';
        $matches = null;
        preg_match_all($pattern, $content, $matches);
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $k = $matches[1][$i];
            $data = Lot::getItem($k, $replaces);
            if (is_string($data)) {
                $data = trim($data);
            }
            if ($data === null || $data === '') {
                $value = $matches[4][$i];
            } else {
                $value = $matches[2][$i];
            }
            $content = str_replace($matches[0][$i], $value, $content);
            //$content .= '|' . $data;
        }
        return $content;
    }
}
