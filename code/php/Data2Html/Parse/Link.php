<?php
class Data2Html_Parse_Link
{
    protected $debug = false;
    protected $data;
    protected $gridName;
    protected $gridBase;
    protected $links;
    protected $joins;
    protected $full2BaseNames;
    protected $baseNames;
    protected $matchLinked = '/(\b[a-z]\w*)\[\s*(\w+)\s*\]|(\b[a-z]\w*\b(?![\[\(]))/i';
    protected $reason;

    public function __construct($data)
    {
        $this->debug = Data2Html_Config::debug();
        $this->data = $data;
    }
    public function getReason()
    {
        return $this->reason;
    }
    public function getGrid($gridName)
    {
    // echo "getGrid($gridName)<br>";
        $this->gridBase = $this->data->getGrid($gridName);
        $this->gridName = $this->gridBase['name'];
        $this->reason = "Linking grid \"{$this->gridName}\" of table \"{$this->gridBase['table']}\"";
        $this->links = array();
        $this->joins = array();
        $this->full2BaseNames = array();
        $this->baseNames = array();
        
        $fields = $this->data->getColDs();
        
        foreach ($this->gridBase['columns'] as $k => &$v) {
            $linkedTo = $this->parseLinkedTo('.', $v);
            if ($linkedTo) {
                $v['linkedTo'] = $linkedTo;
                $this->searchForLinks('.', $linkedTo, $fields);
            }
            $fullName = $this->getFieldFullName('.', $k);
            if (!array_key_exists($fullName, $this->full2BaseNames)) {
                $this->addGridItem($fullName);
            }
        }
        if (array_key_exists('filter', $this->gridBase)) {
            foreach ($this->gridBase['filter']['fields'] as $k => &$v) {
                $linkedTo = $this->parseLinkedTo('.', $v);
                if ($linkedTo) {
                    $v['linkedTo'] = $linkedTo;
                    $this->searchForLinks('.', $linkedTo, $fields);
                }
            }
        }
        foreach ($this->gridBase['columns'] as $k => &$v) {
            $this->applyLinkField('.', $v);
        }
        foreach ($this->gridBase['columns'] as &$field) {
            if (array_key_exists('teplateItems', $field)) {
                for($i = 0; $i < count($field['teplateItems'][1]); $i++) {
                    $fullName = $field['teplateItems'][1][$i];
                    $field['teplateItems'][1][$i] = $this->full2BaseNames[$fullName];
                }
            }
        }
        if (count($this->joins) > 0) {
            $this->gridBase['joins'] = $this->joins;
        }
        $this->gridBase['debug->full2BaseNames'] = $this->full2BaseNames;
        $this->gridBase['debug->baseNames'] = $this->baseNames;
        return $this->gridBase;
    }
    
    protected function searchForLinks($fromLinkName, $linkedTo, $fields)
    {
    // echo "searchForLinks($fromLinkName, ..)<br>";
        for ($i = 0; $i < count($linkedTo['links']); $i++) {
            $linkName = $linkedTo['links'][$i];
            $fieldName = $linkedTo['names'][$i];
            if (!array_key_exists($linkName, $this->links)) { // Add new link
                if (count($this->links) === 0) {
                    $this->addLink(
                        null,           // $fromLinkName
                        null,           // $anchorField
                        '.',            // $toLinkName
                        '.',            // $modelName
                        $this->data,    // $dataLink
                        $this->gridBase // $gridLink
                    );
                }
                if ($linkName !== '.') {
                    $this->createLink($fromLinkName, $linkName, $fieldName, $fields);
                }
            }
        }
    }
    
    protected function createLink($fromLinkName, $toLinkName, $fieldName, $fields)
    {
            // echo "createLink($fromLinkName, $toLinkName, $fieldName, )<br>";
        if ($toLinkName !== '.') {
            $linkParts = explode('->', $toLinkName);
            $finalLinkName = $linkParts[count($linkParts)-1];
            if (!array_key_exists($finalLinkName, $fields)) { 
                throw new Exception(
                    "{$this->reason}: Linked field \"{$toLinkName}[{$fieldName}]\" uses a link \"{$finalLinkName}\" that not exist on `fields`"
                );
            }
            $anchorField = $fields[$finalLinkName];
            $linkedTo = $this->parseLinkedTo($fromLinkName, $anchorField);
            if ($linkedTo) {
                $anchorField['linkedTo'] = $linkedTo;
                $this->searchForLinks($toLinkName, $linkedTo, $fields);
            }
            if (!array_key_exists('link', $anchorField)) {
                throw new Exception(
                    "{$this->reason}: Linked field \"{$toLinkName}[{$fieldName}]\" uses field \"{$toLinkName}\" without link."
                );
            }
            list($modelName, $gridName) = 
                Data2Html_Model::explodeLink($anchorField['link']);
            $dataLink = Data2Html_Model::createModel($modelName); //, $gridName);
            $linkGrid = $dataLink->getGrid($gridName);
            $this->addLink(
                $fromLinkName,
                $anchorField,
                $toLinkName,
                $modelName,
                $dataLink,
                $linkGrid
            );
        }
    }
    
    protected function addLink(
        $fromLinkName,
        $anchorField,
        $toLinkName,
        $modelName,
        $dataLink,
        $gridLink
    ) {
    // echo "addLink($fromLinkName, , $toLinkName, $modelName,)<br>";
        $linkedKeys = $gridLink['keys'];
        if (count($linkedKeys) !== 1) {
            throw new Exception(
                "{$this->reason}: Requires a primary key with only one field, on link \"{$toLinkName}[...]\"."
            );
        }
        $toAlias = 'T' . count($this->joins);
        $toFields = $dataLink->getColDs();
        $this->links[$toLinkName] = array(
            'fromLink' => $fromLinkName,
            'model' => $modelName,
            'toAlias' => $toAlias,
            'toTable' => $gridLink['table'],
            'tableKey' => $linkedKeys[0],
            'fields' => $toFields,
            'gridName' => $gridLink['name'],
            'grid' => $gridLink,
            'gridColNames' => array_keys($gridLink['columns'])
        );
        if (count($linkedKeys) === 0) {
            throw new Exception(
                "{$this->reason}: On link \"{$toLinkName}\"" .
                " for table \"{$gridLink['table']}\"" .
                " grid without keys."
            );
        }
        $keyToField = $gridLink['columns'][$linkedKeys[0]]; //$toFields[$linkedKeys[0]];
        if (!array_key_exists('linkedTo', $keyToField)) {
            $linkedTo = $this->parseLinkedTo($toLinkName, $keyToField);
            if ($linkedTo) {
                $keyToField['linkedTo'] = $linkedTo;
            }
        }
        $this->applyLinkField($toLinkName, $keyToField);
       // die('no ->applyLinkField');
        if ($fromLinkName) {
            $fromJoin = $this->joins[$fromLinkName];
            $this->applyLinkField($fromLinkName, $anchorField);
        }
        $this->joins[$toLinkName] = array(
            'fromDbKeys' => $fromLinkName ? $anchorField['db'] : null,
            'toTable' => $gridLink['table'],
            'toAlias' => $toAlias,
            'toDbKeys' => $keyToField['db']
        );
    }
    
    protected function parseLinkedTo($linkName, $field)
    {
        $linkedTo = array(
            'matches' => array(),
            'links' => array(),
            'names' => array()
        );
        if (isset($field['db'])) {
            $this->matchLinkedTo($linkName, $field['db'], $linkedTo);
        } elseif (array_key_exists('teplateItems', $field)) {
            foreach ($field['teplateItems'][1] as $v) {
                $this->matchLinkedTo($linkName, $v, $linkedTo);
            }
        }
        if (count($linkedTo['links']) > 0) {
            return $linkedTo;
        } else {
            return null;
        }
    }
    protected function matchLinkedTo($linkName, $attribute, &$linkedTo)
    {
        $matches = null;
        preg_match_all($this->matchLinked, $attribute, $matches);
        for ($i = 0; $i < count($matches[0]); $i++) {
            array_push($linkedTo['matches'], $matches[0][$i]);
            // link[name|123] -> linked field
            if ($matches[1][$i]) {
                if ($linkName === '.') {
                    array_push($linkedTo['links'], $matches[1][$i]);
                } else {
                    array_push(
                        $linkedTo['links'], $linkName . '->' . $matches[1][$i]);
                }
                array_push($linkedTo['names'], $matches[2][$i]);
            // name -> field
            } elseif ($matches[3][$i]) {
                array_push($linkedTo['links'], $linkName);
                array_push($linkedTo['names'], $matches[3][$i]);
            }
        }
    }
    protected function applyLinkField($linkName, &$field)
    {
    // echo "applyLinkField($linkName,)<br>";
        if(array_key_exists('linkedTo', $field)) {
            if (array_key_exists('db', $field)) {
            // echo "applyLinkField($linkName,) field['db']= {$field['db']}<br>";
                $field['db'] = $this->applyLinkToDb(
                    $linkName,
                    $field['linkedTo'],
                    $field['db']
                );
                // Merge with field destination
                if (count($field['linkedTo']['links']) === 1) {
                    $linkedTo_0 = $field['linkedTo']['links'][0];
                    $toField = $this->getToField(
                        $linkedTo_0,
                        $field['linkedTo']['names'][0]
                    );
                    $this->mergeAttributes(
                        $field,
                        $toField,
                        array(
                            'description',
                            'size',
                            'teplateItems',
                            'title',
                            'type',
                            'value',
                        )
                    );
                    if (array_key_exists('value', $field)) {
                        unset($field['db']);
                    }
                    if (array_key_exists('teplateItems', $field)) {
                        unset($field['linkedTo']);
                        for($i = 0; $i < count($field['teplateItems'][1]); $i++) {
                            $field['teplateItems'][1][$i] = 
                                $linkedTo_0 . 
                                '[' . $field['teplateItems'][1][$i] . ']';
                        }
                        $linkedTo = $this->parseLinkedTo($linkName, $field);
                        if ($linkedTo) {
                            $field['linkedTo'] = $linkedTo;
                        }
                        $this->applyLinkField($linkName, $field);
                    }
                }
            } elseif (array_key_exists('teplateItems', $field)) {
                $linkedTo = $field['linkedTo'];
                for($i = 0; $i < count($linkedTo['links']); $i++) {
                    $newLinkName = $linkedTo['links'][$i];
                    $baseName = $linkedTo['names'][$i];
                    $fullName = $this->getFieldFullName($newLinkName, $baseName);
                    if (!array_key_exists($fullName, $this->full2BaseNames)) {
                        $toField = $this->getToField($newLinkName, $baseName);
                        $this->addGridItem($fullName, $toField);
                    }
                }
            }
        } elseif(isset($field['db'])) {
            $field['db'] =  $this->links[$linkName]['toAlias'] . '.' . $field['db'];
        }
    }

    protected function getFieldFullName($linkName, $fieldBaseName)
    {
        if ($linkName === '.') {
            $fullName = $fieldBaseName;
        } else {
            $fullName = $linkName . '[' . $fieldBaseName . ']';
        }
        return $fullName;
    }

    protected function addGridItem($fullName, $field = null)
    {
    // echo "addGridItem($fullName,<br>"; // New item
        $pKey = str_replace(
            array('(', '[', '->', '.', ']', ')'),
            array('_', '_', '_', '', '', ''),
            $fullName
        );
        if (substr($pKey, 0, 1) === '_') {
            $pKey = substr($pKey, 1);
        }
        if ($field) {
            if (array_key_exists($pKey, $this->gridBase['columns'])) {
                $pKey = $this->data->createIdParse($this->gridName);
            }
            $this->gridBase['columns'][$pKey] = $field;
        }
        $this->full2BaseNames[$fullName] = $pKey;
        $this->baseNames[$pKey] = array(
            'fullName' => $fullName
        );
        
        return $pKey;
    }
    
    protected function mergeAttributes(&$baseField, $linkedField, $keys) {
        foreach ($keys as $v) {
            if(!array_key_exists($v, $baseField) &&
                array_key_exists($v, $linkedField)
            ) {
                $baseField[$v] = $linkedField[$v];
            }
        }
    }
    
    protected function applyLinkToDb($formLinkName, $linkedTo, $db) {   
    // echo "applyLinkToDb($formLinkName, ,$db)<br>";
        for ($i = 0; $i < count($linkedTo['links']); $i++) {
            $toLinkName = $linkedTo['links'][$i];
            if ($toLinkName === '.') {
                $toLinkName = $formLinkName;
            }
            $toFieldName = $linkedTo['names'][$i];
            $link = $this->links[$toLinkName];
            $linkedField = $this->getToField($toLinkName, $toFieldName);
            if(!array_key_exists('db', $linkedField)) {
                return null;
            }
            if (array_key_exists('linkedTo', $linkedField)) {
                // if is not the same field
                if ($formLinkName !== $toLinkName || $db !== $linkedField['db']) {
                    $dbLinked = $this->applyLinkToDb(
                        $toLinkName,
                        $linkedField['linkedTo'],
                        $linkedField['db']
                    );
                    $db = str_replace($linkedTo['matches'][$i], $dbLinked, $db);
                } else {
                    $db = str_replace(
                        $linkedTo['matches'][$i], 
                        $link['toAlias'] . '.' . $linkedField['db'],
                        $db
                    );  
                }
            } else {
                $db = str_replace(
                    $linkedTo['matches'][$i], 
                    $link['toAlias'] . '.' . $linkedField['db'],
                    $db
                );  
            }
        }
        return $db;
    }
    
    protected function getToField($linkName, $toFieldName)
    {
    // echo "getToField($linkName, $toFieldName)<br>";
        $link = $this->links[$linkName];
        if (is_numeric($toFieldName)) {
            if (($toFieldName+0) >= count($link['gridColNames'])) {
                throw new Exception(
                    "{$this->reason}: Linked field \"{$linkName}[{$toFieldName}]\" uses a link with a index out of range on grid \"{$link['gridName']}\" on grid of table \"{$link['toTable']}."
                );
            }
            $toField = $link['grid']['columns'][$link['gridColNames'][$toFieldName+0]];
        } elseif (array_key_exists($toFieldName, $link['grid']['columns'])) {
            $toField = $link['grid']['columns'][$toFieldName];
        } else {
            if (!array_key_exists($toFieldName, $link['fields'])) {
                throw new Exception(
                    "{$this->reason}: Linked field \"{$linkName}[{$toFieldName}]\" not exist on `fields` on model of table \"{$link['toTable']}\"."
                );
            }
            $toField = $link['fields'][$toFieldName];
        }
        $linkedTo = $this->parseLinkedTo($linkName, $toField);
        if ($linkedTo) {
            $toField['linkedTo'] = $linkedTo;
        }
        return $toField;
    }
}
    