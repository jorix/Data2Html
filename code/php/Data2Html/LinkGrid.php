<?php
class Data2Html_LinkGrid
{
    protected $debug = false;
    protected $data;
    protected $gridName;
    protected $gridBase;
    protected $links;
    public function __construct($data)
    {
        $this->data = $data;
        $this->debug = $data->debug;
    }
    public function getName()
    {
        return $this->name;
    }
    public function getGrid($gridName)
    {
        $this->gridName = $gridName;
        $this->gridBase = $this->data->getGrid($gridName);
        $this->name = "Linking grid \"{$this->gridName}\" from {$this->data->getName()}";
        $this->links = array();
        $this->joins = array();
        
        $fields = $this->data->getColDs();
        
        foreach ($this->gridBase['columns'] as $k => $v) {
            if(array_key_exists('linkedTo', $v)) {
                $this->searchForLinks('.', $v['linkedTo'], $fields);
            }
        }
        if (array_key_exists('filter', $this->gridBase)) {
            foreach ($this->gridBase['filter']['fields'] as $k => $v) {
                if(array_key_exists('linkedTo', $v)) {
                    $this->searchForLinks('.', $v['linkedTo'], $fields);
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
        } else {
            $this->gridBase['joins'] = array( '.' => array(
                'fromTable' => null,
                'fromAlias' => null,
                'fromDbKeys' => null,
                'toTable' => $this->data->getTable(),
                'toAlias' => '',
                'toDbKeys' => ''
            ));
        }
        return $this->gridBase;
    }
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
    
    protected function createLink($fromLinkName, $linkName, $fieldName, $fields)
    {
        if ($linkName !== '.') {
            if (!array_key_exists($linkName, $fields)) { 
                throw new Exception(
                    "{$this->name}: Linked field \"{$linkName}[{$fieldName}]\" uses a link \"{$linkName}\" that not exist on `fields`"
                );
            }
            $anchorField = $fields[$linkName];
            if(array_key_exists('linkedTo', $anchorField)) {
                $this->searchForLinks($linkName, $anchorField['linkedTo'], $fields);
            }
            
            if (!array_key_exists('link', $anchorField)) {
                throw new Exception(
                    "{$this->name}: Linked field \"{$linkName}[{$fieldName}]\" uses field \"{$linkName}\" without link."
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
                $linkName,
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
                "{$this->name}: Requires a primary key with only one field, on link \"{$toLinkName}[...]\"."
            );
        }
        $toAlias = 'T' . count($this->joins);
        $toFields = $dataLink->getColDs();
        $this->links[$toLinkName] = array(
            'model' => $modelName,
            'toAlias' => $toAlias,
            'tableKey' => $linkedKeys[0],
            'fields' => $toFields,
            'gridName' => $gridLink['name'],
            'grid' => $gridLink,
            'gridColNames' => array_keys($gridLink['columns'])
        );
        if (count($linkedKeys) === 0) {
            throw new Exception(
                "{$this->name}: On link \"{$toLinkName}\"" .
                " for table \"{$gridLink['table']}\"" .
                " grid without keys."
            );
        }
        $keyToField = $gridLink['columns'][$linkedKeys[0]]; //$toFields[$linkedKeys[0]];
        $this->applyLinkField($toLinkName, $keyToField);
        if ($fromLinkName) {
            $fromJoin = $this->joins[$fromLinkName];
            $this->applyLinkField($fromLinkName, $anchorField);
        }
        $this->joins[$toLinkName] = array(
            'fromTable' => $fromLinkName ? $fromJoin['toTable'] : null,
            'fromAlias' => $fromLinkName ? $fromJoin['toAlias'] : null,
            'fromDbKeys' => $fromLinkName ? $anchorField['db'] : null,
            'toTable' => $dataLink->getTable(),
            'toAlias' => $toAlias,
            'toKeyFieldName' => $linkedKeys[0], //$gridLink['columns'][$linkedKeys[0]]['db'],
            'toDbKeys' => $keyToField['db']
        );
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
                    //echo '<pre>';print_r($field);echo '</pre><hr>';
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
                    }
                }
            }
            if (array_key_exists('teplateItems', $field)) {
                $linked = array(
                    'matches' => array(),
                    'links' => array(),
                    'names' => array()
                );
                foreach ($field['teplateItems'][1] as $v) {
                    $matches = null;
                    // link[name|123] | .[name|123] -> link_field or self_field
                    preg_match_all($this->data->matchLinked, $v, $matches); // TODO Unify code
                    if (count($matches[0]) > 0) {
                        array_push($linked['matches'], $matches[0]);                    
                        array_push($linked['links'], $matches[1]);
                        array_push($linked['names'], $matches[2]);
                    }
                }
                if (count($linked['links']) > 0) {
                    $field['linkedTo'] = $linked;
                }
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
        $linkedName,
        $linkedTo,
        $db
    ) {   
        for ($i = 0; $i < count($linkedTo['links']); $i++) {
            $linkName = $linkedTo['links'][$i];
            if ($linkName === '.') {
                $linkName = $linkedName;
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
                    "{$this->name}: Linked field \"{$linkName}[{$toFieldName}]\" uses a link with a index out of range on grid \"{$link['gridName']}\" on  model \"{$link['model']}\"."
                );
            }
            $toField = $link['grid']['columns'][$link['gridColNames'][$toFieldName+0]];
        } elseif (array_key_exists($toFieldName, $link['grid']['columns'])) {
            $toField = $link['grid']['columns'][$toFieldName];
        } else {
            if (!array_key_exists($toFieldName, $link['fields'])) {
                throw new Exception(
                    "{$this->name}: Linked field \"{$linkName}[{$toFieldName}]\" not exist on `fields` of model \"{$link['model']}\"."
                );
            }
            $toField = $link['fields'][$toFieldName];
        }
        return $toField;
    }
}
    