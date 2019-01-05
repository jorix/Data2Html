<?php
namespace Data2Html\Render;

use Data2Html\DebugException;
use Data2Html\Data\InfoFile;
use Data2Html\Config;

class FileContents
{
    use \Data2Html\DebugStatic;
    
    // 0 => file-info, 1 => file content, 2 => resolved content, "xx" => translated to "xx"
    protected static $templateContents = [];
    
    // Configured template folders
    protected static $folders = null;

    public static function __debugStaticInfo()
    {
        $response = [];
        foreach(self::$templateContents as $k => $v) {
            $response[$k] = $v[0][0];
        }
        return $response;
    }
    
    public static function load($templateName)
    {
        if (!self::$folders) {
            self::$folders = array_reverse(
                (array)Config::getForlder('templateFolder')
            );
        }
        return self::readTemplateFile($templateName . '.php');
    }
    
    public static function clear()
    {
        self::$templateContents = [];
        self::$folders = null; 
    }

    public static function getContent($filePath, $lang = null) {
        return self::getContentFile($filePath, $lang);
    }
   
    protected static function readTemplateFile($fileName)
    {
        // Clean and put in cache the file name
        $filePath = self::loadContent($fileName);
        
        // Put in cache the resolve tree
        if (!array_key_exists(2, self::$templateContents[$filePath])) {
            // Load content if is necessary
            $tree = self::getContentFile($filePath);
            if (is_string($tree) && substr($tree, 0, 1) === '@') {
                $paths = array_map('trim', explode(',', substr($v, 1)));
                // short-cut to file: '@path_to_file_1, path_to_file_2, ...'
                $tree = ['file' => $paths];
            }
            if (is_array($tree)) {
                self::$templateContents[$filePath][2] = 
                    self::loadTemplateTree(
                        InfoFile::toCleanFolderPath(dirname($fileName)),
                        $tree
                    );
            } elseif (is_callable($tree)) {
                self::$templateContents[$filePath][2] = $tree;
            } else {
                throw new DebugException(
                    "Tree in file \"{$filePath}\") must be a array or a function!",
                    $tree
                );
            }
        }
        return self::$templateContents[$filePath][2];
    }
    
    protected static function loadTemplateTree($folder, $tree)
    {
        if (is_callable($tree)) {
            return $tree;
        }
        if (array_key_exists('folder', $tree)) {
            $folder = InfoFile::toCleanFolderPath($folder . $tree['folder']);
        }
        $response = [];
        foreach($tree as $k => $v) {
            if (is_string($v) && substr($v, 0, 2) === '@@') {
                // short-cut to include folder files: 'keyword' => '@path_to_folder_1, path_to_folder_2, ...'
                $paths = array_map('trim', explode(',', substr($v, 2)));
                $response[$k] = self::loadTemplateTree(
                    $folder, 
                    ['includeFolder' => $paths]
                );
            } elseif (is_string($v) && substr($v, 0, 1) === '@') {
                $paths = array_map('trim', explode(',', substr($v, 1)));
                if (is_integer($k)) {
                    // short-cut to file without keyword: '@path_to_file_1, path_to_file_2, ...'
                    $response += self::loadTemplateTree(
                        $folder,
                        ['file' => $paths]
                    );
                } else {
                    // short-cut to file into keyword: 'keyword' => '@path_to_file_1, path_to_file_2, ...'
                    $response[$k] = self::loadTemplateTree(
                        $folder, 
                        ['file' => $paths]
                    );
                }
            } else {
                switch ($k) {
                    // Declarative words
                    case 'folder':
                        break;
                    case 'require':
                    case 'include':
                        $response[$k] = $v;
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
                    // Add to the tree
                    case 'includeFolder': // short-cut @@
                        $response += self::readFolderTemplates($folder, $v);
                        break;
                    case 'file': // short-cut @
                        foreach((array)$v as $vv) {
                            $vvv = self::readTemplateFile($folder . $vv);
                            if (is_callable($vvv)) {
                                $response = $vvv;
                                break;
                            }
                            if (!is_array($vvv)) {
                                throw new DebugException(
                                    "Content on \"{$k}\" must be a array or a function!",
                                    $tree
                                );
                            }
                            $response += $vvv;
                        }
                        break;
                    case 'html':
                    case 'js':
                        $response[$k] = $v;
                        break;
                    default:
                        if (is_callable($v)) {
                            $response[$k] = $v;
                        } elseif (is_array($v)) {
                            $response[$k] = self::loadTemplateTree($folder, $v);
                        } else {
                            throw new DebugException(
                                "Tree of \"{$k}\" must be a array or a function!",
                                $tree
                            );
                        }
                        break;
                }
            }
        }
        return $response;
    }


    protected static function readFolderTemplates($baseFolder, $folders)
    {
        $templates = [];
        foreach ((array)$folders as $v) {
            $templates += self::getContentFile(self::loadFolderHtml($baseFolder . $v));
        }
        return ['templates' => $templates];
    }

    // =======================================================================
    protected static function loadFolderHtml($folderPath)
    {
        $folderPath = InfoFile::toCleanFilePath($folderPath);
        if (!array_key_exists($folderPath, self::$templateContents)) {
            $htmlFiles = [];
            foreach(self::$folders as $v) {
                $fullFolder = InfoFile::toCleanFilePath($v . $folderPath);
                if (is_dir($fullFolder)) {
                    foreach (new \DirectoryIterator($fullFolder) as $fInfo) {
                        if ($fInfo->isFile()) {
                            $pathObj = InfoFile::parseWrappedPath(
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
            self::$templateContents[$folderPath] = [
                0 => InfoFile::parseWrappedPath($folderPath),
                1 => $htmlFiles
            ];
        }
        return $folderPath;
    }
    
    protected static function loadTemplateFile($filePath)
    {
        $filePath = self::loadContent($filePath);
        $fileType = self::getFileType($filePath);
        $response = [];
        switch ($fileType) {
            case '.html':
                $response['@html'] = $filePath;
                $jsFilePath = preg_replace(
                    ['/\.html$/i',  '/\.html\.php$/i'],
                    '.js',
                    $filePath
                );
                $jsFilePath = self::loadContent($jsFilePath, false);
                if ($jsFilePath) {
                    $response['@js'] = $jsFilePath;
                }
                break;
            case '.js':
                $response['@js'] = $filePath;
                break;
            case '.php':
                $response = $filePath;
                break;
            default:
                throw new \Exception(
                    "Extension \"{$fileType}\" for template \"{$filePath}\" is not supported."
                );
        }
        return $response;
    }

    protected static function loadContent($filePath, $required = true)
    {
        $filePath = InfoFile::toCleanFilePath($filePath);
        if (!array_key_exists($filePath, self::$templateContents)) {
            $loaded = false;
            foreach(self::$folders as $k => $v) {
                $pathObj = InfoFile::parseWrappedPath(
                    InfoFile::toCleanFilePath($v . $filePath)
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
                        0 => InfoFile::parseWrappedPath($fullFile)
                    ];
                    self::$templateContents[$filePath][0]['conf-index'] = 
                        count(self::$folders) - (1 + $k);
                    $loaded = true;
                    break;
                }
            }
            if (!$loaded) {
                if ($required) {
                    throw new DebugException(
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
    
    protected static function getFileType($filePath) {
        if (!array_key_exists($filePath, self::$templateContents)) {
            throw new DebugException(
                "File \"{$filePath}\" is yet not loaded.",
                $filePath
            );
        }
        return self::$templateContents[$filePath][0]['extension'];
    }

    protected static function getContentFile($filePath, $lang = null) {
        if (!array_key_exists($filePath, self::$templateContents)) {
            throw new DebugException(
               "File \"{$filePath}\" is not yet loaded.",
                $filePath
            );
        }
        
        $contentItem = self::$templateContents[$filePath];
        if (!array_key_exists(1, self::$templateContents[$filePath])) {
            $pathObj = self::$templateContents[$filePath][0];
            
            $fileName = $pathObj[0];
            $fileNameDebug = "config=>templateFolder[{$pathObj['conf-index']}]/\"{$filePath}\"";
            switch ($pathObj['extension']) {
            case '.html':
                $content = InfoFile::readWrappedFile($fileName);
                if (Config::debug()) {
                    $content = 
                        "\n<!-- debug-name=\"\$\${debug-name}\" level=\"\$\${_level}\" id=\"\$\${id}\" - {$fileNameDebug} #\$\${_renderCount}# [[ -->\n" .
                        $content .
                        "\n<!-- ]] #\$\${_renderCount}# -->\n";
                }
                break;
            case '.js':
                $content = InfoFile::readWrappedFile($fileName);
                if (Config::debug()) {
                    $content = 
                        "\n// debug-name=\"\$\${debug-name}\" level=\"\$\${_level}\" id=\"\$\${id}\" - {$fileNameDebug} #\$\${_renderCount}# [[\n" .
                        $content .
                        "\n// ]] #\$\${_renderCount}#\n";
                }
                break;
            case '.json':
                $content = InfoFile::readJson($fileName);
                break;
            case '.php':
                $content = InfoFile::readPhp($fileName);
                break;
            default:
                throw new \Exception("Extension \"{$pathObj['extension']}\" on definitions name \"{$fileName}\" is not supported.");
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
