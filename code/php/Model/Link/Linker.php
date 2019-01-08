<?php
namespace Data2Html\Model\Link;

use Data2Html\DebugException;
use Data2Html\Config;
use Data2Html\Handler;
use Data2Html\Model\Set;
use Data2Html\Data\Lot;

class Linker
{
    use \Data2Html\Debug;
    use \Data2Html\DebugStatic;

    protected $linkedAlias = [];
    protected $tableCount = 0;
    protected $tableSources = [];
    protected $sources = [];
    
    // To parse link
    protected static $patternLinked = '/(\b[a-z]\w*)\[\s*(\w*)\s*\]/i';
    protected static $patternValueTemplate = '/\$\$\{([a-z]\w*|[a-z]\w*\[([a-z]\w*|\d+)\]|[a-z][\w\-]*)\}/i';
        // template as: $${base_name} or $${link_name[field_name]} or $${tow-word}
    

    public function __construct(Set $set)
    {
        $tableAlias = $this->addTable(null, null, $set);
    }
    
    public function __debugInfo()
    {
        return [
            'getFrom()' => $this->getFrom(),
            'linkedAlias' => $this->linkedAlias,
            'sources' => $this->sources,
        ];
    }

    public function getFrom()
    {
        return $this->tableSources;
    }
    
    public function getKeys()
    {
        return $this->tableSources['T0']['table-keys'];
    }
    
    public function getItems(Set $set = null)
    {
        if (!$set) {
            return $this->sources['T0']['_items'];
        } else { 
            return self::parseLinkedItems($set, $this->sources['T0']['_base'], 'T0');
        }
    }
    
    public static function getPatternValueTemplate()
    {
        return self::$patternValueTemplate;
    }
    
    public function parseLinkedName($tableAlias, $baseName)
    {
        $matches = null;
        preg_match_all(self::$patternLinked, $baseName, $matches);
        if (count($matches[0]) === 0) {
            return null;
        }
        if (count($matches[0]) > 1) {
            throw new DebugException(
                "Defining \"{$baseName}\" with more that one link or list."
            );
        }
        
        $linkedTo = [];
        $baseItems = $this->sources[$tableAlias]['_base'];
        for ($i = 0; $i < count($matches[0]); $i++) {
            if ($matches[1][$i]) {
                $baseLink = $matches[1][$i];
                $match = $matches[0][$i];
                $toAlias = $this->prepareLinkedSource($tableAlias, $baseLink);
                if (!$toAlias) {
                    throw new DebugException(
                        "Defining \"{$baseName}\", the \"{$baseLink}\" is not a link or a list."
                    );
                }
                $linkedTo = [
                    'debug-match' => $match,
                    'debug-fromTableAlias' => $tableAlias,
                    'debug-fromBaseName' => $baseLink,
                    'toTableAlias' => $toAlias,
                    'toBaseName' => $matches[2][$i]
                ];
            }
        }
        return $linkedTo;
    }

    public function getSourceItem($tableAlias, $baseName)
    {
        // Get item
        $l = $this->sources[$tableAlias];
        $lkItem = null;
        if (array_key_exists($baseName, $l['_items'])) {
            $lkItem = $l['_items'][$baseName];
        } elseif (array_key_exists($baseName, $l['_base'])) {
            $lkItem = $l['_base'][$baseName];
        } elseif (array_key_exists('[list]', $l['_items'])) {
            $baseName = '[list]';
            $lkItem = $l['_items'][$baseName];
        }
        if (!$lkItem) {
            throw new debugException(
                "Item \"{$baseName}\" not found on 'items' and 'base'.",
                $l
            );
        }
        return $lkItem;
    }
    
    // -----------------------
    // Internal procedures
    // -----------------------
    protected static function parseLinkedItems(Set $set, $baseItems, $tableAlias)
    {
        $items = $set->getItems();
        foreach ($items as $k => &$v) {
            if (array_key_exists('base', $v)) {
                $base = $v['base'];
                if ($k !== $base && array_key_exists($base, $items)) {
                    $set->applyBaseItem($v, $items[$base]);
                } elseif ($baseItems && array_key_exists($base, $baseItems)) {
                    $set->applyBaseItem($v, $baseItems[$base]);
                }
            }
            
            if (isset($v['link'])) {
                $linkedSet = self::getColumnsSet($v['link']);
                $linkedKeys = $linkedSet->getKeys();
                if (isset($v['db-items'])) {
                    $originItems = $v['db-items'];
                } else {
                    $originItems = [$k];
                }
                if ($originItems) {
                    if (count($originItems) !== count($linkedKeys)) {
                        throw new DebugException(
                            "Linked origin db-names and destination keys has a different number of fields.", [
                            'origin set' => $set->__debugInfo()['set-info'],
                            'origin items' => $originItems,
                            'destination set' => $linkedSet->__debugInfo()['set-info'],
                            'destination keys' => $linkedKeys
                        ]);
                    }
                    $i = 0;
                    foreach ($linkedKeys as $kk => $vv) {
                        $set->applyBaseItem(
                            $items[$originItems[$i]], 
                            $linkedSet->getSetItem($kk)
                        );
                        $i++;
                    }
                }
            }
            $v['table-alias'] =  $tableAlias;
            if (isset($v['db'])) {
                $v['table-item'] = self::setDbItem($tableAlias, $v['db']);
            }
        }
        unset($v);
        return $items;
    }
    
    protected function prepareLinkedSource($fromAlias, $fromBaseName)
    {
        $toAlias = Lot::getItem([$fromAlias, $fromBaseName], $this->linkedAlias);
        if (!$toAlias) {
            $item = $this->getSourceItem($fromAlias, $fromBaseName);
            if (isset($item['link'])) {
                $toAlias = $this->addTable(
                    $fromAlias, 
                    $fromBaseName, 
                    self::getColumnsSet($item['link'])
                );
            }
            if (isset($item['list'])) {
                $toAlias = $this->addListItem($fromAlias, $fromBaseName);
            }
        }
        return $toAlias;
    }
    
    protected static function getColumnsSet($linkedWith)
    {
        $playerNames = Handler::parseLinkText($linkedWith);
        if (!array_key_exists('grid', $playerNames)) {
            throw new \Exception(
                "Link \"{$linkedWith}\" without a grid name."
            );
        }
        $modelName = $playerNames['model'];
        $model = Handler::getModel($modelName);
        return $model->getColumns($playerNames['grid']);
    }
    
    protected function addTable($fromAlias, $fromBaseName, $set) {
        $toAlias = 'T' . $this->tableCount;
        $this->tableCount++;
        
        if ($toAlias !== 'T0' && $set->getAttribute('summary', null, false)) {
            throw new DebugException(
                "Is not possible link with a summary grid", [
                    'groupName' => $groupName, 
                    'fromAlias' => $fromAlias, 
                    'fromBaseName' => $fromBaseName
            ]);
        }
        
        // Prepare
        $tableSource = [
            'origin-table-items' => null,
            'join-type' => null,
            'table' =>  $set->getTableName(),
            'table-keys' => null
        ];
        $source = [
            '_base' => self::parseLinkedItems($set->getBase(), null, $toAlias)
        ];
        $source['_items'] = self::parseLinkedItems($set, $source['_base'], $toAlias);
        
        // Set
        $this->tableSources[$toAlias] = &$tableSource;
        $this->sources[$toAlias] = &$source;
        
        // Apply db-item on keys
        $keys = $set->getKeys();
        foreach ($keys as $k => &$v) {
            $item = $this->getSourceItem($toAlias, $k);
            if (!isset($item['db'])) {
                throw new DebugException(
                    "Key \"{$k}\" of \"{$toAlias}\" without a 'db' attribute.", [
                    [$v, $keys, $tableSource]
                ]);
            }
            $v['table-item'] = self::setDbItem($toAlias, $k);
        }
        unset($v);
        $tableSource['table-keys'] = $keys;
        
        if ($toAlias !== 'T0') {
            $this->linkedAlias[$fromAlias][$fromBaseName] = $toAlias;
            // TODO: multi key on $fromBaseNames
            
            $sourceItem = $this->getSourceItem($toAlias, $fromBaseName);
            if (isset($sourceItem['db-items'])) {
                $fromBaseNames = $sourceItem['db-items'];
            } else {
                $fromBaseNames = [$fromBaseName];
            }
            
            // Get attributes from origin keys for link field
            if (count($fromBaseNames) !== count($keys)) {
                throw new DebugException(
                    "Linked origin db-names and destination keys has a different number of fields.", [
                    'from' => [$fromAlias, $fromBaseName],
                    'Destination keys' => $keys,
                    'tableSource' => $tableSource,
                    'debugInfo' => $this->__debugInfo()
                ]);
            }
            $i = 0;
            $areRequired = true;
            $formFinalDb = [];
            foreach (array_keys($keys) as $k) {
                $item = $this->getSourceItem($fromAlias, $fromBaseNames[$i]);
                $formFinalDb[] = Lot::getItem('table-item', $item);
                $areRequired = $areRequired &&
                    Lot::getItem(['validations', 'required'], $item, false);
                unset($item);
                $i++;
            }
            $tableSource['origin-table-items'] = $formFinalDb;
            $tableSource['join-type'] = ($areRequired ? 'inner' : 'left');
        }
        return $toAlias;
    }
    
    protected function addListItem($fromAlias, $fromBaseName) {
        $toAlias = Lot::getItem([$fromAlias, $fromBaseName], $this->linkedAlias);
        $isNew = false;
        if ($toAlias) {
            // Alias already exist e.g. by addTable
            $tableSource = &$this->tableSources[$toAlias];
            $source = &$this->sources[$toAlias];
        } else {
            // Create new alias
            $isNew = true;
            $toAlias = 'T' . $this->tableCount;
            $this->tableCount++;
            
            $tableSource = [
                'origin-dbItems'=> null,
                'table' =>  null,
                'keys' => null
            ];
            $source = [
                '_items' => [],
                '_base' => []
            ];
            $this->tableSources[$toAlias] = &$tableSource;
            $this->sources[$toAlias] = &$source;
            $this->linkedAlias[$fromAlias][$fromBaseName] = $toAlias;
        }
        return $toAlias;
        // Add origin list as item
        $finalBaseName =
            $this->makeInstrumentalItemByBase($groupName, $fromAlias, $fromBaseName);
        $tableSource['from-list'] = $finalBaseName;
        $origin = $this->items[$groupName][$finalBaseName];
        if ($isNew) {
            $listItem = [
                'table-alias' => $toAlias,
                'type' => 'string'
            ];
            if (array_key_exists('title', $origin)) {
                $listItem['title'] = $origin['title'];
            }
            if (array_key_exists('description', $origin)) {
                $listItem['description'] = $origin['description'];
            }
            $source['_items']['[list]'] = $listItem;
        }
        
        return $toAlias;
    }
    
    protected static function setDbItem($tableAliasDb, $db) {
        if (preg_match('/^\w+$/', $db)) { // is a name
            return $tableAliasDb  . '.' . $db;
        } else {
            return preg_replace_callback(
                '/(\b[a-z]\w*\b\s*(?![\(]))/i', // TODO: funtionName + space + ( eg: '1000 + id + sin (e)'
                function ($matches) use ($tableAliasDb) {
                    return $tableAliasDb . '.' . $matches[0];
                },
                $db
            );
        }
    }
}
