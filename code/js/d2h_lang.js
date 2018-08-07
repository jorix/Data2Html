var __ = (function($) {
    var _literals = {};
    var _lang = null;
    var _langFn = function(key) {
        if (Array.isArray(key)) {
            key = key.join('/');
        }
        
        if (_literals[key]) {
            return literals[key];
        } else {
            return '??{' + key + '}';
        }
        _lang = _literals.lang;
    };
    _langFn.load = function(literals) {
        if (_lang && _lang !== literals.lang) {
            $.error('[d2h_lang].load(): Literals that will be loaded are of a language different from those already loaded "' + literals.lang + "!=" + _lang + '".');
        }
        $.extend(_literals, literals);
        _lang = literals.lang;
    }
    return _langFn;
})(jQuery);
