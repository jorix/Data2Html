// Static
var d2h_values = (function ($) {

    // private methods
    var _getDataItem = function($elem, dataType) {
        var elemType = $elem.attr('type');
        if (elemType && elemType === 'checkbox') {
            return $elem.prop('checked') ? 1 : 0;
        } else {
            return _toData($elem.val(), dataType);
        }
    };

    var _putDataItem = function($elem, val, dataType) {
        var elemType = $elem.attr('type');
        if (elemType && elemType === 'checkbox') {
            return $elem.prop('checked', val);
        } else {
            return $elem.val(_toVal(val, dataType));
        }
    };
    
    var _toHtml = function(val, dataType) {
        if (val === null) {
            return '&amp;{null}';
        } else {
            return ('' + _toVal(val, dataType))
                .split('&').join('&amp;')
                .split('"').join('&quot;')
                .split("'").join('&#39;'); //(val === null ? '{null}' : val);
        }
    };

    var _toVal = function(val, dataType) {
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
                return val;
        }
    };

    
    var _toData = function(val, dataType) {
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
        repeatHtml: function(html, visualData) {
            this.html = html;
            this.visualData = visualData;
            
            var patt = /\$\{(\[keys\]\s*\|?\s*|[\w\d]+|[\w\d]+\s*\|[\s\w\d,;:\(\)\.\|\-+'"]+)\}/g,
                repl,
                __replaces = [],
                __cols = [];
            while (repl = patt.exec(this.html)) {
                var formatIndex = repl[1].indexOf('|'),
                    name,
                    format = '';
                if (formatIndex >= 0) {
                    name = repl[1].substr(0, formatIndex -1).trim();
                    format = repl[1].substr(formatIndex +1).trim();
                } else {
                    name = repl[1];
                }
                __replaces.push({
                    repl: repl[0],
                    name: name,
                    format: format
                });
            }
            
            this.apply = function(row) {
                var xName,
                    rowKeys = null;
                if (__cols.length === 0) {
                    // initialize __cols of object
                    for (xName in row) {
                        __cols.push(xName);
                    }
                }
                var html = this.html,
                    visualData = this.visualData ;
                for (var i = 0, l = __replaces.length; i < l; i++) {
                    var replItem = __replaces[i],
                        iName = replItem.name.replace(' ', ''),
                        val;
                    if (iName === '[keys]') {
                        val = row['[keys]'];
                        if (replItem.repl.indexOf('|') > 0 && val.length === 1) {
                            // When pattern ${[keys] | } force scalar if is possible
                            rowKeys = val[0]; // (val[0] === null ? '{null}' : val[0]);
                        } else {
                            rowKeys = JSON.stringify(val);
                        }
                        html = html.replace(replItem.repl, _toHtml(rowKeys, 'string'));
                    } else {
                        var visualEle = visualData ? visualData[iName] : null,
                            dataType =  visualEle ? visualEle.type : null;
                        if ($.isNumeric(iName)) {
                            val = row[__cols[iName]];
                        } else {
                            val = row[iName];
                        }
                        html = html.replace(replItem.repl, _toHtml(val, dataType));
                    }
                }
                return html;
            };
        },

        putData: function(server, row) {
            var tagName,
                hasData = $.isPlainObject(row),
                allElements = (!hasData && row === true),
                visualData = server.getVisual();
            if (hasData) {
                server.blockKeys(row['[keys]']);
            } else {
                server.blockKeys('');
            }
            for (tagName in visualData) {
                var visualEle = visualData[tagName];
                if (hasData) {
                    var val = row[tagName] !== undefined ? row[tagName] : "";
                    _putDataItem(server.$('[name=' + tagName + ']'), val, visualEle.type);
                } else {
                    var val = "",
                        hasDefault = false;
                    if (visualEle['default'] !== undefined) {
                        hasDefault = true;
                        val = visualEle['default'];
                    };
                    if (allElements || hasDefault) {
                        try {
                            _putDataItem(
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
                    var val = _getDataItem(
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
                    var val = _getDataItem($(this), null);
                    if (val !== undefined) {
                        _data[this.name] = val;
                    }
                });
            }
            return _data;
        },
                
        validateServer: function(server, bypass) {
            var inputData = this.getData(server);
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
