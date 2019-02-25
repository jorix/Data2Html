<?php
namespace Data2Html\Model\Link;

use Data2Html\Model\Set;
use Data2Html\Model\Models;
use Data2Html\Data\Lot;
use Data2Html\Config;
use Data2Html\DebugException;
use Data2Html\Controller\SqlEdit;

class LinkedSet
{
    use \Data2Html\Debug;
   
    // Internal use
    private $linker;
    private $set;
    private $items;
    private $refItems;
    
    public function __construct(Set $set, Linker $linker = null)
    {
        $this->set = $set;
        if (!$linker) {
            $this->linker = new Linker($set);
            $this->items = $this->linker->getItems();
        } else {
            $this->linker = $linker;
            $this->items = $this->linker->getItems($set);
        }
        $this->refItems = [];
        
        $items = &$this->items;
        foreach ($items as $k => &$item) {
            $this->parseItem($item);
        }
        $keys = $this->linker->getKeys();
        foreach ($keys as $k => $v) {
            $this->makeItem($this->linker->getSourceItem('T0', $k));
        }
    }

    public function __debugInfo()
    {
        return [
            'set-info' => $this->set->__debugInfo()['set-info'],
            'attributes' => $this->set->__debugInfo()['attributes'],
            'links' => $this->getLinkedFrom(),
            'keys' => $this->getLinkedKeys(),
            'items' => $this->getLinkedItems(),
            'refItems' => $this->refItems,
            'linked-sources' => $this->linker->__debugInfo()['sources']
        ];
    }
    
    // -----------------------
    // Methods from set
    // -----------------------
    public function getModelName()
    {
        return $this->set->getModelName();
    }  
    
    public function getId()
    {
        return $this->set->getId();
    }
    
    public function getTableName()
    {
        return $this->set->getTableName();
    }
    
    public function getSort()
    {
        return $this->set->getSort();
    }
    
    public function getAttributeUp($attributeKeys, $default = null, $verifyName = true)
    {
        return $this->set->getAttributeUp($attributeKeys, $default, $verifyName);
    }
    
    public function getAttribute($attributeKeys, $default = null, $verifyName = true)
    {
        return $this->set->getAttribute($attributeKeys, $default, $verifyName);
    }

    // -----------------------
    // Linked info
    // -----------------------
    public function getLinkedFrom()
    {
        return $this->linker->getFrom();
    }
    
    public function getLinkedItems()
    {
        return $this->items;
    }

    public function getLinkedKeys()
    {
        return $this->linker->getKeys();
    }
    
    public function searchLinkOfBranch($branchModelName)
    {
        $items = $this->items;
        foreach ($items as $k => $v) {
            if (array_key_exists('link', $v)) {
                if (Models::parseUrl('grid=' . $v['link'])['model'] === $branchModelName) {
                    return  $v;
                }
            }
        }
        throw new \Data2Html\DebugException(
            "Link of branch '{$branchModelName}' not found leaves items of set.", [
                'links' => $this->getLinkedFrom(),
                'items' => $items
        ]);
    }
    
    public function searchItemNameByDb($dbIntemName)
    {
        $items = $this->getLinkedItems();
        foreach ($items as $k => $v) {
            if (array_key_exists('db', $v)) {
                if ($v['db'] === $dbIntemName) {
                    return  $k;
                }
            }
        }
        return false;
    }
    
    // -----------------------
    // Parse links
    // -----------------------
    protected function parseItem(&$item) {
        $linker = $this->linker;
        
        $iniValueTableAlias = null;
        $iniSortByTableAlias = null;
        if (array_key_exists('value', $item)) {
            $iniValueTableAlias = $item['table-alias'];
        }
        if (array_key_exists('sortBy', $item)) {
            $iniSortByTableAlias = $item['table-alias'];
        }
        
        $linkedTo = $linker->parseLinkedName($item['table-alias'], Lot::getItem('base', $item));
        if ($linkedTo) {
            $iniLinkTableAlias = $item['table-alias'];
            // Linked with a item link
            if ($linkedTo['toBaseName']) {
                unset($item['table-alias']);
                unset($item['table-item']);
                $this->set->applyBaseItem(
                    $item, 
                    $linker->getSourceItem($linkedTo['toTableAlias'], $linkedTo['toBaseName'])
                );
            }
            // Linked with a item list (a item could have both link methods)
            $lkItem = $linker->getSourceItem($linkedTo['toTableAlias'], '[list]', false);
            if ($lkItem) {
                $this->set->applyBaseItem($item, $lkItem);
                $item['list-item'] = $this->makeItem(
                    $linker->getSourceItem($iniLinkTableAlias, $linkedTo['fromBaseName'])
                );
            }
            if (Config::debug()) {
                $item['debug-linkedTo'] = $linkedTo;
            }
        }
        
        // Parse 'db-items'
        if (isset($item['db-items'])) {
            foreach ($item['db-items']['items'] as $k => &$v) {
                $lkItem = $linker->getSourceItem($item['table-alias'], $k);
                if ($lkItem) {
                    $v['table-item'] = $this->makeItem(
                        $linker->getSourceItem($item['table-alias'], $k)
                    );
                }
            }
            unset($v);
        }
        
        if (isset($item['bridge']) && is_string($item['bridge'])) {
            $pComp = Models::parseUrl('grid=' . $item['bridge']);
            $bridge = Models::linkGrid($pComp['model'], $pComp['grid']);
            $bItem = $bridge->getLinkedColumns()->searchLinkOfBranch($this->set->getModelName());
            $item['bridge'] = [
                'bridge-grid' => $bridge,
                'bridge-item' => $bItem['name']
            ];
        }
        
        // Set refItems
        if (isset($item['base'])) {
            $this->refItems[$item['table-alias']][$item['base']] = $item['name'];
        } else {
            $this->refItems[$item['table-alias']][$item['name']] = $item['name'];
        }

        
        // Default attributes
        if (array_key_exists('base', $item) &&
            !array_key_exists('title', $item)
        ) {
            $item['title'] = $item['base'];
        }
        if (array_key_exists('db', $item) &&
            !array_key_exists('title', $item)
        ) {
            $item['title'] = $item['db'];
        }
        if (!array_key_exists('description', $item) &&
            array_key_exists('title', $item)
        ) {
            $item['description'] = $item['title'];
        }
        if (!array_key_exists('sortBy', $item) && isset($item['db'])) {
            $item['sortBy'] = $item['name'];
            $this->set->parseSortBy($item['sortBy']);
        }
        
        // Parse value patterns
        if (array_key_exists('value', $item)) {
            // Parse patterns as: $${name} | $${link[name]}
            $matches = null;
            preg_match_all(Linker::getPatternValueTemplate(), $item['value'], $matches);
            $tItems = [];
            if (count($matches[0]) > 0) {
                if (!array_key_exists('type', $item)) {
                    $item['type'] = 'string';
                }
                for ($i = 0; $i < count($matches[0]); $i++) {
                    $linkedTo = $linker->parseLinkedName(
                        $iniValueTableAlias ? $iniValueTableAlias : $item['table-alias'],
                        $matches[1][$i]
                    );
                    if (!$linkedTo) {
                        $valueItem = $linker->getSourceItem(
                            $item['table-alias'],
                            $matches[1][$i]
                        );
                    } else {
                        $valueItem = $linker->getSourceItem(
                            $linkedTo['toTableAlias'],
                            $linkedTo['toBaseName']
                        );
                    }
                    $valueItemName = $this->makeItem($valueItem);
                    if (!$valueItemName || preg_match('/^\w$/', $valueItemName)) {
                        throw new DebugException(
                            "Internal error",
                            [$tableAlias, $valueItemName, $valueItem]
                        );
                    }
                    $tItems[$matches[0][$i]] = [
                        'base' => $matches[1][$i],
                        'table-item' => $valueItemName
                    ];
                }
                $item['value-patterns'] = $tItems;
            }
        }
        
        if (isset($item['sortBy'])) {
            // Change key if it is a linkedTo
            $sortBy = &$item['sortBy']['items'];
            $sortByTableAlias = $iniSortByTableAlias ? 
                $iniSortByTableAlias :
                $item['table-alias'];
            foreach ($sortBy as $k => &$v) {
                $linkedTo = $linker->parseLinkedName($sortByTableAlias, $k);
                if ($linkedTo) {
                    $sortItem = $linker->getSourceItem(
                        $linkedTo['toTableAlias'],
                        $linkedTo['toBaseName']
                    );
                } else {
                    $sortItem = $linker->getSourceItem($sortByTableAlias, $k);
                }
                if (!isset($sortItem['table-item'])) {
                    throw new DebugException(
                        "Don't use a item without db in a sortBy.", [
                            'item' => $item,
                            $k => $sortItem,
                            $linkedTo
                    ]);
                }
                $v['table-item'] = $sortItem['table-item'];
            }
        }
    }
        
    protected function makeItem($item) {
        $tableAlias = $item['table-alias'];
        $baseName = Lot::getItem('base', $item);
        if (!$baseName) {
            $baseName = $item['name'];
        }
        $oldName = Lot::getItem([$tableAlias, $baseName], $this->refItems);
        if ($oldName) {
            if (array_key_exists($oldName, $this->items)) {
                return $oldName;
            }
            $newName = $oldName;
        }  else {
            if ($tableAlias === 'T0') {
                $newName = $baseName;
            } else {
                $newName = $tableAlias . '_' . $baseName;
            }   
        }
            
        $item['name'] = $newName;
        $item['_instrumental'] = true;
        $this->parseItem($item);
        $this->items[$newName] = $item;
        return $newName;
    }

    // -----------------------
    // Database management
    // -----------------------
    public function dbInsert($db, &$values)
    {
        $response = ['success' => false];
        if (Config::debug()) {
            $debugResponse = ['action' => 'dbInsert'];
        } else {
            $debugResponse = false;
        }
        if ($this->callbackEvent('beforeInsert', $db, $values) === false) {
            $response['success'] = false;
            if ($debugResponse) {
                $debugResponse['beforeInsert'] = [
                    'response' => false,
                    'values' => $values,
                ];
            }
        } else {
            $sqlObj = new SqlEdit($db, $this);
            $sql = $sqlObj->getInsert($values);
            $db->execute($sql);
            $newId = $db->lastInsertId();
            // Get new keys
            $lkItems = $this->getLinkedItems();
            $keyNames = $this->getLinkedKeys();
            $keys = [];
            foreach($keyNames as $k => $v) {
                if (Lot::getItem([$k, 'key'], $lkItems) === 'autoKey') {
                    $keys[] = $newId + 0;
                } else {
                    $keys[] = $values[$k];
                }
            }
            $response['keys'] = $keys;
            
            if ($debugResponse) {
                $debugResponse['sql'] = explode("\n", $sql);
            }

            $this->callbackEvent('afterInsert', $db, $values, $newId);
            $response['success'] = true;
        }
        if ($debugResponse) {
            $response['debug'] = $debugResponse;
        }
        return $response;
    }
    
    public function dbUpdate($db, &$values, $keys)
    {
        $response = ['success' => false];
        if (Config::debug()) {
            $debugResponse = [
                'action' => 'dbUpdate',
                'keys' => $keys
            ];
        } else {
            $debugResponse = false;
        }
        // before update
        if ($this->callbackEvent('beforeUpdate', $db, $values, $keys) === false) {
            $response['success'] = false;
            if ($debugResponse) {
                $debugResponse['beforeUpdate'] = [
                    'response' => false,
                    'values' => $values,
                ];
            }
        } else {
            $sqlObj = new SqlEdit($db, $this);
            $sqlObj->checkSingleRow($keys);
            $sql = $sqlObj->getUpdate($values);
            $db->execute($sql);
            if ($debugResponse) {
                $debugResponse['sql'] = explode("\n", $sql);
            }
            $this->callbackEvent('afterUpdate', $db, $values, $keys);
            $response['success'] = true;
        }
        
        if ($debugResponse) {
            $response['debug'] = $debugResponse;
        }
        return $response;
    }

    public function dbDelete($db, &$values, $keys)
    {
        $response = ['success' => false];
        if (Config::debug()) {
            $debugResponse = [
                'action' => 'dbDelete',
                'keys' => $keys
            ];
        } else {
            $debugResponse = false;
        }
        // before Delete
        if ($this->callbackEvent('beforeDelete', $db, $values, $keys) === false) {
            $response['success'] = false;
            if ($debugResponse) {
                $debugResponse['beforeDelete'] = [
                    'response' => false,
                    'values' => $values,
                ];
            }
        } else {
            $sqlObj = new SqlEdit($db, $this);
            $sqlObj->checkSingleRow($keys);
            $sql = $sqlObj->getDelete($values);
            $db->execute($sql);
            
            if ($debugResponse) {
                $debugResponse['sql'] = explode("\n", $sql);
            }
            // after delete
            $this->callbackEvent('afterDelete', $db, $values, $keys);
            $response['success'] = true;
        }
        
        if ($debugResponse) {
            $response['debug'] = $debugResponse;
        }
        return $response;
    }
    
    protected function callbackEvent($eventName, $db, &$values) // arguments may be 3 or 4, depends of the event
    {
        $callEvent = function ($set, $args, $response) use($eventName, $db, &$values) {
            $fn = $set->getAttribute($eventName);
            if ($fn) {
                switch (count($args)) {
                    case 3:
                        $response = $fn($set, $db, $values);
                        break;
                    case 4:
                        $response = $fn($set, $db, $values, $args[3]);
                        break;
                    default:
                        throw new \Exception(
                            "\"{$eventName}\" defined with incorrect number of arguments=" . count($args)
                        );  
                }
            }
            return $response;
        };
        $response = true;
        

        $baseSet = $this->set->getBase();
        if ($baseSet) {
            $response = $callEvent($baseSet, func_get_args(), $response);
        }
        if ($response !== false) {
            $response = $callEvent($this, func_get_args(), $response);
        }
        return $response;
    }
}
