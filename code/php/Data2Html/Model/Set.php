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
    protected $setItems = null;
    protected $keys = null;
    //protected $matchedFields = null;
    protected $baseSet = null;
    protected $link = null;
    protected $linkName = '';
    
    // To parse
    protected $matchLinked = '/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i';
        // fields as: link_name[field_name]
    protected $matchTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/';
        // value as: $${base_name} i $${link_name[field_name]}
    protected $attributeNames = array();
    protected $keywords = array();
    protected $baseAttributeNames = array(
        'title' => 'attribute'
    );
    protected $baseKeywords = array(
        'key' => array(
            'single' => true,
            'keys' => array('autoKey', 'key')
        ),
        'type' => array(
            'single' => true,
            'keys' => array('boolean', 'date', 'float', 'integer', 'number', 'string')
        ),
        'size' => array(
            'type' => 'integer',
            'keys' => array('digits', 'length')
        ),
        'validations' => array(
            'keys' => array('required', 'email', 'emails', 'url')
        ),
        'display' => array('hidden'),
        'alias' => array(
            'autoKey' =>    array('type' => 'integer', 'key' => 'autoKey'),
            'boolean' =>    array('type' => 'boolean'),
            'date' =>       array('type' => 'date'),
            'digits' =>     array('type' => 'number', 'size' => '[]'),
            'email' =>      array('type' => 'string', 'size' => '[]', 'validations' => array('email' => true)),
            'emails' =>     array('type' => 'string', 'size' => '[]', 'validations' => array('emails' => true)),
            'float' =>      array('type' => 'float'),
            'hidden' =>     array('display' => array('none')),
            'integer' =>    array('type' => 'integer'),
            'key' =>        array('key' => true),
            'length' =>     array('type' => 'string', 'size' => '[]'),
            'number' =>     array('type' => 'number', 'size' => '[]'),
            'currency' =>     array('type' => 'number', 'size' => '[]'),
            'required' =>   array('validations' => array('required' => true)),
            'no-required' =>   array('validations' => array('required' => false)),
            'string' =>     array('type' => 'string', 'size' => '[]'),
            'url' =>        array('type' => 'string', 'validations' => array('url' => true))
        ),
        'words' => array(
            'base' => 'string',
            'db' => 'string',
            'default' => null,
            'description' => 'string',
            'display' => null,
            'format' => 'string',
            'link' => 'string',
            'linkedTo' => 'array',
            'name' => 'string',
            'size' => null,
            'title' => 'string',
            'type' => null,
            'validations' => null,
            'value' => null,
            'visualClass' => 'string'
        )
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
        $this->attributeNames = array_replace_recursive(
            $this->baseAttributeNames, $this->attributeNames
        );
        $this->keywords = array_replace_recursive(
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
        
    public function getVisualItems()
    {
        if (!$this->link) {
            throw new Exception(
                "{$this->culprit} getLinkedItems(): Before get items, must create by createLink()."
            );
        }
        return $this->link->getVisualItems();
    }
    public function getVisualItemsJson()
    {
        return str_replace('"', "'", 
            Data2Html_Value::toJson($this->getVisualItems())
        );
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
        foreach ($items as $k => $v) {
            $this->parseItem($k, $v);
        }
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
                $linkedTo = $this->getLinkedTo($base, $baseItems);
                if (count($linkedTo)) {
                    $v['linkedTo'] = $linkedTo;                    
                } else {
                    if (!array_key_exists($base, $baseItems)) {
                        throw new Exception(
                            "{$this->culprit}: Defining field \"{$k}\", the base \"{$base}\" was not found."
                        );
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
                $linkedTo = $this->getLinkedTo($v['value'], $baseItems);
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
    
    protected function parseSortBy($sortBy, $baseItems) {
        return null;
    }
    
    protected function beforeParseItem(&$key, &$field)
    {
        return true;
    }
    protected function beforeAddItem(&$key, &$field)
    {
        return true;
    }
    protected function parseItem($key, $field)
    {
        if (!$this->beforeParseItem($key, $field)) {
            return;
        }
        
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
        $fieldDx = new Data2Html_Collection($field);
        $name = is_int($key) ? null : $key;
        $pField = array();
        
        $db = null;
        if (array_key_exists('db', $field)) {
            $db = $field['db'];
        } elseif ($name && 
            !array_key_exists('value', $field) && 
            !array_key_exists('base', $field)
        ) {
            $db = $name;
        }
        if ($db) {
            $pField['db'] = $db;
        }
        $alias = $this->keywords['alias'];
        $words = $this->keywords['words'];
        foreach ($field as $kk => $vv) {
            if (is_int($kk)) {
                if (array_key_exists($vv, $alias)) {
                    $word = $alias[$vv];
                    foreach ($word as $kkk => $vvv) {
                        if ($vvv === '[]') {
                            unset($word[$kkk]);
                            break;
                        }
                    }
                    $pField = array_replace_recursive($pField, $word);
                } else {
                    throw new Exception(
                        "{$this->culprit}: Alias \"{$vv}\" on field \"{$key}\" is not supported."
                    );
                }
            } else {
                if (array_key_exists($kk, $alias)) {
                    $word = $alias[$kk];
                    foreach ($word as &$vvv) {
                        if ($vvv === '[]') {
                            $vvv = $vv;
                            break;
                        }
                    }
                    $pField = array_replace_recursive($pField, $word);
                } elseif (array_key_exists($kk, $words)) {
                    $pField[$kk] = $vv;
                } else {
                    throw new Data2Html_Exception(
                        "{$this->culprit}: Word or alias \"{$kk}\" on field \"{$key}\" is not supported.",
                        $field
                    );
                }
            }
        }
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
                if (array_key_exists('db', $field) ) {
                    if (isset($field['db'])) {
                        throw new Exception(
                            "{$this->culprit}: Field \"{$key}\": `db` and `value` can not be used simultaneously."
                        );
                    }
                    unset($field['db']);
                }
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
        $pKey = $key;
        if (is_int($pKey)) {
            if (array_key_exists('base', $pField)) {
                $pKey = Data2Html_Utils::toCleanName($pField['base'], '_');
            } elseif (array_key_exists('db', $pField)) {
                $pKey = Data2Html_Utils::toCleanName($pField['db'], '_');
            }
        }
        if (is_int($pKey) || array_key_exists($key, $this->setItems)) {
            $this->fCount++;
            $pKey = $this->fPrefix . '_' . $this->fCount;
        }
        if (!$this->beforeAddItem($pKey, $pField)) {
            return;
        }
        $this->setItems[$pKey] = $pField;
        return $pKey;
    }
   
    protected function getLinkedTo($base, $baseItems)
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
}
