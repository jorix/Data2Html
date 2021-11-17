var d2h_input = (function ($) {
    return {
        get: function(elem) {
            var $elem = $(elem);
            if ($elem.is(':checkbox')) {
                return $elem.prop('checked') ? 1 : 0;
            } else if ($elem.hasClass('selectivity-input')) {
                return $elem.selectivity('val');
            } else {
                return $elem.val();
            }
        },
        put: function(elem, value) {
            var $elem = $(elem);
            if ($elem.is(':checkbox')) {
                $elem.prop('checked', !!value);
            } else if ($elem.hasClass('selectivity-input')) {
                $elem.selectivity('val', value + '', {triggerChange: false});
                $elem.selectivity('rerenderSelection');
            } else {
                $elem.val(value);
            }
        }
    };
})( jQuery );
