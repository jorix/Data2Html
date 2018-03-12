<?php
use Data2Html_Render_FileContents as _contents;

class Data2Html_Render_Branches
{
    private static $culprit = "Template object";
    
    public static function dump($subject = null)
    {
        Data2Html_Utils::dump(get_called_class(), $subject);
    }

    public static function startTree($templateName)
    {
        return [[], _contents::load($templateName)];
    }
    
    public static function getBranch($keys, $templateBranch, $required = true)
    {
        if (!is_array($keys)) {
            $keys = [$keys];
        }
        $finalKeys = array_merge($templateBranch[0], $keys);
        $tree = Data2Html_Value::getItem($templateBranch[1], $keys);
        if ($tree) {
            $result = [$finalKeys, $tree];
        } else {
            if ($required) {
                throw new Data2Html_Exception(
                    "_Render_FileContents::getBranch(['" . implode("', '", $finalKeys) . "'], ...) keys does not exist.",
                    $templateBranch[1]
                );
            } 
            $result = null;
        }
        return $result;
    }
    
    public static function getItem($keys, $templateBranch = null)
    {
        if ($templateBranch) {
            $leaf = Data2Html_Value::getItem($templateBranch[1], $keys);
            if (is_string($leaf)) {
                return _contents::getContent($leaf);
            } else {
                return $leaf ? $leaf : [];
            }
        } else {
            return _contents::getContent($keys);
        }
    }
}
