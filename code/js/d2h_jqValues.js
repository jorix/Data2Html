// Static
d2h_jQvalues = {
    get: function($elem) {
        var type = $elem.attr('type');
        if (type && type==='checkbox') {
            return $elem.prop('checked') ? 1 : 0;
        } else {
            return $elem.val();
        }
    },
    put: function($elem, val) {
        var type = $elem.attr('type');
        if (type && type==='checkbox') {
            return $elem.prop('checked', val);
        } else {
            return $elem.val(val);
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
