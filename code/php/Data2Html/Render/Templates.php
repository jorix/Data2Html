<?php

class Data2Html_Render_Templates
{
    protected $templateName;
    protected $templates;
    protected $templateContents;
    
    public function __construct($templateName)
    {
        $this->templateName = $templateName;
        $pathObj = $this->parsePath($templateName);
        
        $this->templateContents = array();
        $this->templates = $this->loadTemplate(
            $pathObj['dirname'],
            $pathObj['basename']
        );
        print_r($this->templates);
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
                    break;
                case 'templates':
                    $items = array();
                    foreach($tree['templates'] as $kk => $vv) {
                        $items[$kk] = $this->loadTemplate($folder, $vv);
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
        $cleanFileName = str_replace(
            array('\\', '/./'),
            array('/', '/'),
            $fileName
        );
        if (!array_key_exists($cleanFileName, $this->templateContents)) {
            $this->templateContents[$cleanFileName] =
                $this->loadContent($fileName);
        }
        return array(
            $cleanFileName,
            $this->templateContents[$cleanFileName][1] // the path info
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
    
    public function renderTemplateItem($baseKeys, $templateTree, $keys, $itemKey)
    {
        list($keysText, $templateArray) = $this->getTemplateTree(
            implode('=>', $baseKeys), $templateTree, $keys
        );
        list($keysText, $templateArray) = $this->getTemplateTree(
            $keysText, $templateTree, array('templates')
        );
        list($keysText, $templateArray) = $this->getTemplateTree(
            $keysText, $templateTree, $itemKey
        );
        return $this->renderMethods($keysText, $replaces, $templateTree[1]);
    }
    
    public function renderTemplate($templateTree, $keys, $replaces)
    {
        $templateTree = $this->getTemplateTree(
            implode('=>', $templateTree[0]), $templateTree[1], $keys
        );
        list($keysText, $templateArray) = $this->getTemplateTree(
            $keysText, $templateTree, array('template')
        );
        return $this->renderMethods($keysText, $replaces, $templateTree[1]);
    }
    
    protected function getTemplateTree($templateTree, $keys)
    {
        $tree = Data2Html_Array::get($templateTree[1], $keys);
        if (!$tree) {
            throw new Exception(
                "Template key \"" . implode('=>', $keys) .
                "\" of template \"{$this->templateName}\" does not exist."
            ); 
        }
        $finalKeys = array_merge($templateTree[0], $keys);
        return array($finalKeys, $tree);       
    }
    
    protected function renderMethods($keysText, $replaces, $templateMethods)
    {
        $result = array();
        foreach ($templateMethods as $k => $v) {
            switch ($k) {
                case 'html':
                    $result[$k] = renderHtml($replaces, $v);
                    break;
                case 'js':
                    $result[$k] = renderJs($replaces, $v);
                    break;
                default:
                    throw new Exception(
                        "Template method {$k} on key \"{$keysText}\" of template \"{$this->templateName}\" is not supported."
                    );
            }
        }
        return $result;
    }
    
    private function renderHtml($replaces, $templateKey)
    {
        $html = $this->getContent($templateKey);
        $html = $this->replaceContent( // <xx attribute="$${template_item}" ...
            '/\=\"\$\$\{([\w.:]+)\}\"/',
            $replaces,
            function($value) {
                return '="' . htmlspecialchars(
                    $value,
                    ENT_COMPAT | ENT_SUBSTITUTE,
                    'UTF-8'
                ) . '"';
            },
            $html
        );
        $html = $this->replaceContent( // others ...
            '/\$\$\{([\w.:]+)\}/', $replaces,
            function($value) {
                return $value;
            },
            $html
        );
        return $html;
    }

    private function renderJs($replaces, $templateKey)
    {
        $html = $this->getContent($templateKey);
        $html = $this->replaceContent( // start string '$${template_item}...
            '/["\']?\$\$\{([\w.:]+)\}/', $replaces,
            function($value) {
                return $value;
            },
            $html
        );
        $html = $this->replaceContent( // others ...
            '/\$\$\{([\w.:]+)\}/', $replaces,
            Data2Html_Value::toJson,
            $html
        );
        return $html;
    }
    private function replaceContent($pattern, $replaces, $encodeFn, $content)
    {
        $replDx = new Data2Html_Collection($replaces);
        $matches = null;
        preg_match_all('/\$\$\{([\w.:]+)\}/', $content, $matches);
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $content = str_replace(
                $matches[0][$i],
                $encodeFn($replDx->getString($matches[1][$i], '')),
                $content
            );
        }
        return $content;
    }
}
