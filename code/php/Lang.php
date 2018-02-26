<?php

class Data2Html_Lang
{
    private $debug = false;
    
    private $languages = null;
    private $fromFiles = null;
    private $literals = null;
    
    public function __construct($language)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Lang";
        
        $language = strtolower($language);
        $cfgLangs = Data2Html_Config::get('languages');
        if (array_key_exists($language, $cfgLangs)) {
            $this->languages = explode(',', $cfgLangs[$language]);
        } else {
            $this->languages = [$language];
        }
        
        $this->literals = ['lang' => $language];
        $this->fromFiles = [];
    }

    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = [
                'languages-priority' => $this->languages,
                'literals' => $this->literals,
                'fromFiles' => $this->fromFiles,
            ];
        }
        Data2Html_Utils::dump($this->culprit, $subject);
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
        if ($this->debug) {
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
    
    public function load($name, $folders)
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
    
    public static function jsCode($lang)
    {
        $lang = new Data2Html_Lang($lang);
        $lang->load('', Data2Html_Autoload::getCodeFolder() . '/../js');
        return "
            var __ = (function () {
                
            var literals = " . Data2Html_Value::toJson(
                $lang->getLiterals(),
                Data2Html_Config::debug()
            ) . ";
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
        
        $cleanForlder = Data2Html_Utils::toCleanFolderPath($folder, '/');
        $literals = [];
        $files = [];
        foreach($regexKeys as $k) {
            $file = Data2Html_Utils::str_removeStart(
                Data2Html_Utils::toCleanFilePath($k, '/'),
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
            $content = Data2Html_Utils::readFilePhp($k, $this->culprit);
            $itr2 = new RecursiveArrayIterator($content);
            iterator_apply($itr2, $flatten, array($itr2, $base));
            $literals = array_replace($literals, $flatContent);
            $files = array_replace($files, $flatFiles);
        }
        $this->literals = array_replace($this->literals, $literals);
        $this->fromFiles = array_replace($this->fromFiles, $files);
    }
    
    
}
