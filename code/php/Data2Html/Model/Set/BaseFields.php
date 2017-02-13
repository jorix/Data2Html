<?php
class Data2Html_Model_Set_BaseFields extends Data2Html_Model_Set 
{
    public function parseItems($fields)
    {
        $matchedFields = array();
        foreach ($fields as $k => &$v) {
            $pKey = $this->addParse($k, $v);
            $pField = $this->setItems[$pKey];
            if (isset($pField['sortBy'])) {
                $sortBy = $pField['sortBy'];
                if (is_string($sortBy)) {
                    $sortBy = array($sortBy);
                }
                $sortByNew = array();
                foreach ($sortBy as $sk => $sv) {
                    if (is_numeric($sk)) {
                        if (substr($sv, 0, 1) === '!') {
                            $sortByNew[substr($sv, 1)] = 'desc';
                        } else {
                            $sortByNew[$sv] = 'asc';
                        }
                    } else {
                        $sortByNew[$sk] = (preg_match("/php/i", 'desc') ? 'desc' : 'asc');
                    }
                }
                $pField['sortBy'] = $sortByNew;
            } elseif (!array_key_exists('sortBy', $pField) && isset($pField['db'])) {
                $pField['sortBy'] = array($k => 'asc');
            } else { // is set to null
                unset($pField['sortBy']);
            }
            foreach ($pField as $nv) {
                if (isset($nv['teplateItems'])) {
                    $matchedFields = array_merge(
                        $matchedFields,
                        $nv['teplateItems'][1]
                    );
                }
            }
            $this->setItems[$pKey] = $pField;
        }
        $pFields = $this->getItems();
        if (count($matchedFields) > 0) {
            foreach ($matchedFields as $v) {
                if (!isset($pFields[$v])) {
                    throw new Exception(
                        "{$this->culprit}: Match `\$\${{$v}}` not exist on `fields`."
                    );
                }
            }
        }
        return $this->setItems;
    }
}
