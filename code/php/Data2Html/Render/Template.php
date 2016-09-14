<?php

class Data2Html_Render_Template
{
    protected $debug;
    protected $templateName;
    protected $templateTree;
    protected $templateContents;
    
    public function __construct($templateName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->templateName = $templateName;
        $pathObj = $this->parsePath($templateName);
        
        $this->templateContents = array();
        $this->templateTree = $this->loadTemplate(
            $pathObj['dirname'],
            $pathObj['basename']
        );
    }
    
    public function dump()
    {
        if ($this->debug) {
            echo "<div style=\"margin-left:.5em\">
                <h3>Template tree of: \"{$this->templateName}\":</h3>
                <pre>" .
                Data2Html_Value::toJson($this->templateTree, true) .
                '</pre></div>';
        } else {
            echo '<h3 style="margin-left:.5em; color:red; test-align:center">
                Debugging mode must be enabled to can use dump() method!</h3>';
        }
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
             throw new Exception("Tree must be a array!");
        }
        if (array_key_exists('folder', $tree)) {
            $folder .=  $tree['folder'];
            if (strpos('/\\', substr($folder, -1, 1)) === false) {
                $folder .= DIRECTORY_SEPARATOR;
            }
        }
        $response = array();
        foreach($tree as $k => $v) {
            switch ($k) {
                case 'folder':
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
                case '_description':
                    break; // Ignore all developer descriptions
                default:
                    $response[$k] = $this->loadTemplateTree($folder, $v);
                    break;
            }
        }
        return $response;
    }
    
    protected function loadTemplate($folder, $templateFileName)
    {
        $fullFileName = $folder . $templateFileName;
        list($contentKey, $pathObj) = $this->addContent($fullFileName);
        switch ($pathObj['extension']) {
            case '.html':
                $response = array(
                    'html' => $contentKey
                );
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
                        $this->addContent($jsFileName);
                    $response['js'] = $jsContentKey;
                }
                return $response;
            case '.js':
                return array(
                    'js' => $contentKey
                );
            case '.json':
                $tree = json_decode($this->getContent($contentKey), true);
                if ($tree === null) {
                    throw new Exception("Error parsing the json file: \"{$fullFileName}\"");
                }
                return $this->loadTemplateTree($folder, $tree);
            default:
                throw new Exception("Extension \"{$pathObj['extension']}\" on template name \"{$fullFileName}\" is not supported.");
        }
    }

    protected function addContent($fileName) {
        $cleanFileName = $this->cleanFileName($fileName);
        if (!array_key_exists($cleanFileName, $this->templateContents)) {
            $this->templateContents[$cleanFileName] =
                $this->loadContent($fileName);
        }
        return array(
            $cleanFileName,
            $this->templateContents[$cleanFileName][1] // the path info
        );
    }

    protected function cleanFileName($fileName) {
        return str_replace(
            array('\\', '/./'),
            array('/', '/'),
            $fileName
        );
    }
    protected function getContent($key) {
        return $this->templateContents[$key][0];
    }
    
    protected function loadContent($fileName) {
        if (!file_exists($fileName)) {
            throw new Exception(
                "The \"{$fileName}\" file does not exist."
            );
        }        
        $text = file_get_contents($fileName);//, FILE_USE_INCLUDE_PATH);
        $pathObj = $this->parsePath($fileName);
        if ($pathObj['extension'] === '.php' ) {
            $wrap = $pathObj['extension'];
            $pathObj = $this->parsePath(
                $pathObj['dirname'] . $pathObj['filename']
            );
            $pathObj['wrap'] = $wrap;
            $phpEnd = strpos($text, "?>\n");
            if ($phpEnd === false) {
                $phpEnd = strpos($text, "?>\r");
            }
            if ($phpEnd !== false) {
                $text = substr($text, $phpEnd + 3);
            }
        }
        if ($this->debug) {
            $cleanFileName = $this->cleanFileName($fileName);
            switch ($pathObj['extension']) {
                case '.html':
                    $text = 
                        "\n<!-- START \"{$cleanFileName}\" [[ -->" .
                        "\n<!-- ======================================== -->\n" .
                        $text .
                        "\n<!-- END  \"{$cleanFileName}\" ]] -->\n";
                    break;
                case '.js':
                    $text = 
                        "\n// START \"{$cleanFileName}\" [[" .
                        "\n// ========================================\n" .
                        $text .
                        "\n// END  \"{$cleanFileName}\" ]]\n";
                    break;
            }
        }
        return array($text, $pathObj);
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
    public function getTemplateBranch($keys, $templateBranch = null)
    {
        if (!$templateBranch) {
            $templateBranch = array(array(), $this->templateTree);
        }
        if (!is_array($keys)) {
            $keys = array($keys);
        }
        $finalKeys = array_merge($templateBranch[0], $keys);
        $tree = Data2Html_Value::getItem($templateBranch[1], $keys);
        if (!$tree) {
            throw new Data2Html_Exception(
                "Template key \"" . 
                implode('=>', $finalKeys) .
                "\" of template \"{$this->templateName}\" does not exist.",
                $templateBranch[1]
            ); 
        }
        return array($finalKeys, $tree);       
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
                        "Template method {$k} on key \"" . implode('=>', $templateLeaf[0]) .
                        "\" of template \"{$this->templateName}\" is not supported."
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
                return Data2Html_Value::toJson($value);
            },
            $js,
            $all
        );
        return $js;
    }
    
    private function replaceContent($pattern, $replaces, $encodeFn, $content, $all)
    {
        $matches = null;
        preg_match_all($pattern, $content, $matches);
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $k = $matches[1][$i];
            if ($all || array_key_exists($k, $replaces)) {
                $content = str_replace(
                    $matches[0][$i],
                    $encodeFn($matches[0][$i], $replaces[$k]),
                    $content
                );
            }
        }
        return $content;
    }
}
