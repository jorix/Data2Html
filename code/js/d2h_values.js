// Static
moment.locale('ca');
d2h_values = {
    get: function($elem, dataType) {
        var elemType = $elem.attr('type');
        if (elemType && elemType === 'checkbox') {
            return $elem.prop('checked') ? 1 : 0;
        } else {
            return d2h_values.toValue($elem.val(), dataType);
        }
    },
    put: function($elem, val, dataType) {
        var elemType = $elem.attr('type');
        if (elemType && elemType === 'checkbox') {
            return $elem.prop('checked', val);
        } else {
            return $elem.val(d2h_values.toHtml(val, dataType));
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
    toValue: function(val, dataType) {
        switch (dataType) {
            case 'date':
                return moment(val, 'L LT').format();
            default:
                return val;
        }
    },
    serialize: function($elem) {
        var vals = $elem.serialize(),
            checkOff = $('input[type=checkbox]:not(:checked)', $elem).map(
                function() {
                    return this.name + '=0';
                }
            ).get();
        if (checkOff.length) {
            vals = vals + (vals ? '&' : '') + checkOff.join('&');
        }
        return vals;
    },
    
    getSerialized: function(server, visualData) {
        $.param(this.getData(server, visualData));
    },
    
    getData: function(server, visualData) {
        var _data = {};
        if (visualData) {
            var iName;
            for (iName in visualData) {
                var visualEle = visualData[iName];
                _data[iName] = d2h_values.get(
                    server.$('[name=' + iName + ']'),
                    visualEle ? visualEle.type : null
                );
            }
        } else {
            server.$('[name]').each(function() {
                _data[this.name] = d2h_values.get(this, null);
            });
        }
        return _data;
    }
};
