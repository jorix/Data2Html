<?php
namespace Data2Html\Render;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Render\FileContents;

class Branch
{
    use \Data2Html\Debug;
    
    private $tree;
    private $keys;
    
    public function __construct($tree)
    {
        if (is_string($tree)) {
            $tree = FileContents::load($tree);
        } elseif (is_array($tree)) {
            if (count($tree) === 1) {
                $key0 = array_keys($tree)[0];
                switch ($key0) {
                    case 'html': 
                        $tree = ['template' => ['html' => $tree[$key0]]];
                        break;
                    case 'js': 
                        $tree = ['template' => ['js' => $tree[$key0]]];
                        break;
                }
            }
        } else {
            throw new DebugException(
                "Argument \$tree must be a string or a array.",
                ['$tree' => $tree]
            );
        }
        $this->tree = $tree;
    }
    
    public function __debugInfo()
    {
        return [
            'keys' => $this->keys,
            'tree' => $this->tree
        ];
    }
    
    public function getBranch($keys, $required = true)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $tree = Lot::getItem($keys, $this->tree);
        if ($tree) {
            $result = new self($tree); 
            $result->keys = array_merge($this->keys, $keys);
        } else {
            if ($required) {
                throw new DebugException(
                    "Keys ['" . implode("', '", $keys) . "'] does not exist.",
                    $this->__debugInfo()
                );
            }
            $result = null;
        }
        return $result;
    }
        
    public function getItem($keys, $default = null)
    {
        return Lot::getItem($keys, $this->tree, $default);
    }
            
    public function getItemsKeys()
    {
        return array_keys($this->tree) ;
    }
    
    public function getTemplate($keys, $required = true)
    {
        $item = Lot::getItem($keys, $this->tree);
        if (is_string($item)) {
            return FileContents::getContent($item);
        } elseif (is_array($item)) {
            $final = [];
            if (array_key_exists('@html', $item)) {
                $final['html'] = FileContents::getContent($item['@html']);
            } elseif (array_key_exists('html', $item)) {
                $final['html'] = $item['html'];
            }
            if (array_key_exists('@js', $item)) {
                $final['js'] = FileContents::getContent($item['@js']);
            } elseif (array_key_exists('js', $item)) {
                $final['js'] = $item['js'];
            }
            return $final;
        } elseif (is_null($item)) {
            if (!$required) {
                return null;
            } else {
                throw new DebugException(
                    "Keys ['" . implode("', '", (array)$keys) . "'] must be exists on tree.",
                    $this->__debugInfo()
                );
            }
        } else {
            throw new DebugException(
                "Keys ['" . implode("', '", (array)$keys) . "'] must be a array or string.",
                $this->__debugInfo()
            );
        }
    }
}
