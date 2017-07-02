<?php
class Data2Html_Model_Set_Base extends Data2Html_Model_Set 
{
    protected $attributeNames = array(
        'table' => 'attribute',
        'base' => 'items',
        'grids' => false,
        'forms' => false
    );
    protected $keywords = array(
        'words' => array(
            'sortBy' => 'string',
            'teplateItems' => null
        )
    );
    protected $startToOrder = array(
        '<' => 1,
        '>' => -1,
        '+' => 1,
        '-' => -1,
        '!' => -1,
    );
    protected function beforeAddItem(&$key, &$field)
    {
        // set default for sortBy 
        if (!array_key_exists('sortBy', $field)) {
            if (array_key_exists('base', $field)) {
                $field['sortBy'] = $field['base'];
            } elseif (array_key_exists('db', $field)) {
                $field['sortBy'] = $key;
            }
        }
        return true;
    }
    
    protected function parseSortBy($sortBy, $baseItems) {
        if (!is_array($sortBy)) {
            $sortBy = array($sortBy);
        } elseif ( // Already parsed 
            count($sortBy) === 2 && 
            array_key_exists('linkedTo', $sortBy) &&
            array_key_exists('items', $sortBy)
        ) {
            return $sortBy; // return as is already parsed
        }
        
        // Create a empty parsed sort
        $sortByNew = array('linkedTo' => array(), 'items' => array());
        
        $startsWith = function($haystack, $needle) {
            return (
                substr($haystack, 0, strlen($needle)) === $needle
            );
        };
        foreach ($sortBy as $item) {
            $order = 1;
            foreach ($this->startToOrder as $k => $v) {
                if ($startsWith($item, $k)) {
                    $item = substr($item, strlen($k));
                    $order = $v;
                    break;
                }
            }
            $linkedTo = $this->getLinkedTo($item, $baseItems);
            if (count($linkedTo)) {
                $sortByNew['linkedTo'] =
                    array_replace($sortByNew['linkedTo'], $linkedTo);
            } else {
                if (!array_key_exists($item, $this->setItems) && !array_key_exists($item, $baseItems)) {
                    throw new Exception(
                        "{$this->culprit}: Defining sortBy \"{$item}\", item and base was not found."
                    );
                }
            }
            $sortByNew['items'][$item] = $order;
        }
        return $sortByNew;
    }

}
