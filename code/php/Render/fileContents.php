<?php
namespace Data2Html\Render;

use Data2Html\DebugException;

class FileContents
{
    use \Data2Html\Debug;
    
    // 0 => file-info, 1 => file content, 2 => resolved content, "xx" => translated to "xx"
    protected $templateContents = [];
    
    // Configured template folders
    protected $folders;
    
    protected $obj;
    
    public static function load($templateName)
    {
        if ($obj) {
            self::$folders = array_reverse(
                (array)Config::getForlder('templateFolder')
            );
        }
        return self::readTemplateTreeFile($templateName . '.php');
    }

    public static function get($filePath, $lang = null) {
        if (count(self::$folders) === 0) {
            self::$folders = array_reverse(
                (array)Config::getForlder('templateFolder')
            );
        }
        return self::getContent(
            self::loadContent($filePath), $lang
        );
    }

    public static function getContent($filePath, $lang = null) {
        return self::getContentFile($filePath, $lang);
    }
    
    public function __construct($folders)
    {
        $this->folders = array_reverse(
            (array)Config::getForlder('templateFolder')
        );
    }
    
    public function __debugInfo()
        $subject = [];
        foreach($this->templateContents as $k => $v) {
            $subject[$k] = $v[0][0];
        }
        reuturn $subject;
    }
    
    protected function readTemplateTreeFile($fileName)
    {
        $filePath = Data2Html_Utils::toCleanFilePath($fileName);
        
        // Load unresolved tree if is necessary
        $tree = self::readArrayFile($filePath);
        
        // Resolved tree
        if (!array_key_exists(2, $this->templateContents[$filePath])) {
            $resolvedTree = self::loadTemplateTree(
                Data2Html_Utils::toCleanFolderPath(dirname($fileName)),
                $tree
            );
            $this->templateContents[$filePath][2] = $resolvedTree;
        }
        return $this->templateContents[$filePath][2];
    }
    
    protected function loadTemplateTree($folder, $tree)
    {
        if (array_key_exists('folder', $tree)) {
            $folder = Data2Html_Utils::toCleanFolderPath($folder . $tree['folder']);
        }
        $response = [];
        foreach($tree as $k => $v) {
            if (substr($v, 0, 1) = '@') {
                @paths = array_map('trim', explode(',', substr($v, 1)));
                if (is_integer($k))) {
                    // short-cut to include file: '@path_to_file_1, path_to_file_2, ...'
                    $k = 'include';
                    $v = @paths;
                } else {
                    // short-cut to include file on folder: 'keyword' => '@path_to_folder_1, path_to_folder_2, ...'
                    $response[$k] = self::loadTemplateTree(
                        $folder, 
                        [$k => ['includeFolder' => $paths]]
                    );
                    $k = 'folder'; // to by-pass next switch
                }
            }
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
                case 'includeFolder':
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
                        throw new ExceptionDebug(
                            "Tree of \"{$k}\" must be a array or a function!",
                            $tree
                        );
                    }
                    break;
            }
        }
        return $response;
    }
    
    protected function readArrayFile($filePath)
    {
        $response = self::getContentFile(self::loadContent($filePath));
        if (!is_array($response)) {
            throw new Data2Html_Exception(
                get_called_class() .
                "::readArrayFile(\"{$filePath}\") Tree must be a array!",
                $response
            );
        }
        return $response;
    }

    protected function readFolderTemplates($baseFolder, $folders)
    {
        $templates = [];
        foreach ((array)$folders as $v) {
            $templates += self::getContentFile(self::loadFolderHtml($baseFolder . $v));
        }
        return ['templates' => $templates];
    }
    
    
    // =======================================================================
    protected function loadFolderHtml($folderPath)
    {
        $folderPath = Data2Html_Utils::toCleanFilePath($folderPath);
        if (!array_key_exists($folderPath, $this->templateContents)) {
            $htmlFiles = [];
            foreach(self::$folders as $v) {
                $fullFolder = Data2Html_Utils::toCleanFilePath($v . $folderPath);
                if (is_dir($fullFolder)) {
                    foreach (new DirectoryIterator($fullFolder) as $fInfo) {
                        if ($fInfo->isFile()) {
                            $pathObj = Data2Html_Utils::parseWrappedPath(
                                $fInfo->getFilename()
                            );
                            $fullFile = $folderPath .
                                $pathObj['filename'] .
                                $pathObj['extension'];
                            switch ($pathObj['extension']) {
                                case '.html':
                                    $htmlFiles[$pathObj['filename']] = 
                                        self::loadTemplateFile($fullFile);
                                    break;
                                case '.php':
                                    $htmlFiles[$pathObj['filename']] = 
                                        self::loadContent($fullFile);
                                    break;
                            }
                        }
                    }
                }
            }
            $this->templateContents[$folderPath] = [
                0 => Data2Html_Utils::parseWrappedPath($folderPath),
                1 => $htmlFiles
            ];
        }
        return $folderPath;
    }
    
    protected function loadTemplateFile($filePath)
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

    protected function loadContent($filePath, $required = true)
    {
        $filePath = Data2Html_Utils::toCleanFilePath($filePath);
        if (!array_key_exists($filePath, $this->templateContents)) {
            $loaded = false;
            foreach(self::$folders as $k => $v) {
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
                    $this->templateContents[$filePath] = [
                        0 => Data2Html_Utils::parseWrappedPath($fullFile)
                    ];
                    $this->templateContents[$filePath][0]['conf-index'] = 
                        count(self::$folders) - (1 + $k);
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
    
    protected function getFileType($filePath) {
        if (!array_key_exists($filePath, $this->templateContents)) {
            throw new Data2Html_Exception(
                "File \"{$filePath}\" is yet not loaded.",
                $filePath
            );
        }
        return $this->templateContents[$filePath][0]['extension'];
    }

    protected function getContentFile($filePath, $lang = null) {
        if (!array_key_exists($filePath, $this->templateContents)) {
            throw new Data2Html_Exception(
               "File \"{$filePath}\" is not yet loaded.",
                $filePath
            );
        }
        
        $contentItem = $this->templateContents[$filePath];
        if (!array_key_exists(1, $this->templateContents[$filePath])) {
            $pathObj = $this->templateContents[$filePath][0];
            
            $fileName = $pathObj[0];
            $fileNameDebug = "config=>templateFolder[{$pathObj['conf-index']}]/\"{$filePath}\"";
            switch ($pathObj['extension']) {
            case '.html':
                $content = Data2Html_Utils::readWrappedFile($fileName, get_called_class());
                if (Config::debug()) {
                    $content = 
                        "\n<!-- debug-name=\"\$\${debug-name}\" id=\"\$\${id}\" - {$fileNameDebug} #\$\${_renderCount}# [[ -->\n" .
                        $content .
                        "\n<!-- ]] #\$\${_renderCount}# -->\n";
                }
                break;
            case '.js':
                $content = Data2Html_Utils::readWrappedFile($fileName, get_called_class());
                if (Config::debug()) {
                    $content = 
                        "\n// debug-name=\"\$\${debug-name}\" id=\"\$\${id}\" - {$fileNameDebug} #\$\${_renderCount}# [[\n" .
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
            $this->templateContents[$filePath][1] = $content;
        }
        if ($lang && !array_key_exists($lang, $this->templateContents[$filePath])) {
            // TODO
            $this->templateContents[$filePath][$lang] = $this->templateContents[$filePath][1];
        } 
        
        if ($lang) {
            return $this->templateContents[$filePath][$lang];
        } else {
            return $this->templateContents[$filePath][1];
        }
    }
}    
