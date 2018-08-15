<?php
//abstract
namespace Data2Html\Model;

use Data2Html\DebugException;
use Data2Html\Data\InfoFile;
use Data2Html\Data\Lot;
use Data2Html\Data\Parse;
use Data2Html\Data\To;

use Data2Html\Model;
 
abstract class Set
{
    use \Data2Html\Debug;
        
    protected $attributeNames = [];
    protected $wordsAlias = [];
    protected $keywords = [];

    protected $setItems = null;

    // Private generic
    private $id = '';
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
    private $matchTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\]|[a-z][\w\-]*)\}/i';
        // template as: $${base_name} or $${link_name[field_name]} or $${tow-word}
    private $baseAttributeNames = [
        'title' => 'attribute',
        'options' => 'attribute',
        'items' => 'items',
    ];
    private $baseWordsAlias = [
        'autoKey' =>    ['type' => 'integer', 'size' => '$${}|9', 'key' => 'autoKey'],
        'boolean' =>    ['type' => 'boolean'],
        'date' =>       ['type' => 'date'],
        'datetime' =>   ['type' => 'datetime'],
        'email' =>      ['type' => 'string', 'size' => '$${}|100', 'validations' => ['email' => true]],
        'emails' =>     ['type' => 'string', 'size' => '$${}|100', 'validations' => ['emails' => true]],
        'float' =>      ['type' => 'float'],
        'hidden' =>     ['display' => 'none'],
        'integer' =>    ['type' => 'integer', 'size' => '$${}|9'],
        'key' =>        ['key' => 'key'],
        'number' =>     ['type' => 'number', 'size' => '$${}|9'],
        'currency' =>   ['type' => 'number', 'size' => '$${}|13,2'],
        'required' =>   ['validations' => ['required' => true]], // TODO: '$${}|true']],
        'string' =>     ['type' => 'string', 'size' => '$${}|100'],
        'text' =>       ['type' => 'text'],
        'url' =>        ['type' => 'string', 'validations' => ['url' => true]]
    ];
    private $subItemsKey = 'items';
    private $baseKeywords = [
        'base' => 'string',
        'db' => 'string|null',
        'default' => null,
        'description' => 'string|null',
        'display'   => ['options' => ['none', 'html', 'all']],
        'format'    => 'string',
        'key'       => ['options' => ['autoKey', 'key']],
        'level'     => 'integer',
        'link'      => 'string|null',
        'leaves'    => 'string',
        'linkedTo'  => 'array',
        'items'     => 'array',
        'name'      => 'string',
        'size'      => '[integer]',
        
        // from form
        'layout-template' => 'string',
        'content-template' => 'string',
        'icon' => 'string',
        'visualClassLayout' => 'string',
        'visualClassBody' => 'string',
        'visual-size' => 'integer',
        'action' => 'string',
         
        'title' => 'string',
        'type' => [
            'options' => [
                'boolean', 'date', 'datetime', 'float', 'integer', 'number', 'string', 'text'
            ]
        ],
        'validations' => [
            'multiple' => true,
            'options' => ['required', 'email', 'emails', 'url']
        ],
        'value' => null,
        'visualClass' => 'string'
    ];
    
    private $sortByStartToOrder = [
        '<' => 1,
        '>' => -1,
        '+' => 1,
        '-' => -1,
        '!' => -1,
    ];
    
    private static $visualWords = array(
        'display', 'format', 'size', 'title', 'type', 'validations', 'default', 'db'
    );
    
    public function __construct(
        Model $model,
        $setName,
        $defs,
        $baseSet = null
    ) { 
        if (!is_array($defs)) {
            throw new DebugException("Definitions must be a associative array.", $defs);
        }
        $setTypeName = str_replace('Data2Html\\Model\\Set\\', '', get_class($this));
        $this->fNamePrefix = 'd2h_' . $setTypeName;
        if ($setName) {
            $this->fNamePrefix .= '_' . $setName;
            $this->id = $model->getId()  . '_' . $setTypeName . '_' . $setName;
        } else {
            $this->id = $model->getId() . $setTypeName;
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
        $this->attributes = [];
        $attNamesDx = new Lot($this->attributeNames);
        foreach ($defs as $k => $v) {
            $attributeType = $attNamesDx->getString($k);
            if ($attributeType === null) {
                throw new DebugException("Attribute \"{$k}\" is not supported.", $defs);
            }
            switch ($attributeType) {
                case 'attribute':
                    $this->attributes[$k] = $v;
                    break;
                case 'items':
                    $this->setItems = [];
                    $this->parseItems($v);
                    break;
            }
        }
    }
    
    public function getId()
    {
        return $this->id;
    }    
  
    public function __debugInfo()
    {
        return [
            'set-info' => [
                'tableName' => $this->getTableName(),
                'setId' => $this->getId(),
                'setClass' => get_class($this)
            ],
            'attributes' => $this->attributes,
            'keys' => $this->keys,
            'setItems' => $this->setItems
        ];
    }
    
    // -----------------------
    // Obtaining info
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

    public function getAttributeUp($attributeKeys, $default = null)
    {
        $attr = $this->getAttribute($attributeKeys);
        if ($attr === null) {
            if ($this->baseSet) {
                $attr = $this->baseSet->getAttribute($attributeKeys, $default);
            } else {
                $attr = $default;
            }
        }
        return $attr;
    }
    
    public function getAttribute($attributeKeys, $default = null)
    {
        if (is_array($attributeKeys)) {
            $attrName = $attributeKeys[0];
        } else {
            $attrName = $attributeKeys;
        }
        if (!isset($this->attributeNames[$attrName])) {
            throw new DebugException("Attribute \"{$attrName}\" is not supported.");
        } elseif ($this->attributeNames[$attrName] === false) {
            throw new DebugException(
                "Attribute \"{$attrName}\" is internal, can't be obtained by getAttribute()."
            );
        }
        return Lot::getItem($attributeKeys, $this->attributes, $default);
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
    // To overwrite in the subclasses
    // -----------------------
    protected function beforeParseItem(&$fieldName, &$field)
    {
        return true;
    }
    protected function beforeApplyBase(&$baseField, &$field)
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
            $sortBy = [$sortBy];
        } elseif ( // Already parsed 
            array_key_exists('items', $sortBy) && (
                count($sortBy) === 1 || 
                (count($sortBy) === 2 && array_key_exists('linkedTo', $sortBy))
            )
        ) {
            return $sortBy; // return as is already parsed
        }
        
        // Create a empty parsed sort
        $sortByNew = ['linkedTo' => [], 'items' => []];
        
        foreach ($sortBy as $item) {
            $order = 1;
            foreach ($this->sortByStartToOrder as $k => $v) {
                if (self::startsWith($item, $k)) {
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
                    throw new DebugException(
                        "Defining sortBy \"{$item}\", item and base was not found.",
                        $sortBy
                    );
                }
            }
            $sortByNew['items'][$item] = [
                'base' => $item,
                'order' => $order
            ];
        }
        if (count($sortByNew['linkedTo']) === 0) {
            unset($sortByNew['linkedTo']);
        }
        return $sortByNew;
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
        
        $keys = [];
        foreach ($this->setItems as $k => &$v) {
            if (array_key_exists('base', $v)) {
                $base = $v['base'];
                $linkedTo = $this->parseLinkedTo($base, $baseItems);
                if (count($linkedTo)) {
                    $v['linkedTo'] = $linkedTo;
                    unset($v['db']);
                    if (!array_key_exists('sortBy', $v) && count($linkedTo) === 1) {
                        $v['sortBy'] = $base; // default sort by self
                    }
                } else {
                    if (!array_key_exists($base, $baseItems)) {
                        throw new DebugException(
                            "Defining field \"{$k}\", `base` \"{$base}\" was not found."
                        );
                    }
                    if ($v['db'] === null) {
                        unset($v['db']);
                    }
                    $v = $this->applyBase($baseItems[$base], $v);
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
                        throw new DebugException(
                            "On template \"{$kk}\", the \"{$base}\" is not a base or link.",
                            $this->setItems
                        );
                    }
                }
            }
            if (array_key_exists('key', $v)) {
                $keys[$k] = [];
            }
        }
        if (count($keys) === 0 && $this->baseSet) {
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
            // Parse sub-items
            if (is_array($v) && array_key_exists($this->subItemsKey, $v)) {
                $this->parseSetItems(
                    $level + 1,
                    Lot::getItem('prefix', $v, ''),
                    $v[$this->subItemsKey]
                );
            }
        }
    }
    
    private function applyBase($baseField, $field)
    {
        $this->beforeApplyBase($baseField, $field);
        return array_replace_recursive([], $baseField, $field);
    }

    private function parseLinkedTo($base, $baseItems)
    {
        $matches = null;
        preg_match_all($this->matchLinked, $base, $matches);
        
        $linkedTo = [];
        for ($i = 0; $i < count($matches[0]); $i++) {
            if ($matches[1][$i] && $matches[2][$i]) {
                $baseLink = $matches[1][$i];
                $match = $matches[0][$i];
                $linkedTo[$match] = [
                    'link' => $baseLink,
                    'base' => $matches[2][$i]
                ];
                if (!array_key_exists($baseLink, $baseItems)) {
                    throw new DebugException(
                        "Defining \"{$base}\", the link \"{$baseLink}\" was not found.",
                        $baseItems
                    );
                }
                if (!array_key_exists('link', $baseItems[$baseLink])) {
                    throw new DebugException(
                        "Defining \"{$base}\", the \"{$baseLink}\" is not a link."
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
                $field = ['value' => substr($field, 1)];
            } elseif ($this->baseSet) {
                $field = ['base' => $field];
            } else {
                $matches = null;
                preg_match_all($this->matchLinked, $field, $matches);
                if (count($matches[0]) > 0) {
                    $field = ['base' => $field];
                } else {
                    $field = ['db' => $field];
                }
            }
        } elseif(is_array($field) && 
            $this->baseSet &&
            is_string($fieldName) &&
            !array_key_exists('base', $field)
        ) {
            $field['base'] = $fieldName;
        }

        $name = is_int($fieldName) ? null : $fieldName;
        $db = null;
        if (isset($field['db'])) {
            $db = $field['db'];
        } elseif ($name && 
            !array_key_exists('value', $field) && 
            !array_key_exists('base', $field) &&
            !array_key_exists('leaves', $field) &&
            !array_key_exists('db', $field)) {
            $db = $name;
        }
        
        $alias = $this->wordsAlias;
        $words = $this->keywords;

        // Create parsed field
        $pField = [];
        foreach ($field as $kk => $vv) {
            if (is_int($kk)) {
                if (is_array($vv)) {
                    throw new DebugException("Field \"{$fieldName}\" is an array.", $field);
                } elseif (array_key_exists($vv, $alias)) {
                    $this->applyAlias($field, $fieldName, $pField, null, $alias[$vv]);
                } else {
                    throw new DebugException(
                        "Alias \"{$vv}\" on field \"{$fieldName}\" is not supported."
                    );
                }
            } else {
                if ($kk === $this->subItemsKey) { continue; }
                if (array_key_exists($kk, $alias) && !array_key_exists($kk, $words)) {
                    $this->applyAlias($field, $fieldName, $pField, $vv, $alias[$kk]);
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
            if (isset($field['db'])) {
                throw new DebugException(
                    "Field \"{$fieldName}\": `db` and `value` can not be used simultaneously."
                );
            }
            $matches = null;
            // $${name} | $${link[name]}
            preg_match_all($this->matchTemplate, $pField['value'], $matches);
            if (count($matches[0]) > 0) {
                if (!array_key_exists('type', $pField)) {
                    $pField['type'] = 'string';
                }
                $tItems = [];
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $tItems[$matches[0][$i]] = ['base' => $matches[1][$i]];
                }
                $pField['teplateItems'] = $tItems;
            }
        }
        
        // Set the keyName for this field
        $pKey = $fieldName;
        if (is_int($pKey)) {
            if (array_key_exists('base', $pField)) {
                $pKey = self::toCleanName($pField['base'], '_');
            } elseif (isset($pField['db'])) {
                $pKey = self::toCleanName($pField['db'], '_');
            }
        }
        if (is_int($pKey) || array_key_exists($fieldName, $this->setItems)) {
            $this->fNameCount++;
            $pKey = $this->fNamePrefix . '_' . $this->fNameCount;
        }
        return [$pKey, $pField];
    }
    
    private function applyAlias($iField, $fieldName, &$pField, $aliasValue, $toWord)
    {
        foreach ($toWord as $k => $v) {
            if (is_string($v) && self::startsWith($v, '$${}')) {
                if ($aliasValue) {
                    $v = $aliasValue;
                } elseif (self::startsWith($v, '$${}|')) {
                    $v = substr($v, 5);
                }
                $keywordType = $this->keywords[$k];
                switch ($keywordType) {
                    case '[integer]':
                        $v = Parse::integerArray($v);
                        break;
                    case 'integer':
                        $v = Parse::integer($v);
                        break;
                    case 'boolean':
                        $v = Parse::integer($v);
                        break;
                }        
            }
            $this->applyWord($iField, $fieldName, $pField, $k, $v);
        }
    }
    
    private function applyWord($iField, $fieldName, &$pField, $wordName, $word)
    {
        if (!array_key_exists($wordName, $this->keywords)) {
            throw new DebugException(
                "Word \"{$wordName}\" on field \"{$fieldName}\" is not supported.",
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
                    $newWord = [];
                }
                foreach ($word as $kkk => $vvv) {
                    if (is_integer($kkk)) {
                        throw new DebugException(
                            "Invalid usage of multiple keyword \"{$wordName}\" on field \"{$fieldName}\" is not valid.",
                            [$pField, $kkk, $word]
                        );
                    }
                    if (!in_array($kkk, $keyword['options'])) {
                        throw new DebugException(
                            "Option \"{$kkk}\" on keyword \"{$wordName}\" on field \"{$fieldName}\" is not valid.",
                            $pField
                        );
                    }
                    $newWord[$kkk] = $vvv;
                }
                $word = $newWord;
            } else {
                if (!in_array($word, $keyword['options']) ) {
                    throw new DebugException(
                        "Option \"{$word}\" on keyword \"{$wordName}\" on field \"{$fieldName}\" is not valid.",
                        $pField
                    );
                }
                if (array_key_exists($wordName, $pField) && $pField[$wordName] !== $word) {
                    throw new DebugException(
                        "Keyword \"{$wordName}\" on field \"{$fieldName}\" not allows multiple values, additional option \"{$word}\" refused.",
                        $pField
                    );
                }
            }
        } else {
            switch ($keyword) {
                case 'string':
                    if (!is_string($word)) {
                       throw new DebugException(
                            "Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'string'.",
                            $pField
                        );
                    }
                    break;
                case 'string|null':
                    if (!is_string($word) && !is_null($word)) {
                        throw new DebugException(
                            "Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'string' o null.",
                            $pField
                        );
                    }
                    break;
                case 'integer':
                    if (!is_int($word)) {
                        throw new DebugException(
                            "Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'integer'.",
                            $pField
                        );
                    }
                    break;
                case 'array':
                    if (!is_array($word)) {
                        throw new DebugException(
                            "Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a 'array'.",
                            $pField
                        );
                    }
                    break;
                case '[integer]':
                    $word = (array)$word;
                    foreach ($word as $vvv) {
                        if (!is_int($vvv)) {
                            throw new DebugException(
                                "Keyword \"{$wordName}\" on field \"{$fieldName}\" must be a array of integers.",
                                $pField
                            );
                        }
                    }
                    break;
            }
        }
        $pField[$wordName] = $word;
    }
    
    // -----------------------
    // Static functions
    // -----------------------
    public static function getVisualItems($lkItems)
    {
        $visualItems = array();
        foreach ($lkItems as $k => $v) {
            if (!Lot::getItem('virtual', $v)) {
                if (!is_int($k)) {
                    $item = array();
                    $visualItems[$k] = &$item;
                    foreach (self::$visualWords as $w) {
                        if (array_key_exists($w, $v)) {
                            $item[$w] = $v[$w];
                        }
                    }
                    unset($item);
                }
            }
        }
        return $visualItems;
    }
    
    // -----------------------
    // Internal Static functions
    // -----------------------    
    protected static function startsWith($haystack, $needle)
    {
        return (
            substr($haystack, 0, strlen($needle)) === $needle
        );
    }
    
    protected static function toCleanName($str, $delimiter = '-')
    {
        //test: echo InfoFile::toCleanName('Xús_i[ sin("lint CC") ]+3');
        $str = strtolower(trim($str, " '\"_|+-,.[]()"));
        $str = str_replace("'", '"', $str); // To protect apostrophes to not 
            // confuse with accented letters converted to ASCII//TRANSLIT, á='a
        $str = iconv('UTF-8', 'ASCII//TRANSLIT', $str);
        $str = preg_replace("/[^ \"\_|+-,\.\[\]\(\)a-zA-Z0-9]/", '', $str);
        $str = preg_replace("/[ \"\_|+-,\.\[\]\(\)]+/", $delimiter, $str);
        return $str;
    }
}
