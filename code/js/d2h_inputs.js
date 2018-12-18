var d2h_inputs = (function ($) {

    // private methods
    var _getVal = function(elem) {
        var $elem = $(elem);
        if ($elem.attr('type') === 'checkbox') {
            return $elem.prop('checked') ? 1 : 0;
        } else if ($elem.hasClass('selectivity-input')) {
            return $elem.selectivity('val');
        } else {
            return $elem.val();
        }
    };

    var _putVal = function(elem, data, dataType) {
        var $elem = $(elem);
        if ($elem.attr('type') === 'checkbox') {
            $elem.prop('checked', data);
        } else if ($elem.hasClass('selectivity-input')) {
            d2h_server(elem).putValues(_toVal(data, dataType));
        } else {
            $elem.val(_toVal(data, dataType));
        }
    };
    
    var _toVal = function(val, dataType) {
        switch (dataType) {
            case 'datetime':
                if (val === null || val === 'now') {
                    return '';
                }
                return moment(val).format('L LT');
            case 'date':
                if (val === null || val === 'now') {
                    return '';
                }
                return moment(val).format('L');
            default:
                return val;
        }
    };
    
    var _validate = function(val, visualAttr) {
        if (!visualAttr) {
            return {data: val};
        }
        
        var messages = [],
            finalData;
        
        // type match and set final value
        if (typeof val === 'string') {
            val = val.trim();
        }
        if (val === '' || val === null) { // Verify a Null value
            finalData = null;
            // required
            if (visualAttr.validations && visualAttr.validations.required) {
                messages.push(__('validate/required'));
            }
        } else { // Verify a Not null value
            switch (visualAttr.type) {
                case undefined:
                    finalData = val;
                    break;
                    
                case 'boolean':
                    if(/^(true|1|-1)$/.test(val)) {
                        finalData = true;
                    } else if(/^(false|0)$/.test(val)) {
                        finalData = false;
                    } else {
                        finalData = null;
                        messages.push(__('validate/not-boolean'));
                    }
                    break;
                    
                case 'date':
                case 'datetime':
                    finalData = val;
                    var formatDate = '';
                    switch (visualAttr.type) {
                        case 'datetime':
                            formatDate = 'L LT';
                            break;
                        case 'date':
                            formatDate = 'L';
                            break;
                    }
                    var date = moment(val, formatDate, true);
                    if (!date.isValid()) {
                        finalData = null;
                        messages.push(__('validate/not-date'));
                    }
                    finalData = date.format('YYYY-MM-DD HH:mm:ss');
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
                    if (visualAttr.type !== 'integer') {
                        finalData = parseFloat(val);
                    } else {
                        // When integer only zeros as decimals are allowed
                        if(!/^[+-]?\d+\.?0*$/.test(val)&&
                           !/^[+-]?\d*\.?0+$/.test(val)) {
                            messages.push(__('validate/not-integer'));
                        }
                        finalData = parseInt(val, 10);
                    }
                    break;
                    
                case 'string':
                    finalData = val;
                    break;
                
                case 'text':
                    finalData = val;
                    break;
                    
                default:
                    throw "Type '" + visualAttr.type + "' is not supported";
            }
        }
        if (messages.length === 0) { // Other validations
        }
        
        // Make the response
        var response = {data: finalData};
        if (messages.length > 0) {
            response['errors'] = messages;
        }
        return response;
    };

    var _putKeys = function(elemBlock, keys) {
        $(elemBlock).data('d2h-keys', JSON.stringify(keys));
    };
    
    var _getVisual = function(elem) {
        var attr = d2h_utils.getJsData(elem, 'd2h-input');
        return attr ? attr : {};
    }
    
    var d2h_inputs = {
        get: function(elemBlock, action) {
            var _data = {},
                _isOk = true;
            
            // keys
            var sKeys = $(elemBlock).data('d2h-keys');
            _data['[keys]'] = (sKeys ? JSON.parse(sKeys) : '');
            
            // clear errors
            if (action) {
                d2h_message.clear(elemBlock);
            }
            
            // Get all inputs from this elemBlock.id
            $('[data-d2h-from-id="' + elemBlock.id + '"]', elemBlock).each(function() {
                var visualAttr = _getVisual(this),
                    validation = _validate(_getVal(this), visualAttr);
                _data[visualAttr.name] = validation.data;
                if (action && validation.errors) {
                    _isOk = false;
                    d2h_message.danger(this, validation.errors);
                }
            });
            
            // return false if exist validation errors
            return _isOk ? _data : false;
        },

        put: function(elemBlock, _data) {
            _putKeys(elemBlock, _data['[keys]']);
            $('[data-d2h-from-id="' + elemBlock.id + '"]', elemBlock).each(function() {
                var visualAttr = _getVisual(this),
                    tagName = visualAttr.name;
                _putVal(
                    this,
                    (_data[tagName] !== undefined ? _data[tagName] : ''),
                    visualAttr.type
                );
            });
        }, 

        clear: function(elemBlock, onlyWithDefault) {
            _putKeys(elemBlock, '');
            $('[data-d2h-from-id="' + elemBlock.id + '"]', elemBlock).each(function() {
                var visualAttr = _getVisual(this);
                if (visualAttr['default'] !== undefined) {
                    _putVal(this, visualAttr['default'], visualAttr.type);
                } else if (!onlyWithDefault) {
                    _putVal(this, '', visualAttr.type);
                }
            });
        }         
    };
    
    return d2h_inputs;
})( jQuery );
