<?php
//abstract 
abstract class Data2Html_Model_Set
{
    protected $setName = '';
    protected $culprit = '';
    protected $debug = false;
    
    protected $fPrefix = '';
    protected $fCount = 0;
    
    protected $model = null;
    protected $attributes = null;
    protected $baseSet = null;
    protected $setItems = null;
    protected $keys = null;
    
    // Link
    protected $link = null;
    protected $linkName = '';
    
    // To parse
    protected $matchLinked = '/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i';
        // fields as: link_name[field_name]
    protected $matchTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/';
        // value as: $${base_name} i $${link_name[field_name]}
    protected $attributeNames = array();
    protected $wordsAlias = array();
    protected $keywords = array();
    protected $baseAttributeNames = array(
        'title' => 'attribute'
    );
    protected $baseWordsAlias = array(
        'autoKey' =>    array('type' => 'integer', 'key' => 'autoKey'),
        'boolean' =>    array('type' => 'boolean'),
        'date' =>       array('type' => 'date'),
        'email' =>      array('type' => 'string', 'size' => '[]', 'validations' => 'email'),
        'emails' =>     array('type' => 'string', 'size' => '[]', 'validations' => array('emails')),
        'float' =>      array('type' => 'float'),
        'hidden' =>     array('display' => 'none'),
        'integer' =>    array('type' => 'integer'),
        'key' =>        array('key' => 'key'),
        'number' =>     array('type' => 'number', 'size' => '[]'),
        'currency' =>   array('type' => 'number', 'size' => '[]'),
        'required' =>   array('validations' => array('required')),
        'string' =>     array('type' => 'string', 'size' => '[]'),
        'text' =>       array('type' => 'text'),
        'url' =>        array('type' => 'string', 'validations' => 'url')
    );
    protected $baseKeywords = array(
        'base' => 'string',
        'db' => 'string|null',
        'default' => null,
        'description' => 'string',
        'display' => array(
            'options' => array('none', 'html', 'input', 'all')
        ),
        'format' => 'string',
        'key' => array(
            'options' => array('autoKey', 'key')
        ),
        'items' => 'array',
        'level' => 'integer',
        'link' => 'string',
        'linkedTo' => 'array',
        'name' => 'string',
        'size' => '(array)integer',
        'title' => 'string',
        'type' => array(
            'options' => array(
                'boolean', 'date', 'float', 'integer', 'number', 'string', 'text'
            )
        ),
        'validations' => array(
            'multiple' => true,
            'options' => array('required', 'email', 'emails', 'url')
        ),
        'value' => null,
        'visualClass' => 'string'
    );
    
    protected $sortByStartToOrder = array(
        '<' => 1,
        '>' => -1,
        '+' => 1,
        '-' => -1,
        '!' => -1,
    );
    
    public function __construct($model, $setName, $defs, $baseSet = null)
    {
        $this->debug = Data2Html_Config::debug();
        $this->setName = $setName;
        $this->fPrefix = str_replace('Data2Html_Model_Set_', 'd2h_', get_class($this));
        if ($setName) {
            $this->culprit = $this->fPrefix . 
                " for \"{$model->getModelName()}->{$this->setName}\"";
        } else {
            $this->culprit = 
                $this->fPrefix . " for \"{$model->getModelName()}\"";
        }
        
        $this->model = $model;
        $this->attributeNames = array_replace(
            $this->baseAttributeNames, $this->attributeNames
        );
        $this->wordsAlias = array_replace(
            $this->baseWordsAlias, $this->wordsAlias
        );
        $this->keywords = array_replace(
            $this->baseKeywords, $this->keywords
        );
        $this->baseSet = $baseSet;
        
        // Read defs
        $this->attributes = array();
        $attNamesDx = new Data2Html_Collection($this->attributeNames);
        foreach ($defs as $k => $v) {
            $attributeType = $attNamesDx->getString($k);
            if ($attributeType === null) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: Attribute \"{$k}\" is not supported.",
                    $defs
                );
            }
            switch ($attributeType) {
                case 'attribute':
                    $this->attributes[$k] = $v;
                    break;
                case 'items':
                    $this->setItems = array();
                    $this->parseItems($v);
                    break;
            }
        }
    }
        
    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = array(
                'attributes' => $this->attributes,
                'keys' => $this->keys,
                'setItems' => $this->setItems
            );
        }
        Data2Html_Utils::dump($this->culprit, $subject);
    }
    public function getCulprit()
    {
        return $this->culprit;
    }
    
    // -----------------------
    // Obtaining
    // -----------------------
    public function getModelName()
    {
        return $this->model->getModelName();
    }

    public function getTableName()
    {
        return $this->model->getTableName();
    }
    
    public function getBase()
    {
        return $this->baseSet;
    }
    
    public function getAttribute($attrName, $default = null)
    {
        if (!array_key_exists($attrName, $this->attributeNames)) {
            throw new Exception(
                "{$this->culprit} getAttribute(): Attribute \"{$attrName}\" is not supported."
            );
        }
        return Data2Html_Value::getItem($this->attributes, $attrName, $default);
    }
    
    public function getSort()
    {
        return null;
    }
    
    public function getItems()
    {
        return $this->setItems;
    }
    
    public function getKeys()
    {
        return $this->keys;
    }
    
    // -----------------------
    // Linking
    // -----------------------
    public function createLink()
    {
        if ($this->link) {
            return $this->link;
        }
        $this->linkName = 'main';
        $this->link = new Data2Html_Model_Link($this->culprit, $this);
        return $this->link;
    }
    
    public function addToLink($link)
    {
        if (!$this->link) {
            $this->linkName = $this->fPrefix;
            $link->add($this->linkName, $this->getItems());
            $this->link = $link;
        }
        return $this->link->getItems($this->linkName);
    }
    
    public function getLinkedFrom()
    {
        if (!$this->link) {
            throw new Exception(
                "{$this->culprit} getLinkedFrom(): Before get the link, must create by createLink()."
            );
        }
        return $this->link->getFrom();
    }
    
    public function getLinkedItems()
    {
        if (!$this->link) {
            throw new Exception(
                "{$this->culprit} getLinkedItems(): Before get items, must create by createLink()."
            );
        }
        return $this->link->getItems($this->linkName);
    }

    public function getLinkedKeys()
    {
        if (!$this->link) {
            throw new Exception(
                "{$this->culprit} getLinkedKeys(): Before get keys, must create by createLink()."
            );
        }
        return $this->link->getKeys();
    }
    
    // -----------------------
    // Internal
    // -----------------------
    protected function parseItems($items)
    {
        $this->parseSetItems(0, '', $items);

        // Extend fields width a base field
        if ($this->baseSet) {
            $baseItems = $this->baseSet->getItems();
        } else {
            $baseItems = $this->setItems;
        }
        
        $keys = array();
        foreach ($this->setItems as $k => &$v) {
            if (array_key_exists('base', $v)) {
                $base = $v['base'];
                $linkedTo = $this->parseLinkedTo($base, $baseItems);
                if (count($linkedTo)) {
                    $v['linkedTo'] = $linkedTo;                    
                } else {
                    if (!array_key_exists($base, $baseItems)) {
                        throw new Exception(
                            "{$this->culprit}: Defining field \"{$k}\", the base \"{$base}\" was not found."
                        );
                    }
                    if ($v['db'] === null) {
                        unset($v['db']);
                    }
                    $v = array_replace_recursive(array(), $baseItems[$base], $v);
                }
            }
            
            if (array_key_exists('sortBy', $v) && $v['sortBy']) {              
                $sortByNew = $this->parseSortBy($v['sortBy'], $baseItems);
                if ($sortByNew) {
                    $v['sortBy'] = $sortByNew;
                } else {
                    unset($v['sortBy']);
                }
            }
            
            // Matches values
            if (array_key_exists('teplateItems', $v)) {
                $linkedTo = $this->parseLinkedTo($v['value'], $baseItems);
                if (count($linkedTo)) {
                    $v['linkedTo'] = $linkedTo;
                }
                foreach ($v['teplateItems'] as $kk => $vv) {
                    $base = $vv['base'];
                    if (!array_key_exists($base, $this->setItems) &&
                        !array_key_exists($base, $baseItems) &&
                        !array_key_exists($base, $linkedTo)
                    ) {
                        throw new Data2Html_Exception(
                            "{$this->culprit}: On template \"{$kk}\", the \"{$base}\" is not a base or link.",
                            $this->setItems
                        );
                    }
                }
            }
            if (array_key_exists('key', $v)) {
                $keys[$k] = array();
            }
        }
        if (count($keys) === 0) {
            $keys = $this->baseSet->getKeys();
        }
        $this->keys = $keys;
    }

    private function parseSetItems($level, $prefix, $items)
    {
        foreach ($items as $k => $v) {
            $fieldName = is_int($k) ? $k : $prefix . $k;
            
            // Parse item
            if ($this->beforeParseItem($fieldName, $v)) {
                list($pName, $pField) = $this->parseItem($level, $fieldName, $v);
                if ($this->beforeAddItem($pName, $pField)) {
                    $this->setItems[$pName] = $pField; // Add the ITEM!
                }
            }
            
            if (is_array($v) && array_key_exists('items', $v)) {
                $this->parseSetItems(
                    $level + 1,
                    Data2Html_Value::getItem($v, 'prefix', ''),
                    $v['items']
                );
            }
        }
    }
    
    // -----------------------
    // To overwrite in the subclasses
    // -----------------------
    protected function beforeParseItem(&$fieldName, &$field)
    {
        return true;
    }
    protected function beforeAddItem(&$parsedName, &$parsedField)
    {
        return true;
    }

    // Overwrite this function to `{ return null; }` to ignore sortBy.
    protected function parseSortBy($sortBy, $baseItems) {
        if (!is_array($sortBy)) {
            $sortBy = array($sortBy);
        } elseif ( // Already parsed 
            array_key_exists('items', $sortBy) && (
                count($sortBy) === 1 || 
                (count($sortBy) === 2 && array_key_exists('linkedTo', $sortBy))
            )
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
            foreach ($this->sortByStartToOrder as $k => $v) {
                if ($startsWith($item, $k)) {
                    $item = substr($item, strlen($k));
                    $order = $v;
                    break;
                }
            }
            $linkedTo = $this->parseLinkedTo($item, $baseItems);
            if (count($linkedTo)) {
                $sortByNew['linkedTo'] =
                    array_replace($sortByNew['linkedTo'], $linkedTo);
            } else {
                if (!array_key_exists($item, $this->setItems) && !array_key_exists($item, $baseItems)) {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: Defining sortBy \"{$item}\", item and base was not found.",
                        $sortBy
                    );
                }
            }
            $sortByNew['items'][$item] = array(
                'base' => $item,
                'order' => $order
            );
        }
        if (count($sortByNew['linkedTo']) === 0) {
            unset($sortByNew['linkedTo']);
        }
        return $sortByNew;
    }
    
    // -----------------------
    // Private functions
    // -----------------------
    private function parseLinkedTo($base, $baseItems)
    {
        $matches = null;
        preg_match_all($this->matchLinked, $base, $matches);
        
        $linkedTo = array();
        for ($i = 0; $i < count($matches[0]); $i++) {
            if ($matches[1][$i] && $matches[2][$i]) {
                $baseLink = $matches[1][$i];
                $match = $matches[0][$i];
                $linkedTo[$match] = array(
                    'link' => $baseLink,
                    'base' => $matches[2][$i]
                );
                if (!array_key_exists($baseLink, $baseItems)) {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: Defining \"{$base}\", the link \"{$baseLink}\" was not found.",
                        $baseItems
                    );
                }
                if (!array_key_exists('link', $baseItems[$baseLink])) {
                    throw new Exception(
                        "{$this->culprit}: Defining \"{$base}\", the \"{$baseLink}\" is not a link."
                    );
                }
                $linkedTo[$match]['linkedWith'] = $baseItems[$baseLink]['link'];
            }
        }
        return $linkedTo;
    }
    
    private function parseItem($level, $fieldName, $field)
    {
        
        if (is_string($field)) {
            if (substr($field, 0, 1) === '=') {
                $field = array('value' => substr($field, 1));
            } elseif ($this->baseSet) {
                $field = array('base' => $field);
            } else {
                $matches = null;
                preg_match_all($this->matchLinked, $field, $matches);
                if (count($matches[0]) > 0) {
                    $field = array('base' => $field);
                } else {
                    $field = array('db' => $field);
                }
            }
        }

        $name = is_int($fieldName) ? null : $fieldName;
        $db = null;
        if (isset($field['db'])) {
            $db = $field['db'];
        } elseif ($name && 
            !array_key_exists('value', $field) && 
            !array_key_exists('base', $field) &&
            !array_key_exists('db', $field)) {
            $db = $name;
        }
        
        $alias = $this->wordsAlias;
        $words = $this->keywords;

        // Create parsed field
        $pField = array();
        foreach ($field as $kk => $vv) {
            if ($kk === 'items') { continue; }
            if (is_int($kk)) {
                if (array_key_exists($vv, $alias)) {
                    $this->applyAlias($fieldName, $pField, $vv, $alias[$vv]);
                } else {
                    throw new Exception(
                        "{$this->culprit}: Alias \"{$vv}\" on field \"{$fieldName}\" is not supported."
                    );
                }
            } else {
                if (array_key_exists($kk, $alias)) {
                    $this->applyAlias($fieldName,$pField, $vv, $alias[$kk]);
                } else {
                    $this->applyWord($fieldName, $pField, $kk, $vv);
                }
            }
        }

        // Final words: level, db, value and teplateItems
        if (!array_key_exists('level', $pField)) {
            $pField['level'] = $level;
        }
        $pField['db'] = $db ? $db : null;
        if (!array_key_exists('base', $pField)) {
            if (!array_key_exists('title', $pField) && $name) {
                $pField['title'] = $name;
            }
            if (!array_key_exists('description', $pField) &&
                array_key_exists('title', $pField)) {
                $pField['description'] = $pField['title'];
            }
        }
        if (array_key_exists('value', $pField)) {
            $value = $pField['value'];
            if ($value) {
                if (isset($field['db'])) {
                    throw new Exception(
                        "{$this->culprit}: Field \"{$fieldName}\": `db` and `value` can not be used simultaneously."
                    );
                }
                $field['db'] = null;
                $matches = null;
                // $${name} | $${link[name]}
                preg_match_all($this->matchTemplate, $value, $matches);
                if (count($matches[0]) > 0) {
                    if (!array_key_exists('type', $pField)) {
                        $pField['type'] = 'string';
                    }
                    $tItems = array();
                    for ($i = 0; $i < count($matches[0]); $i++) {
                        $tItems[$matches[0][$i]] = array(
                            'base' => $matches[1][$i]
                        );
                    }
                    $pField['teplateItems'] = $tItems;
                }
            }
        }
        
        // Set the keyName for this field
        $pKey = $fieldName;
        if (is_int($pKey)) {
            if (array_key_exists('base', $pField)) {
                $pKey = Data2Html_Utils::toCleanName($pField['base'], '_');
            } elseif (isset($pField['db'])) {
                $pKey = Data2Html_Utils::toCleanName($pField['db'], '_');
            }
        }
        if (is_int($pKey) || array_key_exists($fieldName, $this->setItems)) {
            $this->fCount++;
            $pKey = $this->fPrefix . '_' . $this->fCount;
        }
        return array($pKey, $pField);
    }
    
    private function applyAlias($fieldName, &$pField, $aliasValue, $toWord)
    {
        // $word = $alias[$kk];
        foreach ($toWord as $k => $v) {
            if ($v === '[]') {
                $v = $aliasValue;
            }
            if (!array_key_exists($k, $pField)) {
                $this->applyWord($fieldName, $pField, $k, $v);
            } elseif (is_array($pField[$k])) {
                $this->dump([$v, $pField]);
                foreach ((array)$v as $vv) {
                    if (!in_array($vv, $pField[$k])) {
                        $this->applyWord(
                            $fieldName, $pField, $k, $vv
                        );
                    }
                }
            }
        }
    }
    
    private function applyWord($fieldName, &$pField, $wordName, $word)
    {
        if (!array_key_exists($wordName, $this->keywords)) {
            throw new Data2Html_Exception(
                "{$this->culprit}: Word \"{$wordName}\" on field \"{$fieldName}\" is not supported.",
                $pField
            );
        }
        $keyword = $this->keywords[$wordName];
        if (is_array($keyword)) {
            if (array_key_exists('multiple', $keyword) && $keyword['multiple'] === true) {
                $word = (array)$word;
                if (array_key_exists($wordName, $pField)) {
                    $newWord = $pField[$wordName];
                } else {
                    $newWord = array();
                }
                foreach ($word as $vvv) {
                    if (!in_array($vvv, $keyword['options'])) {
                        throw new Data2Html_Exception(
                            "{$this->culprit}: Option \"{$vvv}\" on keyword \"{$wordName}\" on field \"{$fieldName}\" is not valid.",
                            $pField
                        );
                    }
                    if (!in_array($vvv, $newWord)) {
                        array_push($newWord, $vvv);
                    }
                }
                $word = $newWord;
            } else {
                if (!in_array($word, $keyword['options']) ) {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: Option \"{$word}\" on keyword \"{$wordName}\" on field \"{$fieldName}\" is not valid.",
                        $pField
                    );
                }
            }
        } else {
            switch ($keyword) {
                case 'string':
                    if (!is_string($word)) {
                        throw new Data2Html_Exception(
                            "{$this->culprit}: Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'string'.",
                            $pField
                        );
                    }
                    break;
                case 'string|null':
                    if (!is_string($word) && !is_null($word)) {
                        throw new Data2Html_Exception(
                            "{$this->culprit}: Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'string' o null.",
                            $pField
                        );
                    }
                    break;
                case 'integer':
                    if (!is_int($word)) {
                        throw new Data2Html_Exception(
                            "{$this->culprit}: Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'integer'.",
                            $pField
                        );
                    }
                    break;
                case 'array':
                    if (!is_array($word)) {
                        throw new Data2Html_Exception(
                            "{$this->culprit}: Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'array'.",
                            $pField
                        );
                    }
                    break;
                case '(array)integer':
                    $word = (array)$word;
                    foreach ($word as $vvv) {
                        if (!is_int($vvv)) {
                            throw new Data2Html_Exception(
                                "{$this->culprit}: Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a array of integers.",
                                $pField
                            );
                        }
                    }
                    break;
            }
        }
        $pField[$wordName] = $word;
    }
}
