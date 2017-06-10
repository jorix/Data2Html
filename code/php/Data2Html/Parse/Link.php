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
    protected $culprit;
    protected $idCount = 0;

    public function __construct($data)
    {
        $this->debug = Data2Html_Config::debug();
        $this->data = $data;
    }
    public function getGrid($gridName)
    {
    // echo "getGrid($gridName)<br>";
        $this->gridBase = $this->data->getGrid($gridName);
        $this->gridName = $this->gridBase['name'];
        $this->culprit = "Linking grid \"{$this->gridName}\" of model \"{$this->gridBase['modelName']}\"";
        $this->links = array();
        $this->joins = array();
        $this->full2BaseNames = array();
        $this->baseNames = array();
        
        $fields = $this->data->getBaseFields()->getItems();
        
        foreach ($this->gridBase['columns'] as $k => &$v) {
            $linkedTo = $this->parseLinkedTo('.', $v);
            if ($linkedTo) {
                $v['linkedTo'] = $linkedTo;
                $this->searchLinksTo('.', $linkedTo, $fields);
            }
            $fullName = $this->getFieldFullName('.', $k);
            if (!array_key_exists($fullName, $this->full2BaseNames)) {
                $this->addGridItem($fullName);
            }
        }
        unset($v);
        if (array_key_exists('filter', $this->gridBase)) {
            foreach ($this->gridBase['filter']['fields'] as $k => &$v) {
                $linkedTo = $this->parseLinkedTo('.', $v);
                if ($linkedTo) {
                    $v['linkedTo'] = $linkedTo;
                    $this->searchLinksTo('.', $linkedTo, $fields);
                }
            }
            unset($v);
        }
        foreach ($this->gridBase['columns'] as $k => &$v) {
            $this->applyLinkField('.', $v);
        }
        if (array_key_exists('filter', $this->gridBase)) {
            foreach ($this->gridBase['filter']['fields'] as $k => &$v) {
                $this->applyLinkField('.', $v);
            }
            unset($v);
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
        $this->gridBase['_linked'] = true;
        return $this->gridBase;
    }
    
    protected function searchLinksTo($fromLinkName, $linkedTo, $fields)
    {
    // echo "searchLinksTo('$fromLinkName', ...)<br>";
        for ($i = 0; $i < count($linkedTo['links']); $i++) {
            $linkName = $linkedTo['links'][$i];
            $fieldName = $linkedTo['names'][$i];
            // echo "  '{$linkName}[$fieldName]'<br>";
            if (!array_key_exists($linkName, $this->links)) { // Add new link
                if (count($this->links) === 0) {
                    $this->createLink(null, '.', null, $fields);
                }
                if ($linkName !== '.') {
                    $this->createLink($fromLinkName, $linkName, $fieldName, $fields);
                }
            }
        }
    }
    
    protected function createLink($fromLinkName, $toLinkName, $fieldName, $fields)
    {
    // echo "createLink('$fromLinkName', '$toLinkName', '$fieldName', )<br>";
        if ($toLinkName === '.') {
            $anchorField = null;
            $modelName = $this->gridBase['modelName'];
            $dataLink = $this->data;
            $linkGrid = $this->gridBase;
        } else {
            $linkParts = explode('->', $toLinkName);
            $finalLinkName = $linkParts[count($linkParts)-1];
            if (!array_key_exists($finalLinkName, $fields)) { 
                throw new Exception(
                    "{$this->culprit}: Linked field \"{$toLinkName}[{$fieldName}]\" uses a link \"{$finalLinkName}\" that not exist on `fields`"
                );
            }
            $anchorField = $fields[$finalLinkName];
            $linkedTo = $this->parseLinkedTo($fromLinkName, $anchorField);
            if ($linkedTo) {
                $anchorField['linkedTo'] = $linkedTo;
               // $this->searchLinksTo($toLinkName, $linkedTo, $fields);
            }
            if (!array_key_exists('link', $anchorField)) {
                throw new Exception(
                    "{$this->culprit}: Linked field \"{$toLinkName}[{$fieldName}]\" uses field \"{$toLinkName}\" without link."
                );
            }
            $playerNames = Data2Html_Model::linkToPlayerNames($anchorField['link']);
            $modelName = $playerNames['model'];
            if (!array_key_exists('grid', $playerNames)) {
                throw new Data2Html_Exception(
                    "{$this->culprit}: Link \"{$anchorField['link']}\" without a grid name.",
                    $anchorField
                );
            }
            $dataLink = Data2Html_Model::createModel($modelName);
            $linkGrid = $dataLink->getGrid($playerNames['grid']);
        }
        // Add the new link
        $linkedKeys = $linkGrid['keys'];
        if (count($linkedKeys) !== 1) {
            throw new Exception(
                "{$this->culprit}: Requires a primary key with only one field, on link \"{$toLinkName}[...]\"."
            );
        }
        $toAlias = 'T' . count($this->joins);
        $toFields = $dataLink->getBaseFields()->getItems();
        $this->links[$toLinkName] = array(
            'fromLink' => $fromLinkName,
            'model' => $modelName,
            'toAlias' => $toAlias,
            'toTable' => $linkGrid['table'],
            'tableKey' => $linkedKeys[0],
            'fields' => $toFields,
            'gridName' => $linkGrid['name'],
            'grid' => $linkGrid,
            'gridColNames' => array_keys($linkGrid['columns'])
        );
        if (count($linkedKeys) === 0) {
            throw new Exception(
                "{$this->culprit}: On link \"{$toLinkName}\"" .
                " for table \"{$linkGrid['table']}\"" .
                " grid without keys."
            );
        }
        $keyToField = $linkGrid['columns'][$linkedKeys[0]]; //$toFields[$linkedKeys[0]];
        if (!array_key_exists('linkedTo', $keyToField)) {
            $linkedTo = $this->parseLinkedTo($toLinkName, $keyToField);
            if ($linkedTo) {
                $keyToField['linkedTo'] = $linkedTo;
            }
        }
        if ($fromLinkName) {
            $fromJoin = $this->joins[$fromLinkName];
            $this->applyLinkField($fromLinkName, $anchorField);
        }
        $this->applyLinkField($toLinkName, $keyToField);
        $this->joins[$toLinkName] = array(
            'fromDbKeys' => $fromLinkName ? $anchorField['db'] : null,
            'toTable' => $linkGrid['table'],
            'toAlias' => $toAlias,
            'toDbKeys' => $keyToField['db']
        );
        // echo "END createLink('$fromLinkName', $toLinkName, $fieldName, )<br>";
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
    //echo "applyLinkField('$linkName', array('{$field['db']}'))<br>";
        if(array_key_exists('linkedTo', $field)) {
            if (array_key_exists('db', $field)) {
                // echo "*applyLinkToDb applyLinkField<br>";
                // TODO Refactor resistivity to remove use of 'db_init'
                if (!array_key_exists('db_init', $field)) {
                    $field['db_init'] = $field['db'];
                    $field['db'] = $this->applyLinkToDb(
                        $linkName,
                        $field['linkedTo'],
                        $field['db']
                    );
                }
                // Merge with field destination
                if (count($field['linkedTo']['links']) === 1) {
                    $linkedTo_0 = $field['linkedTo']['links'][0];
                    $fieldTo = $this->getFieldTo(
                        $linkedTo_0,
                        $field['linkedTo']['names'][0]
                    );
                    $this->mergeAttributes(
                        $field,
                        $fieldTo,
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
                        $fieldTo = $this->getFieldTo($newLinkName, $baseName);
                        $this->applyLinkField($newLinkName, $fieldTo);
                        $this->addGridItem($fullName, $fieldTo);
                    }
                }
            }
        } elseif(isset($field['db'])) {
            $field['db_init'] = $field['db'];
            $field['db'] =  $this->links[$linkName]['toAlias'] . '.' . $field['db'];
        }
    //echo "END applyLinkField('$linkName', array('{$field['db']}'))<br>";
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
                $pKey = $this->createId();
            }
            $this->gridBase['columns'][$pKey] = $field;
        }
        $this->full2BaseNames[$fullName] = $pKey;
        $this->baseNames[$pKey] = array(
            'fullName' => $fullName
        );
        return $pKey;
    }
    protected function createId() {
        $this->idCount++;
        return 'd2h_' . $this->gridName . '_Link_' . $this->idCount;
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
    //echo "applyLinkToDb('$formLinkName', ,'$db')<br>";
        for ($i = 0; $i < count($linkedTo['links']); $i++) {
            $toLinkName = $linkedTo['links'][$i];
            if ($toLinkName === '.') {
                $toLinkName = $formLinkName;
            }
            $fieldToName = $linkedTo['names'][$i];
            $link = $this->links[$toLinkName];
            $linkedField = $this->getFieldTo($toLinkName, $fieldToName);
            if(!array_key_exists('db', $linkedField)) {
                return null;
            }
            if (array_key_exists('linkedTo', $linkedField)) {
                // if is not the same field
                if ($formLinkName !== $toLinkName || $db !== $linkedField['db']) {
                    // echo "*applyLinkToDb self {$db} !== {$linkedField['db']} <br>";
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
    
    protected function getFieldTo($linkName, $fieldToName)
    {
    //echo "getFieldTo('$linkName', $fieldToName)<br>";
        $link = $this->links[$linkName];
        if (is_numeric($fieldToName)) {
            // Is a column of grid indexed by number
            if (($fieldToName+0) >= count($link['gridColNames'])) {
                throw new Exception(
                    "{$this->culprit}: Linked field \"{$linkName}[{$fieldToName}]\" uses a link with a index out of range on grid \"{$link['gridName']}\" on grid of model \"{$link['model']}."
                );
            }
            $fieldTo = $link['grid']['columns'][$link['gridColNames'][$fieldToName+0]];
        } elseif (array_key_exists($fieldToName, $link['grid']['columns'])) {
            // Is a column of grid
            $fieldTo = $link['grid']['columns'][$fieldToName];
        } else {
            // Is a field of model
            if (!array_key_exists($fieldToName, $link['fields'])) {
                throw new Exception(
                    "{$this->culprit}: Linked field \"{$linkName}[{$fieldToName}]\" not exist on `fields` on model \"{$link['model']}\"."
                );
            }
            $fieldTo = $link['fields'][$fieldToName];
        }
        $linkedTo = $this->parseLinkedTo($linkName, $fieldTo);
        if ($linkedTo) {
            $fieldTo['linkedTo'] = $linkedTo;
        }
        return $fieldTo;
    }
}
    