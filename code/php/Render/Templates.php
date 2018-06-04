<?php
namespace Data2Html\Render;

class Templates
{
    use \Data2Html\Debug;

    private static $renderCount = 0;
    
    public static function apply($template, $replaces)
    {
        if (is_callable($template)) {
            return $template($replaces);
        } elseif (array_key_exists('html', $template)) {
            $html = FileContents::getContent($template['html']);
        } else {
            $html = '';
        }
        $replaces['_renderCount'] = self::$renderCount++;
        $js = '';
        $finalReplaces = array();
        foreach($replaces as $k => $v) {
            if (is_array($v) && array_key_exists('d2hToken_content', $v)) {
                if (array_key_exists('html', $v)) {
                    $html = str_replace(
                        '$${' . $k . '}',
                        $v['html'],
                        $html
                    );
                } 
                if (array_key_exists('js', $v)) {
                    $js .= $v['js'];
                }
            } else {
                $finalReplaces[$k] = $v;
            }
        }
        $result = array();
        if ($html) {
            $result['html'] = self::renderHtml($html, $finalReplaces);
        }
        if (array_key_exists('js', $template)) {
            $js = self::renderJs(
                FileContents::getContent($template['js']),
                $finalReplaces,
                false
            ) . $js;
        }
        if ($js) {
            $result['js'] = $js;
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
        $html = self::replaceContent( // <elem attribute="$${template_item}" ...
            '/\w[\w-]*\s*=\s*\"\$\$\{(\w[\w\-]*)(\|*\w*-*)\}\"/',
            $replaces,
            function($matchItem, $value) { // $encodeFn
                if ($value) {
                    $posEq = strpos($matchItem, '=');
                    if (is_array($value)) {
                        $value = str_replace('"', "'", Data2Html_Value::toJson($value));
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
        $html = self::replaceContent( // others ...
            '/\$\$\{([\w.:]+)\}/', $replaces,
            function($matchItem, $value) { // $encodeFn
                return $value;
            },
            $html
        );
        $html = preg_replace("/\r\n\s*\r\n/", "\r\n", $html); // Windows CrLf
        $html = preg_replace("/\n\s*\n/", "\n", $html); // linux Lf
        $html = preg_replace("/\r\s*\r/", "\r", $html); // iOs Cr
        return $html;
    }

    private static function renderJs($js, $replaces)
    {
        $js = self::replaceContent( // start string '$${template_item}...
            // '/["\']([^"\'\$]*)\$\$\{([\w.:-]+)\}/',
            '/["\']\$\$\{([\w.:-]+)\}/',
            $replaces,
            function($matchItem, $value) { // $encodeFn
                if (!is_array($value)) {
                    $v = Data2Html_Value::toJson($value);
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
        $js = self::replaceContent( // start string '$${template_item}...
            // '/["\']([^"\'\$]*)\$\$\{([\w.:-]+)\}/',
            '/["\']#\$\$\{([\w.:-]+)\}/',
            $replaces,
            function($matchItem, $value) { // $encodeFn
                if (!is_array($value)) {
                    if (is_string($value)) {
                        // remove quotes
                        $v = substr(Data2Html_Value::toJson('#' . $value), 1, -1);
                    } else {
                        $v = Data2Html_Value::toJson($value);
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
        $js = self::replaceContent( // others ...
            '/\$\$\{([\w.:]+)\}/',
            $replaces,
            function($matchItem, $value) { // $encodeFn
                if (is_array($value) && count($value) === 0) {
                    // array as js object
                    return '{}';
                } else {
                    return Data2Html_Value::toJson($value);
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
                $encodedVal = $encodeFn($matches[0][$i], $replaces[$k]);
                $match = $matches[0][$i];
                if (is_array($encodedVal)) {
                    throw new Data2Html_Exception(
                        "Value of match \"{$match}\" is array, must be a string.",
                        array(
                            'value' => $encodedVal,
                            'matches' => $matches
                        )
                    );
                }
                $content = str_replace($match, $encodedVal, $content);
            }
        }
        return $content;
    }
}
