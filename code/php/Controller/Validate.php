<?php
namespace Data2Html\Controller;

use Data2Html\Lang;
use Data2Html\Data\Lot;
use Data2Html\Data\Parse;

class Validate
{
    use \Data2Html\Debug;
    
    private $langObj;
    
    public function __construct($lang) {
        
        $this->langObj = new Lang($lang, ['/_lang', '/../js']);
    }

    public function __($key)
    {
        return $this->langObj->_($key);
    }
    
    public function validateValue($value, $visual)
    {
        $visual = $visual ? $visual : [];
        $messages = [];
        $finalVal = null;
            
        // type match and set final value
        if (is_string($value)) {
            $value = trim($value);
        }
        if ($value === '' || $value === null) { // Verify a Null value
            $finalVal = null;
            // required
            if (Lot::getItem(['validations', 'required'], $visual, false)) {
                $messages[] = $this->__('validate/required');
            }
        } elseif (isset($visual['type'])) { // Verify a typed Not null value
            switch ($visual['type']) {
                case 'boolean':
                    $finalVal = Parse::boolean($value);
                    if ($finalVal === null) {
                        $messages[] = $this->__('validate/not-boolean');
                    }
                    break;
                case 'date':
                case 'datetime':
                    $finalVal = Parse::date($value);
                    if ($finalVal === null) {
                        $messages[] = $this->__('validate/not-date');
                    }
                    break;
                case 'float':
                case 'number':
                    $finalVal = Parse::number($value);
                    if ($finalVal === null) {
                        $messages[] = $this->__('validate/not-number');
                    }
                    break;
                case 'integer':
                    $finalVal = Parse::integer($value);
                    if ($finalVal === null) {
                        $messages[] = $this->__('validate/not-integer');
                    }
                    break;
                default:
                    $finalVal = $value;
            }
        } else {
            $finalVal = $value;
        }
        if (count($messages) === 0) { // Other validations
        }
            
        // Make the response
        $response = ['value' => $finalVal];
        if (count($messages) > 0) {
            $response['errors'] = $messages;
        }
        return $response;
    }
    
    public function validateData($inputData, $visualData) {
        $outputData = [];
        $errors = [];
        if ($visualData) {
            $iName;
            foreach ($inputData as $iName => $v) {
                if ($iName === '_keys_') {
                    $outputData[$iName] = $v;
                } else {
                    $valItem = $this->validateValue(
                        $v,
                        Lot::getItem($iName, $visualData)
                    );
                    $outputData[$iName] = $valItem['value'];
                    if (array_key_exists('errors', $valItem)) {
                        $errors[$iName] = $valItem['errors'];
                    }
                }
            }
        }
        return ['data' => $outputData, 'user-errors' => $errors];
    }
};
