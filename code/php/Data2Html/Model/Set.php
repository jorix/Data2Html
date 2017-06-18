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
    protected $setItems = null;
    protected $keys = null;
    //protected $matchedFields = null;
    protected $baseItems = null;
    
    // To parse
    protected $matchLinked = '/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i';
        // fields as: link_name[field_name]
    protected $matchTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/';
        // value as: $${base_name} i $${link_name[field_name]}
    
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
        'words' => array(
            'autoKey' => array('type' => 'integer', 'key' => 'autoKey'),
            'key' => array('key' => true),
            'email' => array('type' => 'string', 'validations' => array('email' => true)),
            'emails' => array('type' => 'string', 'validations' => array('emails' => true)),
            'required' => array('validations' => array('required' => true)),
            'url' => array('type' => 'string', 'validations' => array('url' => true)),
            'digits' => array('type' => 'number', 'size' => '[]'),
            'length' => array('type' => 'string', 'size' => '[]'),
            'hidden' => array('display' => 'hidden'),
            'boolean' => array('type' => 'boolean'),
            'date' => array('type' => 'date'),
            'float' => array('type' => 'float'),
            'integer' => array('type' => 'integer'),
            'number' => array('type' => 'number'),
            'string' => array('type' => 'string'),
            'format' => 'string',
            'base' => 'string',
            'name' => 'string',
            'db' => 'string',
            'title' => 'string',
            'link' => 'string',
            'linkedTo' => 'array',
            'teplateItems' => null,
            'description' => 'string',
            'default' => null,
            'validations' => null,
            'type' => null,
            'display' => null,
            'value' => null,
            'sortBy' => 'string',
            'visualClass' => 'string'
        )
    );
    
    protected $keywords = array();
    
    public function __construct($model, $setName, $defs, $baseItems = null)
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
        
        // Read defs
        $this->model = $model;
        $this->keywords = array_replace_recursive(
            array(), $this->baseKeywords, $this->keywords
        );
        $this->baseItems = $baseItems;
        $this->setItems = array();
        $this->parseItems($defs);
    }
    
    public function getItems()
    {
        return $this->setItems;
    }
    
    public function dump()
    {
        Data2Html_Utils::dump($this->culprit, array(
            'setItems' => $this->setItems,
            'keys' => $this->keys,
            //'matchedFields' => $this->matchedFields,
        ));
    }
    public function getName()
    {
        return $this->setName;
    }
    
    protected function parseItems($items)
    {
        foreach ($items as $k => $v) {
            $this->parseItem($k, $v);
        }
        // Extend fields width a base field
        if ($this->baseItems) {
            $baseItems = $this->baseItems;
        } else {
            $baseItems = $this->setItems;
        }
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
        }

        // Matches values
        $keys = array();
        foreach ($this->setItems as $k => &$v) {
            if (array_key_exists('teplateItems', $v)) {
                $linkedTo = $this->getLinkedTo($v['value'], $baseItems);
                if (count($linkedTo)) {
                    $v['linkedTo'] = $linkedTo;
                }
            }
            if (array_key_exists('key', $v)) {
                array_push($keys, $k);
            }
        }
        $this->keys = $keys;
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
            } elseif ($this->baseItems) {
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
        // if ($this->baseItems) {
            // array_key_exists('db', $field)
            // $field = array('base' => $field);
            // if (count($linkedTo)) {
                // $field = array('linkedTo' => $linkedTo);
            // } else
        // } else {
            // $field = array('db' => $field);
        // }
        $words = $this->keywords['words'];
        foreach ($field as $kk => $vv) {
            if (is_int($kk)) {
                if (!array_key_exists($vv, $words)) {
                    throw new Exception(
                        "{$this->culprit}: Word \"{$vv}\" on field \"{$key}\" is not supported."
                    );
                }
                $pField = array_replace_recursive(array(), $words[$vv], $pField);
            } else {
                if (!array_key_exists($kk, $words)) {
                    throw new Exception(
                        "{$this->culprit}: Word \"{$kk}\" on field \"{$key}\" is not supported."
                    );
                }
                $word = $words[$kk];
                if (is_Array($word)) {
                    foreach ($word as &$vvv) {
                        if ($vvv === '[]') {
                            $vvv = $vv;
                            break;
                        }
                    }
                    $pField[$kk] = $word;
                } else {
                    $pField[$kk] = $vv;
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
                        $tItems[] = array(
                            'match' => $matches[0][$i],
                            'base' => $matches[1][$i]
                        );
                    }
                    $pField['teplateItems'] = $tItems;
                }
            }
            // // Templates as: $${name} | $${link[name]}
            // $matches = null;
            // preg_match_all($this->matchTemplate, $value, $matches);
            // if (count($matches[0]) > 0) {
                // if (!array_key_exists('type', $pField)) {
                    // $pField['type'] = 'string';
                // }
                // $pField['teplateItems'] = $matches;
            // }
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
                array_push($linkedTo, array(
                    'match' => $matches[0][$i],
                    'link' => $baseLink,
                    'base' => $matches[2][$i],
                ));
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
            }
        }
        return $linkedTo;
    }
}
