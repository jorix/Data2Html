<?php
namespace Data2Html\Model\Join;

use Data2Html\Handler;
use Data2Html\Model\Set;
use Data2Html\Data\Lot;
use Data2Html\Config;
use Data2Html\Controller\SqlEdit;

class LinkedSet
{
    use \Data2Html\Debug;
   
    // Internal use
    private $linkName;
    private $linker;
    private $set;
    
    public function __construct(Linker $linker, Set $set, $linkName = '')
    {
        if (!$linkName) {
            $linkName = 'main';
        } 
        $this->linker = $linker;
        $this->set = $set;
        $this->linkName = $linkName;
        $linker->linkUp($linkName, $set);
    }

    public function __debugInfo()
    {
        return [
            'linkName' => $this->linkName,
            'set-info' => $this->set->__debugInfo()['set-info'],
            'attributes' => $this->set->__debugInfo()['attributes'],
            'links' => $this->getLinkedFrom(),
            'keys' => $this->getLinkedKeys(),
            'setItems' => $this->getLinkedItems(),
            'linker' => $this->linker->__debugInfo()
        ];
    }
    // -----------------------
    // Methods from set
    // -----------------------
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
        return $this->linker->getItems($this->linkName);
    }

    public function getLinkedKeys()
    {
        return $this->linker->getKeys();
    }
    
    public function searchLinkOfBranch($branchModelName)
    {
        $items = $this->getLinkedItems();
        foreach ($items as $k => $v) {
            if (array_key_exists('link', $v)) {
                if (Handler::parseLinkText($v['link'])['model'] === $branchModelName) {
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
