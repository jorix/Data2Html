<?php

class Data2Html_Render_FileContents
{
    // 0 => file-info, 1 => file content, 2 => resolved content, "xx" => translated to "xx"
    private static $templateContents = [];
    
    // Configured template folders
    private static $folders;
    
    public static function dump($subject = null)
    {
        if (!$subject) {
            $subject = [];
            foreach(self::$templateContents as $k => $v) {
                $subject[$k] = $v[0][0];
            }
        }
        Data2Html_Utils::dump(get_called_class(), $subject);
    }
    
    public static function load($templateName)
    {
        if (count(self::$folders) === 0) {
            self::$folders = array_reverse(
                (array)Data2Html_Config::getForlder('templateFolder')
            );
        }
        return self::readTemplateTreeFile($templateName . '.php');
    }
    
    private static function readTemplateTreeFile($fileName)
    {
        $filePath = Data2Html_Utils::toCleanFilePath($fileName);
        
        // Load unresolved tree if is necessary
        $tree = self::readArrayFile($filePath);
        
        // Resolved tree
        if (!array_key_exists(2, self::$templateContents[$filePath])) {
            $resolvedTree = self::loadTemplateTree(
                Data2Html_Utils::toCleanFolderPath(dirname($fileName)),
                $tree
            );
            self::$templateContents[$filePath][2] = $resolvedTree;
        }
        return self::$templateContents[$filePath][2];
    }
    
    private static function loadTemplateTree($folder, $tree)
    {
        if (array_key_exists('folder', $tree)) {
            $folder = Data2Html_Utils::toCleanFolderPath($folder . $tree['folder']);
        }
        $response = [];
        foreach($tree as $k => $v) {
            switch ($k) {
                // Declarative words
                case 'folder':
                    break;
                case 'startItems':
                case 'endItems':
                    $response[$k] = self::loadContent($folder . $v);
                    break;
                case 'template':
                    $response[$k] = self::loadTemplateFile($folder . $v);
                    break;
                case 'templates':
                    $items = [];
                    foreach($v as $kk => $vv) {
                        $items[$kk] = self::loadTemplateFile($folder . $vv);
                    }
                    $response[$k] = $items;
                    break;
                case 'templatesFolder':
                    $response += self::readFolderTemplates($folder, $v);
                    break;
                // Add to the tree
                case 'include':
                case 'includes':
                    foreach((array)$v as $vv) {
                         $response += self::readTemplateTreeFile($folder . $vv);
                    }
                    break;
                default:
                    if (is_callable($v)) {
                        $response[$k] = $v;
                    } elseif (is_array($v)) {
                        $response[$k] = self::loadTemplateTree($folder, $v);
                    } else {
                        throw new Data2Html_Exception(
                            "Tree of \"{$k}\"must be a array or a function!",
                            $tree
                        );
                    }
                    break;
            }
        }
        return $response;
    }
    
    private static function readArrayFile($filePath)
    {
        $response = self::getContent(self::loadContent($filePath));
        if (!is_array($response)) {
            throw new Data2Html_Exception(
                get_called_class() .
                "::readArrayFile(\"{$filePath}\") Tree must be a array!",
                $response
            );
        }
        return $response;
    }

    private static function readFolderTemplates($baseFolder, $folders)
    {
        $templates = [];
        foreach ((array)$folders as $v) {
            $templates += self::getContent(self::loadFolderHtml($baseFolder . $v));
        }
        return ['templates' => $templates];
    }
    
    
    // =======================================================================
    private static function loadFolderHtml($folderPath)
    {
        $folderPath = Data2Html_Utils::toCleanFilePath($folderPath);
        if (!array_key_exists($folderPath, self::$templateContents)) {
            $htmlFiles = [];
            foreach(self::$folders as $v) {
                $fullFolder = Data2Html_Utils::toCleanFilePath($v . $folderPath);
                if (is_dir($fullFolder)) {
                    foreach (new DirectoryIterator($fullFolder) as $fInfo) {
                        if ($fInfo->isFile()) {
                            $pathObj = Data2Html_Utils::parseWrappedPath(
                                $fInfo->getFilename()
                            );
                            if ($pathObj['extension'] === '.html') {
                                $htmlFiles[$pathObj['filename']] = self::loadTemplateFile(
                                    $folderPath . 
                                    $pathObj['filename'] .$pathObj['extension']
                                );
                            }
                        }
                    }
                }
            }
            self::$templateContents[$folderPath] = [
                0 => Data2Html_Utils::parseWrappedPath($folderPath),
                1 => $htmlFiles
            ];
        }
        return $folderPath;
    }
    
    private static function loadTemplateFile($filePath)
    {
        $filePath = self::loadContent($filePath);
        $fileType = self::getFileType($filePath);
        $response = [];
        switch ($fileType) {
            case '.html':
                $response['html'] = $filePath;
                $jsFilePath = preg_replace(
                    ['/\.html$/i',  '/\.html\.php$/i'],
                    '.js',
                    $filePath
                );
                $jsFilePath = self::loadContent($jsFilePath, false);
                if ($jsFilePath) {
                    $response['js'] = $jsFilePath;
                }
                break;
            case '.js':
                $response['js'] = $filePath;
                break;
            default:
                throw new Data2Html_Exception(
                    "Extension \"{$pathObj['extension']}\" for template \"{$filePath}\" and file \"{$fullFileName}\" is not supported.",
                    $pathObj
                );
        }
        return $response;
    }

    private static function loadContent($filePath, $required = true)
    {
        $filePath = Data2Html_Utils::toCleanFilePath($filePath);
        if (!array_key_exists($filePath, self::$templateContents)) {
            $loaded = false;
            foreach(self::$folders as $v) {
                $pathObj = Data2Html_Utils::parseWrappedPath(
                    Data2Html_Utils::toCleanFilePath($v . $filePath)
                );
                $fullFile = $pathObj['dirname'] . $pathObj['filename'] . $pathObj['extension'];
                if (!file_exists($fullFile) &&
                    $pathObj['extension'] &&
                    strpos('.html.js.json', $pathObj['extension']) !== false
                ) {
                    $fullFile .= '.php';
                } 
                if (file_exists($fullFile)) {
                    self::$templateContents[$filePath] = [
                        0 => Data2Html_Utils::parseWrappedPath($fullFile)
                    ];
                    $loaded = true;
                    break;
                }
            }
            if (!$loaded) {
                if ($required) {
                    throw new Data2Html_Exception(
                        "File \"{$filePath}\" does not exist in configured `templateFolder`.",
                        self::$folders
                    );
                } else {
                    return false;
                }
            } 
        }
        return $filePath;
    }
    
    private static function getFileType($filePath) {
        if (!array_key_exists($filePath, self::$templateContents)) {
            throw new Data2Html_Exception(
                "File \"{$filePath}\" is yet not loaded.",
                $filePath
            );
        }
        return self::$templateContents[$filePath][0]['extension'];
    }
    
    public static function get($filePath, $lang = null) {
        if (count(self::$folders) === 0) {
            self::$folders = array_reverse(
                (array)Data2Html_Config::getForlder('templateFolder')
            );
        }
        return self::getContent(
            self::loadContent($filePath), $lang
        );
    }
    
    public static function getContent($filePath, $lang = null) {
        if (!array_key_exists($filePath, self::$templateContents)) {
            throw new Data2Html_Exception(
               "File \"{$filePath}\" is not yet loaded.",
                $filePath
            );
        }
        
        $contentItem = self::$templateContents[$filePath];
        if (!array_key_exists(1, self::$templateContents[$filePath])) {
            $pathObj = self::$templateContents[$filePath][0];
            $fileName = $pathObj[0];
            switch ($pathObj['extension']) {
            case '.html':
                $content = Data2Html_Utils::readWrappedFile($fileName, get_called_class());
                if (Data2Html_Config::debug()) {
                    $content = 
                        "\n<!-- name=\"\$\${name}\" id=\"\$\${id}\" - \"{$filePath}\" #\$\${_renderCount}# [[ -->\n" .
                        $content .
                        "\n<!-- ]] #\$\${_renderCount}# -->\n";
                }
                break;
            case '.js':
                $content = Data2Html_Utils::readWrappedFile($fileName, get_called_class());
                if (Data2Html_Config::debug()) {
                    $content = 
                        "\n// name=\"\$\${name}\" id=\"\$\${id}\" - \"{$filePath}\" #\$\${_renderCount}# [[\n" .
                        $content .
                        "\n// ]] #\$\${_renderCount}#\n";
                }
                break;
            case '.json':
                $content = Data2Html_Utils::readFileJson($fileName, get_called_class());
                break;
            case '.php':
                $content = Data2Html_Utils::readFilePhp($fileName, get_called_class());
                break;
            default:
                throw new Exception("Extension \"{$pathObj['extension']}\" on definitions name \"{$fileName}\" is not supported.");
            }
            self::$templateContents[$filePath][1] = $content;
        }
        if ($lang && !array_key_exists($lang, self::$templateContents[$filePath])) {
            // TODO
            self::$templateContents[$filePath][$lang] = self::$templateContents[$filePath][1];
        } 
        
        if ($lang) {
            return self::$templateContents[$filePath][$lang];
        } else {
            return self::$templateContents[$filePath][1];
        }
    }
}    
