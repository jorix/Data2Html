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
    
    public function __construct($tree, $keys = [])
    {
        if (is_string($tree)) {
            $tree = FileContents::load($tree);
        } elseif (!is_array($tree)) {
            throw new DebugException(
                "Argument \$tree must be a array.",
                ['$tree' => $tree]
            );
        }
        $this->keys = (array)$keys;
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
        $finalKeys = array_merge($this->keys, $keys); 
        $tree = Lot::getItem($keys, $this->tree);
        if ($tree) {
            $result = new self($tree, $finalKeys);
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
    
    public function getTemplate($keys, $default = null)
    {
        $item = Lot::getItem($keys, $this->tree, $default);
        if (!is_string($item)) {
            return $item;
        } else {
            $final = [];
            if (array_key_exists('html', $item)) {
                $final['html'] = FileContents::getContent($item['html']);
            }
            if (array_key_exists('js', $item)) {
                $final['js'] = FileContents::getContent($item['js']);
            }
            return $final;
        }
    }
}
