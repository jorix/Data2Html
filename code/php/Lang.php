<?php
namespace Data2Html;

use Data2Html\Config;
use Data2Html\Data\To;
use Data2Html\Data\InfoFile;
use Data2Html\Lang;

class Lang
{
    use \Data2Html\Debug;
    
    private $languages = null;
    private $fromFiles = null;
    private $literals = null;
    
    public function __construct($language, $folders)
    {
        $language = strtolower($language);
        $cfgLangs = Config::get('languages');
        if (array_key_exists($language, $cfgLangs)) {
            $this->languages = explode(',', $cfgLangs[$language]);
        } else {
            $this->languages = [$language];
        }
        
        $this->literals = ['lang' => $language];
        $this->fromFiles = [];
        
        foreach($folders as $k => $v) {
            if (is_integer($k)) {
                $this->load('', Data2Html\Autoload::getCodeFolder() . $v);
            } else {
                $this->load($k, Data2Html\Autoload::getCodeFolder() . $v);
            }
        }
    }

    public function __debugInfo()
        return [
            'languages-priority' => $this->languages,
            'literals' => $this->literals,
            'fromFiles' => $this->fromFiles,
        ];
    }
    
    public function _($key)
    {
        if (is_array($key)) {
            $key = implode('/', $key);
        }
        if (array_key_exists($key, $this->literals)) {
            return $this->literals[$key];
        } else {
            return "??{{$key}}";
        }
    }
    
    public function from($key)
    {
        if (Config::debug()) {
            if (is_array($key)) {
                $key = implode('/', $key);
            }
            if (array_key_exists($key, $this->fromFiles)) {
                return $this->fromFiles[$key];
            } else {
                return "??{{$key}}";
            }
        }
    }
    
    private function load($name, $folders)
    {
        foreach(array_reverse($this->languages) as $v) {
            foreach((array)$folders as $vv) {
                $this->loadOne($v, $name, $vv);
            }
        }
    }
    
    public function getLiterals()
    {
        return $this->literals;
    }
    
    public function responseJs($lang)
    {
        Data2Html\Data\Response::js(self::jsCode($lang));
    }
    
    protected static function jsCode($lang)
    {
        $lang = new Lang($lang, ['/_lang', '/../js']);
        return "
            var __ = (function () {
            var literals = " . 
                To::json($lang->getLiterals(), Config::debug()) . ";
            return function(key) {
                if (Array.isArray(key)) {
                    key = key.join('/');
                }
                if (literals[key]) {
                    return literals[key];
                } else {
                    return '??{' + key + '}';
                }
            };
                
            })();
        ";
    }
    
    private function loadOne($lang, $name, $folder)
    {
        
        $rDir = new RecursiveDirectoryIterator($folder);
        $regex = new RegexIterator(
            new RecursiveIteratorIterator($rDir), 
            '/^.+' . $lang . '\.php$/i',
            RegexIterator::GET_MATCH 
        );
        // The deeper folders are applied last.
        $regexKeys = [];
        foreach($regex as $k => $v) {
            $regexKeys[] = $k;
        }
        $regexKeys = array_reverse($regexKeys);
        
        $cleanForlder = InfoFile::toCleanFolderPath($folder, '/');
        $literals = [];
        $files = [];
        foreach($regexKeys as $k) {
            $file = self::strRemoveStart(
                InfoFile::toCleanFilePath($k, '/'),
                $cleanForlder
            );
            $base = substr(strstr($file, '_lang/', true), 0, -1);
            if ($name) {
                $base = $name . '/' . $base;
            }
            $fullFile = $cleanForlder . $file;
            
            $flatContent = [];
            $flatFiles = [];
            $flatten = function($iterator, $flatKey) 
            use(&$flatten, &$flatContent, &$flatFiles, $fullFile) {
                while ($iterator->valid()) {
                    $newFlatKey = $iterator->key();
                    if ($flatKey) {
                        $newFlatKey = $flatKey . '/' . $newFlatKey;
                    }
                    if ($iterator->hasChildren() ) {
                        $flatten($iterator->getChildren(), $newFlatKey);
                    } elseif ($newFlatKey !== 'lang') {
                        $flatContent[$newFlatKey] = $iterator -> current();
                        $flatFiles[$newFlatKey] = $fullFile;                          
                    }
                    $iterator->next();
                }
            };
            $content = InfoFile::readPhp($k);
            $itr2 = new RecursiveArrayIterator($content);
            iterator_apply($itr2, $flatten, array($itr2, $base));
            $literals = array_replace($literals, $flatContent);
            $files = array_replace($files, $flatFiles);
        }
        $this->literals = array_replace($this->literals, $literals);
        $this->fromFiles = array_replace($this->fromFiles, $files);
    }

    protected static function strRemoveStart($string, $start)
    {
        if (strpos($string, $start) === 0) {
            return substr($string, strlen($start));
        } else {
            return $string;
        }
    }
}
