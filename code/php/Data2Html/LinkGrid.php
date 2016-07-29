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
        $linkedKeys = $dataLink->getKeys();
        if (count($linkedKeys) !== 1) {
            throw new Exception(
                "{$this->name}: Requires a primary key with only one field, on link \"{$toLinkName}[...]\"."
            );
        }
        $toAlias = 'T' . count($this->joins);
        $this->links[$toLinkName] = array(
            'model' => $modelName,
            'toAlias' => $toAlias,
            'tableKey' => $linkedKeys[0],
            'fields' => $dataLink->getColDs(),
            'gridName' => $gridLink['name'],
            'grid' => $gridLink,
            'gridColNames' => array_keys($gridLink['columns'])
        );
        $ff = $gridLink['columns'][$linkedKeys[0]];
        $this->applyLinkField($toLinkName, $ff);
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
            'toDbKeys' => $ff['db']
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
            } elseif(array_key_exists('value', $field)) {
                
            }
        } elseif(isset($field['db'])) {
            $field['db'] =  $this->links[$linkName]['toAlias'] . '.' . $field['db'];
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
            if (is_numeric($fieldName)) {
                if (($fieldName+0) >= count($link['gridColNames'])) {
                    throw new Exception(
                        "{$this->name}: Linked field \"{$linkName}[{$fieldName}]\" uses a link with a index out of range on grid \"{$link['gridName']}\" on  model \"{$link['model']}\"."
                    );
                }
                $linkedField = $link['grid']['columns'][$link['gridColNames'][$fieldName+0]];
            } else {
                if (!array_key_exists($fieldName, $link['fields'])) {
                    throw new Exception(
                        "{$this->name}: Linked field \"{$linkName}[{$fieldName}]\" not exist on `fields` of model \"{$link['model']}\"."
                    );
                }
                $linkedField = $link['fields'][$fieldName];
            }
            if(!array_key_exists('db', $linkedField)) {
                throw new Exception(
                    "{$this->name}: Linked field \"{$linkName}[{$fieldName}]\" not exist on `fields` of model \"{$link['model']}\"."
                );
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
}
    