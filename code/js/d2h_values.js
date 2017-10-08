// Static
moment.locale('ca');
d2h_values = {
    get: function($elem) {
        var type = $elem.attr('type');
        if (type && type==='checkbox') {
            return $elem.prop('checked') ? 1 : 0;
        } else {
            return $elem.val();
        }
    },
    put: function($elem, val, dataType) {
        var elemType = $elem.attr('type');
        if (elemType && elemType==='checkbox') {
            return $elem.prop('checked', val);
        } else {
            return $elem.val(d2h_values.toHtml(val, dataType));
        }
    },
    toHtml: function(val, dataType) {
        switch (dataType) {
            case 'date':
                return moment(val).format('L LT')
            default:
                return val;
        }
    },
    toValue: function(val, dataType) {
        switch (dataType) {
            case 'date':
                return moment(val).format('L LT')
            default:
                return val;
        }
    },
    serialize: function($elem) {
        var vals = $elem.serialize();
        vals = vals.concat($('input[type=checkbox]:not(:checked)', $elem).map(
                function() {
                    return {"name": this.name, "value": 0}
                }
            ).get()
        );
        return vals;
    }
};
