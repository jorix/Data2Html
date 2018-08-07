<?php
namespace Data2Html\Data;

class DateTime extends \DateTime implements \JsonSerializable
{
    public function jsonSerialize()
    {
        // For now ignores time zone.
        // NOTE: Formats 'Y-m-d\TH:i:sP' or 'c' force js to work with time zone!
        return $this->format('Y-m-d\TH:i:s'); 
    }
}  
