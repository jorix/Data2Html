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
        switch (dataType) {
            case 'date':
                return moment(val, 'L LT').format();
            default:
                return val;
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
        }
    };
})(jQuery);
