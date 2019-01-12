<?php
namespace Data2Html\Model\Set;

use Data2Html\DebugException;

class Includes extends \Data2Html\Model\Set
{
    public function __construct($setName, $defs, $itemsName)
    {
        if (!is_string($itemsName) || !array_key_exists($itemsName, $defs)) {
            throw new DebugException(
                "'itemsName' argument must be a string and exist on 'defs'.", [
                'itemsName' => $itemsName,
                'defs' => $defs
            ]);
        }
        parent::__construct(
            $setName . '_' . str_replace($itemsName, '-', '_'),
            ['items' => $defs[$itemsName]]
        );
    }
}
