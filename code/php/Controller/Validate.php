<?php
namespace Data2Html\Controller;

use Data2Html\Lang;
use Data2Html\Data\Lot;

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
        if ($value === '' || $value === null) {
            $finalVal = null;
            // required
            if (Lot::getItem(['validations', 'required'], $visual, false)) {
                $messages[] = $this->__('validate/required');
            }
        } else {
            $finalVal = $value;
        }
            // else if ($messages.length === 0) {
                // switch ($visual.type) {
                    // case undefined:
                        // $finalVal = $value;
                        // break;
                        
                    // case 'boolean':
                        // if(/^(true|1|-1)$/.test($value)) {
                            // $finalVal = true;
                        // } else if(/^(false|0)$/.test($value)) {
                            // $finalVal = false;
                        // } else {
                            // $finalVal = null;
                            // $messages.push(__('validate/not-boolean'));
                        // }
                        // break;
                        
                    // case 'date':
                        // $finalVal = $value;
                        // break;
                         // date = moment($value, 'L LT', true);
                        // if (!date.isValid()) {
                            // throw "tipus no ??????";
                        // }
                        // $finalVal = date.format();
                        // break;
                        
                    // case 'float':
                    // case 'number':
                    // case 'integer':
                         // decSep = __('global/decimal-separator');
                        // if (decSep.length !== 1) {
                            // decSep = '.';
                        // }
                         // valList = $value.split(decSep);
                        // switch (decSep) {
                            // case ',':
                                // valList[0] = valList[0].replace(/\./g, '');
                                // break;
                            // case '.':
                                // valList[0] = valList[0].replace(/,/g, '');
                                // break;
                            // default:
                                // throw "Lang['global/decimal-separator'] = '" + decSep + "' is not valid";
                        // }
                        // $value = valList.join('.');
                        // if(!/^[+-]?\d+\.?\d*$/.test($value) &&
                           // !/^[+-]?\d*\.?\d+$/.test($value)) {
                            // $messages.push(__('validate/not-number'));
                        // }
                        // if ($visual.type !== 'integer') {
                            // $finalVal = parseFloat($value);
                        // } else {                            When integer only zeros as decimals are allowed
                            // if(!/^[+-]?\d+\.?0*$/.test($value)&&
                               // !/^[+-]?\d*\.?0+$/.test($value)) {
                                // $messages.push(__('validate/not-integer'));
                            // }
                            // $finalVal = parseInt($value, 10);
                        // }
                        // break;
                        
                    // case 'string':
                        // $finalVal = $value;
                        // break;
                    
                    // case 'text':
                        // $finalVal = $value;
                        // break;
                        
                    // default:
                        // throw "Type '" + $visual.type + "' is not supported";
                // }
            // }
            
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
                if ($iName === '[keys]') {
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
