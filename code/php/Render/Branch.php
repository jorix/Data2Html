<?php
namespace Data2Html\Render;

use Data2Html\DebugException;
use Data2Html\Data\Lot;

class Branch
{
    use \Data2Html\Debug;
    
    private $tree;
    private $keys;
    
    public function __construct($tree, $keys = [])
    {
        if (!is_array($tree)) {
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
    
    public function getItem($keys)
    {
        $leaf = Lot::getItem($keys, $this->tree);
        if (is_string($leaf)) {
            return FileContents::getContent($leaf);
        } else {
            $leaf;
        }
    }
}
