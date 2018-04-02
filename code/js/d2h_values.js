// Static
moment.locale('ca');

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
    var _toValue = function(val, dataType) {
        if (typeof val !== 'string') {
            throw "?????????????????";
        } else if (val.trim() === '') {
            return null;
        }
        switch (dataType) {
            case 'date':
                var date = moment(val, 'L LT', true);
                if (!date.isValid()) {
                    throw "tipus no ??????";
                }
                return date.format();
            default:
                return val;
        }
    };
    
    var _validateString = function(val, visual) {
        if (!visual) {
            return null;
        }
        if (typeof val !== 'string') {
            val = val.toString();
        }
        val = val.trim();
        var messages = [];
        
        // required
        if (val === '') {
            if (visual.required) {
                messages.push(__('validate/required'));
                return messages;
            } else {
                return null;
            }
        }
        
        // type match
        if (visual.type) {
            switch (visual.type) {
                case 'date':
                case 'boolean':
                case 'date':
                    var date = moment(val, 'L LT', true);
                    if (!date.isValid()) {
                        throw "tipus no ??????";
                    }
                    return date.format();
                case 'float':
                case 'integer':
                    if(!/^-?\d+\.?0*$/.test(val)) {
                        messages.push(__('validate/not-integer'));
                        return messages;
                    }
                    val = parseInt(val, 10);
                case 'number':
                case 'string':
                case 'text':
            }
        }
        if (messages.length) {
            return messages;
        } else {
            return null;
        }
    };
    
    // Public static methods
    return {
        put: function($elem, val, dataType) {
            var elemType = $elem.attr('type');
            if (elemType && elemType === 'checkbox') {
                return $elem.prop('checked', val);
            } else {
                return $elem.val(this.toHtml(val, dataType));
            }
        },
        
        toHtml: function(val, dataType) {
            switch (dataType) {
                case 'date':
                    if (val === null || val === '[now]') {
                        return '';
                    }
                    return moment(val).format('L LT');
                default:
                    return val;
            }
        },
        
        getData: function(server, visualData) {
            var _data = {};
            if (visualData) {
                var iName;
                for (iName in visualData) {
                    var visualEle = visualData[iName];
                    _data[iName] = _get(
                        server.$('[name=' + iName + ']'),
                        visualEle ? visualEle.type : null
                    );
                }
            } else {
                server.$('[name]').each(function() {
                    _data[this.name] = _get($(this), null);
                });
            }
            return _data;
        },
        
        validateData: function(server, visualData) {
            var _data = {},
                _errors = {};
            if (visualData) {
                var iName;
                for (iName in visualData) {
                    var visualEle = visualData[iName];
                    _data[iName] = _get(
                        server.$('[name=' + iName + ']'),
                        visualEle ? visualEle.type : null
                    );
                }
            } else {
                server.$('[name]').each(function() {
                    _data[this.name] = _get($(this), null);
                });
            }
            return [_data, _errors];
        },
        
        validate: _validateString
    };
})(jQuery);
