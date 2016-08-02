<?php
class Data2Html_Parse_Link
{
    protected $debug = false;
    protected $data;
    protected $gridName;
    protected $gridBase;
    protected $links;
    protected $matchLinked = '/([a-z]\w*|.)\[([a-z]\w*|\d+)\]/';
    protected $reason;

    public function __construct($data)
    {
        $this->data = $data;
        $this->debug = $data->debug;
    }
    public function getReason()
    {
        return $this->reason;
    }
    public function getGrid($gridName)
    {
        $this->gridName = $gridName;
        $this->gridBase = $this->data->getGrid($gridName);
        $this->reason = "Linking grid \"{$this->gridName}\" of table \"{$this->gridBase['table']}\"";
        $this->links = array();
        $this->joins = array();
        
        $fields = $this->data->getColDs();
        
        foreach ($this->gridBase['columns'] as $k => &$v) {
            $linkedTo = $this->parseLinkedTo('.', $v);
            if ($linkedTo) {
                $v['linkedTo'] = $linkedTo;
                $this->searchForLinks('.', $linkedTo, $fields);
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
        if (count($this->links) > 0) {
            foreach ($this->gridBase['columns'] as $k => &$v) {
                $this->applyLinkField('.', $v);
            }
        }
        if (count($this->joins) > 0) {
            $this->gridBase['joins'] = $this->joins;
        }
        return $this->gridBase;
    }
    
        // $matchedFields = array();
        // foreach ($fields as $k => &$v) {
            // list($pKey, $pField) = $this->parseField($k, $v);
            // if (isset($pField['orderBy'])) {
                // $fSorts += array($pField['db'] => $pField['orderBy']);
            // }
            // foreach ($pField as $nv) {
                // if (isset($nv['teplateItems'])) {
                    // $matchedFields = array_merge(
                        // $matchedFields,
                        // $nv['teplateItems'][1]
                    // );
                // }
            // }
            // $this->addItem('field', $pKey, $pField, $pFields);
        // }
        // if (count($matchedFields) > 0) {
            // foreach ($matchedFields as $v) {
                // if (!isset($pFields[$v])) {
                    // throw new Exception(
                        // "{$this->reason}: Match `\$\${{$v}}` not exist on `fields`."
                    // );
                // }
            // }
        // }
    protected function searchForLinks($fromLinkName, $linkedTo, $fields)
    {
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
            $modelName = $anchorField['link'];
            $dataLink = Data2Html::createModel($modelName);
            $linkGrid = $dataLink->getGrid(
                Data2Html::getGridNameByModel($modelName)
            );
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
        $linkedTo = $this->parseLinkedTo($toLinkName, $keyToField);
        if ($linkedTo) {
            $keyToField['linkedTo'] = $linkedTo;
        }
        $this->applyLinkField($toLinkName, $keyToField);
        if ($fromLinkName) {
            $fromJoin = $this->joins[$fromLinkName];
            $this->applyLinkField($fromLinkName, $anchorField);
        }
        $this->joins[$toLinkName] = array(
            'fromLink' => $fromLinkName,
            'fromTable' => $fromLinkName ? $fromJoin['toTable'] : null,
            'fromAlias' => $fromLinkName ? $fromJoin['toAlias'] : null,
            'fromDbKeys' => $fromLinkName ? $anchorField['db'] : null,
            'toLink' => $toLinkName,
            'toTable' => $gridLink['table'],
            'toAlias' => $toAlias,
            'toKeyFieldName' => $linkedKeys[0], //$gridLink['columns'][$linkedKeys[0]]['db'],
            'toDbKeys' => $keyToField['db']
        );
        }
    
    protected function parseLinkedTo($linkName, $field)
    {
        $linkedTo = $linkedTo = array(
            'matches' => array(),
            'links' => array(),
            'names' => array()
        );
        if (isset($field['db'])) {
            $matches = null;
            // link[name|123] | .[name|123] -> link_field or self_field
            preg_match_all($this->matchLinked, $field['db'], $matches);
            for ($i = 0; $i < count($matches[0]); $i++) {
                array_push($linkedTo['matches'], $matches[0][$i]);                    
                array_push($linkedTo['links'], 
                    $matches[1][$i] === '.' ?
                    $linkName :
                    $linkName . '->' . $matches[1][$i]
                );
                array_push($linkedTo['names'], $matches[2][$i]);
            }
        } elseif (array_key_exists('teplateItems', $field)) {

            foreach ($field['teplateItems'][1] as $v) {
                $matches = null;
                // link[name|123] | .[name|123] -> link_field or self_field
                preg_match_all($this->matchLinked, $v, $matches);
                for ($i = 0; $i < count($matches[0]); $i++) {
                    array_push($linkedTo['matches'], $matches[0][$i]);                    
                    array_push($linkedTo['links'],
                        $matches[1][$i] === '.' ?
                        $linkName :
                        $linkName . '->' . $matches[1][$i]
                    );
                    array_push($linkedTo['names'], $matches[2][$i]);
                }
            }
        }
        if (count($linkedTo['links']) > 0) {
            return $linkedTo;
        } else {
            return null;
        }
    }
    
    protected function applyLinkField($linkName, &$field)
    {
        if(array_key_exists('linkedTo', $field)) {
            if(array_key_exists('db', $field)) {
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
                        // TODO chk if is yet .[] or link[]
                        for($i = 0; $i < count($field['teplateItems'][1]); $i++) {
                            $field['teplateItems'][1][$i] = 
                                $linkedTo_0 . 
                                '[' . $field['teplateItems'][1][$i] . ']';
                        }
                        $linkedTo = $this->parseLinkedTo($linkName, $field);
                        if ($linkedTo) {
                            $field['linkedTo'] = $linkedTo;
                        }
                    }
                }
            }
            if (array_key_exists('teplateItems', $field)) {
                

            }
        } elseif(isset($field['db'])) {
            $field['db'] =  $this->links[$linkName]['toAlias'] . '.' . $field['db'];
        }
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
    
    protected function applyLinkToDb(
        $formLinkName,
        $linkedTo,
        $db
    ) {   
        for ($i = 0; $i < count($linkedTo['links']); $i++) {
            $linkName = $linkedTo['links'][$i];
            if ($linkName === '.') {
                $linkName = $formLinkName;
            }
            $fieldName = $linkedTo['names'][$i];
            $link = $this->links[$linkName];
            $linkedField = $this->getToField($linkName, $fieldName);
            if(!array_key_exists('db', $linkedField)) {
                return null;
            }
            if (array_key_exists('linkedTo', $linkedField)) {
                $dbLinked = $this->applyLinkToDb(
                    $linkName,
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
        }
        return $db;
    }
    
    protected function getToField($linkName, $toFieldName)
    {
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
    