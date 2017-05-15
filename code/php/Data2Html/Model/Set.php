<?php
//abstract 
class Data2Html_Model_Set
{
    protected $originName = '';
    protected $culprit = '';
    protected $debug = false;
    
    protected $idPrefix = '';
    protected $idCount = 0;
    
    protected $setItems = array();
    
    // To parse
    protected $matchLinkedOnce = '/^[a-z]\w*\[([a-z]\w*|\d+)\]$/';
    protected $matchTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\])\}/';
    
    // Parse
    protected $keywords = array(
        'autoKey' => 'key',
        'boolean' => 'type',
        'check' => 'check',
        'currency' => 'type',
        'date' => 'type',
        'db' => 'db',
        'default' => 'default',
        'description' => 'description',
        'digits' => 'size',
        'display' => 'display',
        'email' => 'type',
        'emails' => 'type',
        'format' => 'format',
        'hidden' => 'display',
        'integer' => 'type',
        'key' => 'key',
        'length' => 'size',
        'link' => 'link',
        'linkedTo' => 'linkedTo',
        'name' => 'name',
        'number' => 'type',
        'sortBy' => 'sortBy',
        'required' => 'validations',
        'size' => 'size',
        'string' => 'type',
        'teplateItems' => 'teplateItems',
        'title' => 'title',
        'type' => 'type',
        'uniqueKey' => 'constraints',
        'url' => 'type',
        'validations' => 'validations',
        'value' => 'value',
        'visualClass' => 'visualClass',
    );
    protected $keywordsToDbTypes = array(
        'autoKey' => 'integer',
        'digits' => 'number',
        'link' => 'integer',
        'length' => 'string',
    );
    protected $typesToDbTypes = array(
        'email' => 'string',
        'url' => 'string',
    );
    protected $keywordsSingle = array(
        'check',
        'db',
        'default',
        'description',
        'key',
        'name',
        'sortBy',
      //  'size', <- join of 'length' or 'digits' 
        'title',
        'type',
        'value',
    );
    
    public function __construct($originName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->originName = $originName;
        $this->ifPrefix = str_replace('Data2Html_Model_Set_', 'd2h_', get_class($this));
        $this->culprit = $this->ifPrefix . " for \"{$this->originName}\"";
    }

    public function getItems()
    {
        return $this->setItems;
    }
    
    public function addParse($key, $field, $baseField = null)
    {
        if (is_string($field)) {
            if (substr($field, 0, 1) === '=') {
                $field = array('value' => substr($field, 1));
            } elseif (preg_match($this->matchLinkedOnce, $field)) { // Is a link
                $field = array('db' => $field);
            } else {
                throw new Exception(
                    "{$this->culprit}: Field `{$key}` as string must bee a `value` " .
                    "as \"=xxx\" or a link as \"link[name]\"."
                );
            }
        }
        $fieldDx = new Data2Html_Collection($field);
        $name = $fieldDx->getString('name', (is_int($key) ? null : $key));
        $db = null;
        if (array_key_exists('db', $field)) {
            $db = $field['db'];
        } elseif (!array_key_exists('value', $field)) {
            $db = $name;
        }
        $pKey = 0;
        if (is_string($key)) {
            $pKey = $key;
        }
        $pField = array();
        if ($name) {
           // $pField['name'] = $name;
        }
        if ($db) {
            $pField['db'] = $db;
        }
        $defTypes = new Data2Html_Collection($this->keywordsToDbTypes);
        $defaultType = null;
        foreach ($field as $kk => $vv) {
            $isValue = is_int($kk);
            if ($isValue) {
                $word = $vv;
            } else {
                $word = $kk;
            }
            if (!isset($this->keywords[$word])) {
                throw new Exception(
                    "{$this->culprit}: Word \"{$word}\" on field \"{$key}\" is not supported."
                );
            }
            $kwGroup = $this->keywords[$word];
            if ($kwGroup === $word) {
                $pField[$word] = $vv; 
            } elseif (in_array($kwGroup, $this->keywordsSingle)) {
                $pField[$kwGroup] = ($isValue ? $vv : array($kk => $vv)); 
            } else {
                if (!isset($pField[$kwGroup])) {
                    $pField[$kwGroup] = array();
                }
                if ($isValue) {
                    array_push($pField[$kwGroup], $vv);
                } else {
                    $pField[$kwGroup][$kk] = $vv;
                }
            }
            if (!$defaultType) {
                $defaultType = $defTypes->getString($word);
            }
        }
        if (!array_key_exists('title', $pField) && $name) {
            $pField['title'] = $name;
        }
        if (!array_key_exists('description', $pField) &&
            array_key_exists('title', $pField)) {
            $pField['description'] = $pField['title'];
        }
        if (!isset($pField['type']) && $defaultType) {
            $pField['type'] = $defaultType;
        }
        $value = null;
        if (array_key_exists('value', $pField)) {
            $value = $pField['value'];
        }
        /*
'/>\$\$([\w.]+)</'
'/\'\$\$([\w.]+)\'/'
'/"\$\$([\w.]+)"/'
        */
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
                $pField['teplateItems'] = $matches;
            }
        }
        if ($baseField) {
            $pField = array_replace_recursive(array(), $pField, $baseField);
        }
        return $this->addItem($pKey, $pField);
    }
    
    protected function addItem($pKey, $pItem)
    {
        if (is_int($pKey) || array_key_exists($pKey, $this->setItems)) {
            $this->idCount++;
            $pKey = $this->ifPrefix . '_' . $this->idCount;;
        }
        $this->setItems[$pKey] = $pItem;
        return $pKey;
    }
}
