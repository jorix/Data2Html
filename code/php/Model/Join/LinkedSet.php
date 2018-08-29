<?php
namespace Data2Html\Model\Join;

use Data2Html\Handler;
use Data2Html\Model\Set;
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
        $linker->LinkUp($linkName, $set);
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
            'linker[origins]' => $this->linker->__debugInfo()['origins']
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
    
    public function getAttributeUp($attributeKeys, $default = null)
    {
        return $this->set->getAttributeUp($attributeKeys, $default);
    }
    
    public function getAttribute($attributeKeys, $default = null)
    {
        return $this->set->getAttribute($attributeKeys, $default);
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
    
    public function searchItemByLink($linkModelName)
    {
        $items = $this->getLinkedItems();
        foreach ($items as $k => $v) {
            if (array_key_exists('link', $v)) {
                if (Handler::parseLinkText($v['link'])['model'] === $linkModelName) {
                    return  $v;
                }
            }
        }
        throw new \Data2Html\DebugException(
            "Link to model '{$linkModelName}' not found in set.",
            [
                $this->__debugInfo()
            ]
        );
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
    public function dbInsert($db, &$values, &$newId)
    {
        if ($this->callbackEvent('beforeInsert', $db, $values) === false) {
            return false;
        }
        $sqlObj = new SqlEdit($db, $this);
        $db->execute($sqlObj->getInsert($values));
        $newId = $db->lastInsertId();
        
        $this->callbackEvent('afterInsert', $db, $values, $newId);
        return true;
    }
    
    public function dbUpdate($db, &$values, $keys)
    {
        if ($this->callbackEvent('beforeUpdate', $db, $values, $keys) === false) {
            return false;
        }
        $sqlObj = new SqlEdit($db, $this);
        $sqlObj->checkSingleRow($keys);
        $db->execute($sqlObj->getUpdate($values));
        
        $this->callbackEvent('afterUpdate', $db, $values, $keys);
        return true;
    }

    public function dbDelete($db, &$values, $keys)
    {
        if ($this->callbackEvent('beforeDelete', $db, $values, $keys) === false) {
            return false;
        }
        $sqlObj = new SqlEdit($db, $this);
        $sqlObj->checkSingleRow($keys);
        $db->execute($sqlObj->getDelete($values));
        
        $this->callbackEvent('afterDelete', $db, $values, $keys);
        return true;
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
