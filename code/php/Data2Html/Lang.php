<?php

class Data2Html_Lang
{
    private $debug = false;
    
    private $languages = null;
    private $fromFiles = null;
    private $literals = null;
    
    public function __construct()
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Lang";
        
        $this->literals = [];
        $this->fromFiles = [];
    }

    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = [
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
    
    public function load($langs, $name, $folder)
    {
        foreach(array_reverse((array)$langs) as $v) {
            $this->loadOne($v, $name, $folder);
        }
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
            $file = Data2Html_Utils::toCleanFilePath($k, '/');
            if (strpos($file, $cleanForlder) === 0) {
                $file = substr($file, strlen($cleanForlder));
            }
            $base = substr($name . '/' . strstr($file, '_lang/', true), 0, -1);
            $fullFile = $cleanForlder . $file;
            
            $flatContent = [];
            $flatFiles = [];
            $flatten = function($iterator, $flatKey) 
            use(&$flatten, &$flatContent, &$flatFiles, $fullFile) {
                while ($iterator->valid()) {
                    $newFlatKey = $flatKey . '/' . $iterator->key();
                    if ($iterator->hasChildren() ) {
                        $flatten($iterator->getChildren(), $newFlatKey);
                    } else {
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
