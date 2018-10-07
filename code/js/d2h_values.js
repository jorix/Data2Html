// Static
var d2h_values = (function ($) {

    // private methods
    var _get = function($elem, dataType) {
        var elemType = $elem.attr('type');
        if (elemType && elemType === 'checkbox') {
            return $elem.prop('checked') ? 1 : 0;
        } else {
            return _toValue($elem.val(), dataType);
        }
    };

    _put = function($elem, val, dataType) {
        var elemType = $elem.attr('type');
        if (elemType && elemType === 'checkbox') {
            return $elem.prop('checked', val);
        } else {
            return $elem.val(_toHtml(val, dataType));
        }
    };
    
    _toHtml = function(val, dataType) {
        switch (dataType) {
            case 'datetime':
                if (val === null || val === '[now]') {
                    return '';
                }
                return moment(val).format('L LT');
            case 'date':
                if (val === null || val === '[now]') {
                    return '';
                }
                return moment(val).format('L');
            default:
                return val; //(val === null ? '{null}' : val);
        }
    };

    var _toValue = function(val, dataType) {
        if (val === undefined || val === null) {
            return null;
        } else if (typeof val !== 'string') {
            throw "?????????????????";
        } else if (val.trim() === '') {
            return null;
        }
        switch (dataType) {
            case 'datetime':
                var date = moment(val, 'L LT', true);
                if (!date.isValid()) {
                    throw "tipus no ??????";
                }
                return date.format();
            case 'date':
                var date = moment(val, 'L', true);
                if (!date.isValid()) {
                    throw "tipus no ??????";
                }
                return date.format();
            default:
                return val;
        }
    };

    var _validateValue = function(val, visual) {

        visual = visual ? visual : {};
        var messages = [],
            finalVal;
        
        // type match and set final value
        if (typeof val === 'string') {
            val = val.trim();
        }
        if (val === '' || val === null) {
            finalVal = null;
            // required
            if (visual.validations && visual.validations.required) {
                messages.push(__('validate/required'));
            }
        } else if (messages.length === 0) {
            switch (visual.type) {
                case undefined:
                    finalVal = val;
                    break;
                    
                case 'boolean':
                    if(/^(true|1|-1)$/.test(val)) {
                        finalVal = true;
                    } else if(/^(false|0)$/.test(val)) {
                        finalVal = false;
                    } else {
                        finalVal = null;
                        messages.push(__('validate/not-boolean'));
                    }
                    break;
                    
                case 'date':
                case 'datetime':
                    finalVal = val;
                    break;
                    var date = moment(val, 'L LT', true);
                    if (!date.isValid()) {
                        throw "tipus no ??????";
                    }
                    finalVal = date.format();
                    break;
                    
                case 'float':
                case 'number':
                case 'integer':
                    var decSep = __('global/decimal-separator');
                    if (decSep.length !== 1) {
                        decSep = '.';
                    }
                    var valList = (val + '').split(decSep);
                    switch (decSep) {
                        case ',':
                            valList[0] = valList[0].replace(/\./g, '');
                            break;
                        case '.':
                            valList[0] = valList[0].replace(/,/g, '');
                            break;
                        default:
                            throw "Lang['global/decimal-separator'] = '" + decSep + "' is not valid";
                    }
                    val = valList.join('.');
                    if(!/^[+-]?\d+\.?\d*$/.test(val) &&
                       !/^[+-]?\d*\.?\d+$/.test(val)) {
                        messages.push(__('validate/not-number'));
                    }
                    if (visual.type !== 'integer') {
                        finalVal = parseFloat(val);
                    } else {
                        // When integer only zeros as decimals are allowed
                        if(!/^[+-]?\d+\.?0*$/.test(val)&&
                           !/^[+-]?\d*\.?0+$/.test(val)) {
                            messages.push(__('validate/not-integer'));
                        }
                        finalVal = parseInt(val, 10);
                    }
                    break;
                    
                case 'string':
                    finalVal = val;
                    break;
                
                case 'text':
                    finalVal = val;
                    break;
                    
                default:
                    throw "Type '" + visual.type + "' is not supported";
            }
        }
        
        // Make the response
        var response = {value: finalVal};
        if (messages.length > 0) {
            response['errors'] = messages;
        }
        return response;
    };
    
    // Public static methods
    return {
        toHtml: _toHtml,

        putData: function(server, row) {
            var tagName,
                hasData = $.isPlainObject(row),
                allElements = (!hasData && row === true),
                visualData = server.getVisual();
            if (hasData) {
                $(server.getElem()).data('d2h-keys', JSON.stringify(row['[keys]']));
            } else {
                $(server.getElem()).data('d2h-keys', '');
            }
            for (tagName in visualData) {
                var visualEle = visualData[tagName];
                if (hasData) {
                    var val = row[tagName] !== undefined ? row[tagName] : "";
                    _put(server.$('[name=' + tagName + ']'), val, visualEle.type);
                } else {
                    var val = "",
                        hasDefault = false;
                    if (visualEle['default'] !== undefined) {
                        hasDefault = true;
                        val = visualEle['default'];
                    };
                    if (allElements || hasDefault) {
                        try {
                            _put(
                                server.$('[name=' + tagName + ']'), 
                                val,
                                visualEle.type
                            );
                        } catch(e) {}
                    }
                }
            }
        },
        
        getData: function(server, visualData) {
            var _data = {},
                visualData = server.getVisual();
            if (visualData) {
                // Get inputs described in visualData
                var iName;
                for (iName in visualData) {
                    var visualEle = visualData[iName];
                    var val = _get(
                        server.$('[name=' + iName + ']'),
                        visualEle ? visualEle.type : null
                    );
                    if (val !== undefined) {
                        _data[iName] = val;
                    }
                }
            } else {
                // Get all input with name
                server.$('[name]').each(function() {
                    var val = _get($(this), null);
                    if (val !== undefined) {
                        _data[this.name] = val;
                    }
                });
            }
            return _data;
        },
                
        validateServer: function(server, bypass) {
            var inputData = d2h_values.getData(server);
            if (bypass) {
                return inputData;
            }
            var visualData = server.getVisual(),
                outputData = {},
                errors = {},
                iName;
            for (iName in inputData) {
                if (iName === '[keys]') {
                    outputData[iName] = inputData[iName];
                } else {
                    var valItem = _validateValue(inputData[iName], visualData[iName])
                    outputData[iName] = valItem['value'];
                    if (valItem['errors']) {
                        errors[iName] = valItem['errors'];
                    }
                }
            }
            if (d2h_display.showErrors(server, errors)){
                return false;
            } else {
                return outputData;
            }
        }

    };
})(jQuery);
