<?php

class Data2Html_Render_Template
{
    protected $debug;
    protected $templateName;
    protected $culprit;
    protected $templateTree;
    protected $templateContents;
    protected static $renderCount = 0;
    
    public function __construct($templateName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->templateName = $templateName;
        $this->culprit = "Template \"{$this->templateName}\"";
        $pathObj = $this->parsePath($templateName);
        
        $this->templateContents = array();
        $this->templateTree = $this->loadTemplate(
            $pathObj['dirname'],
            $pathObj['basename']
        );
    }
    
    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = $this->templateTree;
        }
        Data2Html_Utils::dump($this->culprit, $subject);
    }
    
    public function concatContents(&$final, $item) {
        foreach($item as $k => $v) {
            if (array_key_exists($k, $final)) {
                $final[$k] .= $item[$k];
            } else {
                $final[$k] = $item[$k];
            }
        }
        $final['d2hToken_content'] = true;
    }
    
    protected function loadTemplateTree($folder, $tree)
    {
        if (!is_array($tree)) {
             throw new Exception("{$this->culprit}: Tree must be a array!");
        }
        if (array_key_exists('folder', $tree)) {
            $folder = $this->setFolderPath($folder . $tree['folder']);
        }
        $response = array();
        foreach($tree as $k => $v) {
            switch ($k) {
                // Declarative words
                case 'folder':
                    break;
                case 'prefix':
                    $response[$k] = $v;
                    break;
                case '_description': // Ignore all: developer descriptions
                    break; 
                // Mount the tree
                case 'definitions':
                    $response['definitions'] = 
                        $this->loadDefinitions($folder, $tree['definitions']);
                    break;
                case 'template':
                    $response['template'] = 
                        $this->loadTemplate($folder, $tree['template']);
                    $response['template']['d2hToken_template'] = true;
                    break;
                case 'templates':
                    $items = array();
                    foreach($tree['templates'] as $kk => $vv) {
                        $items[$kk] = $this->loadTemplate($folder, $vv);
                        $items[$kk]['d2hToken_template'] = true;
                    }
                    $response['templates'] = $items;
                    break;
                case 'include':
                    $response += $this->loadTemplate($folder, $v);
                    break;
                case 'includes':
                    foreach($tree['includes'] as $vv) {
                         $response += $this->loadTemplate($folder, $vv);
                    }
                    break;
                default:
                    $response[$k] = $this->loadTemplateTree($folder, $v);
                    break;
            }
        }
        return $response;
    }
    
    protected function setFolderPath($folder)
    {
        if (strpos('/\\', substr($folder, -1, 1)) === false) {
            return $folder .= DIRECTORY_SEPARATOR;
        } else {
            return $folder;
        }
    }
    
    protected function loadDefinitions($folder, $definitionsFileName)
    {
        $fullFileName = $folder . $definitionsFileName;
        list($contentKey, $pathObj) = $this->loadContent($fullFileName);
        return $this->getContent($contentKey);
    }
    
    protected function loadTemplate($folder, $templateFileName)
    {
        $fullFileName = $folder . $templateFileName;
        $folder = $this->setFolderPath(dirname($fullFileName));
        list($contentKey, $pathObj) = $this->loadContent($fullFileName);
        switch ($pathObj['extension']) {
            case '.html':
                $response = array('html' => $contentKey);
                $jsFileName =
                    $pathObj['dirname'] . $pathObj['filename'] . '.js';
                if (!file_exists($jsFileName)) {
                    if ($pathObj['wrap'] === '') { // js not found
                        $jsFileName = '';
                    } else { // tray with wrap
                        $jsFileName .= $pathObj['wrap'];
                        if (!file_exists($jsFileName)) {
                            $jsFileName = '';
                        }
                    }
                }
                if ($jsFileName !== '') {
                    list($jsContentKey, $pathObj) =
                        $this->loadContent($jsFileName);
                    $response['js'] = $jsContentKey;
                }
                return $response;
            case '.js':
                return array('js' => $contentKey);
            case '.json':
                return $this->loadTemplateTree($folder, $this->getContent($contentKey));
            default:
                throw new Exception("{$this->culprit}: Extension \"{$pathObj['extension']}\" on template name \"{$fullFileName}\" is not supported.");
        }
    }

    protected function loadContent($fileName) {
        $cleanFileName = $this->cleanFileName($fileName);
        if (!array_key_exists($cleanFileName, $this->templateContents)) {
            if (!file_exists($fileName)) {
                throw new Exception(
                    "{$this->culprit}: The \"{$fileName}\" file does not exist."
                );
            }        
            $pathObj = $this->parsePath($fileName);
            if ($pathObj['extension'] !== '.php') {
                $content = file_get_contents($fileName);
            } else {
                $pathObj2 = $this->parsePath(
                    $pathObj['dirname'] . $pathObj['filename']
                );
                if (strpos('.html.js.json', $pathObj2['extension']) !== false) {
                // Is a wrapped text into a php died.
                    $content = file_get_contents($fileName);
                    $pathObj2['wrap'] = $pathObj['extension'];
                    $pathObj = $pathObj2;
                    $phpEnd = strpos($content, "?>\n");
                    if ($phpEnd === false) {
                        $phpEnd = strpos($content, "?>\r");
                    }
                    if ($phpEnd !== false) {
                        $content = substr($content, $phpEnd + 3);
                    }
                }
            }
            if ($this->debug && $pathObj['extension'] !== '.php') {
                $cleanFileName = $this->cleanFileName($fileName);
                $rCount = self::$renderCount++;
                switch ($pathObj['extension']) {
                    case '.html':
                        $content = 
                            "\n<!-- \"{$cleanFileName}\" #{$rCount}# [[ -->" .
                            $content .
                            "\n<!-- ]] #{$rCount}# -->\n";
                        break;
                    case '.js':
                        $content = 
                            "\n// S\"{$cleanFileName}\" #{$rCount}# [[" .
                            $content .
                            "\n// ]] #{$rCount}#\n";
                        break;
                }
            }
            switch ($pathObj['extension']) {
            case '.html':
            case '.js':
                break;
            case '.json':
                $content = json_decode($content, true);
                if ($content === null) {
                    throw new Exception("{$this->culprit}: Error parsing the json file: \"{$fileName}\"");
                }
                break;
            case '.php':
                $content = $this->getPhpReturn($fileName);
                break;
            default:
                throw new Exception("{$this->culprit}: Extension \"{$pathObj['extension']}\" on definitions name \"{$fileName}\" is not supported.");
            }
            $this->templateContents[$cleanFileName] = array($content, $pathObj);
        }
        return array(
            $cleanFileName,
            $this->templateContents[$cleanFileName][1] // the path info
        );
    }

        
    protected function getPhpReturn($fullFileName) {
        require $fullFileName;
        if (isset($return)) {
            return $return;
        } else {
            throw new Exception("{$this->culprit}: Error parsing the phpReturn file: \"{$fullFileName}\"");
        }
    }
    
    protected function cleanFileName($fileName) {
        $fileName = str_replace(
            array('\\', '/./'),
            array('/', '/'),
            $fileName
        );
        $path = explode('/', $fileName);
        $cleanFile = '';
        for ($i = 0; $i + 1 < count($path); $i++) {
            if ($path[$i + 1] === '..') {
                array_splice($path, $i, 2);
            } else if ($path[$i + 1] === '.') {
                array_splice($path, $i + 1, 1);
            }
        }
        return implode('/', $path);
    }
    
    protected function getContent($key) {
        return $this->templateContents[$key][0];
    }
    
    protected function parsePath($fileName) {
        $pathObj = pathinfo($fileName);
        if (isset($pathObj['extension'])) {
            $pathObj['extension'] = '.' . strtolower($pathObj['extension']);
        } else {
            $pathObj['extension'] = '';
        }
        if ($pathObj['dirname']) {
            $pathObj['dirname'] .= DIRECTORY_SEPARATOR;
        }
        $pathObj['wrap'] = '';
        return $pathObj;
    }
    
    // ==========================================
    // Apply template
    // ==========================================
    public function getTemplateRoot()
    { 
        return array(array(), $this->templateTree);
    }
    public function getTemplateDefinitions($keys, $templateBranch)
    {
        return Data2Html_Value::getItem($templateBranch[1], $keys, array());
    }
    public function getTemplateBranch($keys, $templateBranch)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        $finalKeys = array_merge($templateBranch[0], $keys);
        $tree = Data2Html_Value::getItem($templateBranch[1], $keys);
        if (!$tree) {
            throw new Data2Html_Exception(
                "{$this->culprit}: Key \"" .
                    implode('=>', $finalKeys) .
                    "\" does not exist.",
                $templateBranch[1]
            ); 
        }
        return array($finalKeys, $tree);       
    }
    public function getTemplateItem($keys, $templateBranch, $default = null)
    {
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        $item = Data2Html_Value::getItem($templateBranch[1], $keys);
        if ($item) {
            return $item;
        } else {
            return $default;
        } 
    }
    
    // TODO: Remove comments after solve parse a empty form filter!!!!!
    // TODO: Remove this function
    public function emptyRender()
    {
        return array('d2hToken_content' => true);
    }
    
    public function renderTemplateItem($itemKey, $templateBranch, $replaces)
    {
        $templateLeaf = $this->getTemplateBranch(
            array('templates', $itemKey),
            $templateBranch
        );
        return $this->renderMethods($templateLeaf, $replaces);
    }
    
    public function renderTemplate($templateBranch, $replaces)
    {
        $templateLeaf = $this->getTemplateBranch('template', $templateBranch);
        return $this->renderMethods($templateLeaf, $replaces);
    }
    
    protected function renderMethods($templateLeaf, $replaces, $all = true)
    {
        if (array_key_exists('html', $templateLeaf[1])) {
            $html = $this->getContent($templateLeaf[1]['html']);
        } else {
            $html = '';
        }
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
        $resul = array();
        if ($html) {
            $resul['html'] = $this->renderHtml($html, $finalReplaces, false);
        }
        if (array_key_exists('js', $templateLeaf[1])) {
            $js = $this->renderJs(
                $this->getContent($templateLeaf[1]['js']),
                $finalReplaces,
                false
            ) . $js;
        }
        if ($js) {
            $resul['js'] = $js;
        }
        $resul['d2hToken_content'] = true;
        return $resul;
        //==================================
        
        $result = array();
        foreach ($templateLeaf[1] as $k => $v) {
            switch ($k) {
                case 'd2hToken_template':
                    break;
                case 'html':
                    $result[$k] =
                        $this->renderHtml($this->getContent($v), $replaces, $all);
                    break;
                case 'js':
                    $result[$k] =
                        $this->renderJs($this->getContent($v), $replaces, $all);
                    break;
                default:
                    throw new Exception(
                        "{$this->culprit}: Method {$k} on key \"" . 
                        implode('=>', $templateLeaf[0]) .
                        "\" is not supported."
                    );
            }
        }
        $result['d2hToken_content'] = true;
        return $result;
    }
    
    private function renderHtml($html, $replaces, $all = true)
    {
        $html = $this->replaceContent( // <xx attribute="$${template_item}" ...
            '/\w[\w-]*\s*=\s*\"\$\$\{(\w+)(\|*\w*)\}\"/',
            $replaces,
            function($matchItem, $value) {
                if ($value) {
                    $posEq = strpos($matchItem, '=');
                    return 
                        substr($matchItem, 0, $posEq) . '="' . 
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
            $html,
            $all
        );
        $html = $this->replaceContent( // others ...
            '/\$\$\{([\w.:]+)\}/', $replaces,
            function($matchItem, $value) {
                return $value;
            },
            $html,
            $all
        );
        $html = preg_replace("/\r\n\s*\r\n/", "\r\n", $html); // Windows CrLf
        $html = preg_replace("/\n\s*\n/", "\n", $html); // linux Lf
        $html = preg_replace("/\r\s*\r/", "\r", $html); // iOs Cr
        return $html;
    }

    private function renderJs($js, $replaces, $all = true)
    {
        $js = $this->replaceContent( // start string '$${template_item}...
            '/["\']\$\$\{([\w.:]+)\}/', $replaces,
            function($matchItem, $value) {
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
            $js,
            $all
        );
        $js = $this->replaceContent( // others ...
            '/\$\$\{([\w.:]+)\}/', $replaces,
            function($matchItem, $value) {
                if (is_array($value) && count($value) === 0) {
                    // array as js object
                    return '{}';
                } else {
                    return Data2Html_Value::toJson($value);
                }
            },
            $js,
            $all
        );
        $js = preg_replace("/\r\n\s*\r\n/", "\r\n", $js); // Windows CrLf
        $js = preg_replace("/\n\s*\n/", "\n", $js); // linux Lf
        $js = preg_replace("/\r\s*\r/", "\r", $js); // iOs Cr
        return $js;
    }
    
    private function replaceContent($pattern, $replaces, $encodeFn, $content, $all)
    {
        $matches = null;
        preg_match_all($pattern, $content, $matches);
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $k = $matches[1][$i];
            if ($all || array_key_exists($k, $replaces)) {
                $encodedVal = $encodeFn($matches[0][$i], $replaces[$k]);
                // TODO: Remove comments after solve parse a empty form filter!!!!!
                // if (is_array($encodedVal)) {
                    // throw new Exception( 
                        // "{$this->culprit}: Replaces of \"{$matches[0][$i]}\" is array after encoded."
                    // );
                // }
                $match = $matches[0][$i];
                if (is_array($encodedVal)) {
                    throw new Data2Html_Exception(
                        "{$this->culprit} replaceContent(): Value of match \"{$match}\" is array, must be a string.",
                        $encodedVal
                    );
                }
                $content = str_replace($match, $encodedVal, $content);
            }
        }
        return $content;
    }
}
