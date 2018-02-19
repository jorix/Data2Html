<?php
//abstract 
abstract class Data2Html_Model_Set
{
    protected $attributeNames = array();
    protected $wordsAlias = array();
    protected $keywords = array();

    protected $setItems = null;

    // Private
    private $culprit = '';
    private $debug = false;
    
    private $fPrefix = '';
    private $fNamePrefix = '';
    private $fNameCount = 0;
    
    private $tableName = null;
    private $attributes = null;
    private $baseSet = null;
    private $keys = null;
    
    // Link
    private $link = null;
    private $linkName = '';
    
    // To parse
    private $matchLinked = '/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i';
        // fields as: link_name[field_name]
    private $matchTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/';
        // value as: $${base_name} i $${link_name[field_name]}
    private $baseAttributeNames = array(
        'title' => 'attribute',
        'items' => 'items',
    );
    private $baseWordsAlias = array(
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
    private $subItemsKey = 'items';
    private $baseKeywords = array(
        'base' => 'string',
        'db' => 'string|null',
        'default' => null,
        'description' => 'string|null',
        'display' => array(
            'options' => array('none', 'html', 'all')
        ),
        'format' => 'string',
        'key' => array(
            'options' => array('autoKey', 'key')
        ),
        'level' => 'integer',
        'link' => 'string|null',
        'leafs' => 'string',
        'linkedTo' => 'array',
        'items' => 'array',
        'name' => 'string',
        'size' => '(array)integer',
        
        // from form
        'layout-template' => 'string',
        'content-template' => 'string',
        'icon' => 'string',
        'visualClassLayout' => 'string',
        'visualClassBody' => 'string',
        'action' => 'string',
         
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
    
    private $sortByStartToOrder = array(
        '<' => 1,
        '>' => -1,
        '+' => 1,
        '-' => -1,
        '!' => -1,
    );
    
    public function __construct(
        $model,
        $setName,
        $defs,
        $baseSet = null
    ) {
        $this->debug = Data2Html_Config::debug();
        $this->fPrefix = str_replace('Data2Html_Model_Set_', 'd2h_', get_class($this));
        $this->fNamePrefix = $this->fPrefix;
        if ($setName) {
            $this->fNamePrefix .= '_' . $setName;
        }
        if (!$model) {
            $this->culprit = $this->fPrefix . " for setName \"{$setName}\"";
        } elseif ($setName) {
            $this->culprit = $this->fPrefix . 
                " for \"{$model->getModelName()}->{$setName}\"";
        } else {
            $this->culprit = $this->fPrefix . " for \"{$model->getModelName()}\"";
        }
        
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
    public function getTableName()
    {
        if ($this->baseSet) {
            return $this->baseSet->getAttribute('table');
        }
    }
    
    public function getBase()
    {
        return $this->baseSet;
    }
    
    public function getAttributeUp($attrName, $default = null)
    {
        $attr = $this->getAttribute($attrName, $default);
        if (!$attr && $this->baseSet) {
            $attr = $this->baseSet->getAttribute($attrName, $default);
        }
        return $attr;
    }
    
    public function getAttribute($attrName, $default = null)
    {
        if (!isset($this->attributeNames[$attrName])) {
            throw new Exception(
                "{$this->culprit} getAttribute(): Attribute \"{$attrName}\" is not supported."
            );
        } elseif ($this->attributeNames[$attrName] === false) {
            throw new Exception(
                "{$this->culprit} getAttribute(): Attribute \"{$attrName}\" is internal, can't be obtained by getAttribute()."
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
    // Database management
    // -----------------------
    public function dbInsert($db, &$values, &$newId)
    {
        if ($this->callbackEvent('beforeInsert', $db, $values) === false) {
            return false;
        }
        $sqlObj = new Data2Html_Controller_SqlEdit($db, $this);
        $result = $db->execute($sqlObj->getInsert($values));
        $newId = $db->lastInsertId();
        
        $this->callbackEvent('afterInsert', $db, $values, $newId);
        return $result;
    }
    
    public function dbUpdate($db, &$values, $keys)
    {
        if ($this->callbackEvent('beforeUpdate', $db, $values, $keys) === false) {
            return false;
        }
        $sqlObj = new Data2Html_Controller_SqlEdit($db, $this);
        $sqlObj->checkSingleRow($keys);
        $result = $db->execute($sqlObj->getUpdate($values));
        
        $this->callbackEvent('afterUpdate', $db, $values, $keys);
        return $result;
    }
    
    protected function callbackEvent($eventName, $db, &$values) // arguments may be 3 or 4, depends of the event
    {
        $callEvent = function ($set, $args, $response) use($eventName, $db, &$values) {
            $fn = $set->getAttribute($eventName);
            if ($fn) {
                switch (count($args)) {
                    case 3:
                        $response = $fn($set, $db, $values);
                        break;
                    case 4:
                        $response = $fn($set, $db, $values, $args[3]);
                        break;
                    default:
                        throw new Exception(
                            "{$this->culprit}: \"{$eventName}\" defined with incorrect number of arguments=" . count($args)
                        );  
                }
            }
            return $response;
        };
        $response = true;
        if ($this->baseSet) {
            $response = $callEvent($this->baseSet, func_get_args(), $response);
        }
        if ($response !== false) {
            $response = $callEvent($this, func_get_args(), $response);
        }
        return $response;
    }
    
    // -----------------------
    // Protected
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
                            "{$this->culprit}: Defining field \"{$k}\", `base` \"{$base}\" was not found."
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
        if (count($keys) === 0 && $this->baseSet) {
            $keys = $this->baseSet->getKeys();
        }
        $this->keys = $keys;
    }

    // -----------------------
    // Internal
    // -----------------------
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
            // Parse sub-items
            if (is_array($v) && array_key_exists($this->subItemsKey, $v)) {
                $this->parseSetItems(
                    $level + 1,
                    Data2Html_Value::getItem($v, 'prefix', ''),
                    $v[$this->subItemsKey]
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
            if (is_int($kk)) {
                if (is_array($vv)) {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: Field \"{$fieldName}\" is an array.",
                        $field
                    );
                } elseif (array_key_exists($vv, $alias)) {
                    $this->applyAlias($field, $fieldName, $pField, $vv, $alias[$vv]);
                } else {
                    throw new Exception(
                        "{$this->culprit}: Alias \"{$vv}\" on field \"{$fieldName}\" is not supported."
                    );
                }
            } else {
                if ($kk === $this->subItemsKey) { continue; }
                if (array_key_exists($kk, $alias) && !array_key_exists($kk, $words)) {
                    $this->applyAlias($field, $fieldName,$pField, $vv, $alias[$kk]);
                } else {
                    $this->applyWord($field, $fieldName, $pField, $kk, $vv);
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
            $this->fNameCount++;
            $pKey = $this->fNamePrefix . '_' . $this->fNameCount;
        }
        return array($pKey, $pField);
    }
    
    private function applyAlias($iField, $fieldName, &$pField, $aliasValue, $toWord)
    {
        foreach ($toWord as $k => $v) {
            if ($v === '[]') {
                $v = $aliasValue;
            }
            if (array_key_exists($k, $pField) && is_array($pField[$k])) {
                foreach ((array)$v as $vv) {
                    if (!in_array($vv, $pField[$k])) {
                        $this->applyWord($iField, $fieldName, $pField, $k, $vv);
                    }
                }
            } else {
                $this->applyWord($iField, $fieldName, $pField, $k, $v);
            }
        }
    }
    
    private function applyWord($iField, $fieldName, &$pField, $wordName, $word)
    {
        if (!array_key_exists($wordName, $this->keywords)) {
            throw new Data2Html_Exception(
                "{$this->culprit}: Word \"{$wordName}\" on field \"{$fieldName}\" is not supported.",
                $iField
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
                if (array_key_exists($wordName, $pField) && $pField[$wordName] !== $word) {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: Keyword \"{$wordName}\" on field \"{$fieldName}\" not allows multiple values, additional option \"{$word}\" refused.",
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
