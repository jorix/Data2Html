var d2h_utils = (function ($) {
    
    function _getElementPath(elem) {
        if (!elem) {
            return "`undefined or null`";
        }
        var selectorArr = [];
        if (elem.jquery) {
            if (elem.length >= 1) {
                selectorArr = [
                    'jQuery[0' + 
                    (elem.length > 1 ? ('..' + elem.length) : '') +
                    ']'
                ];
                elem = elem[0];
            } else {
                return "emty-jQuery-object";
            }
        }
        if (!elem.tagName) {
            return "no-DOM-element";
        }
        selectorArr.push(elem.tagName.toLowerCase() + (elem.id ? '#' + elem.id : ''));
        $(elem).parents().map(
            function() {
                selectorArr.push(
                    this.tagName.toLowerCase() +
                    (this.id ? '#' + this.id : '')
                );
            }
        );
        return selectorArr.reverse().join(">");
    }
    
    return {
        getSingleElement: function(selector) {
            var $elem = $(selector);
            if (!selector || $elem.length !== 1) {
                $.error(
                    "d2h_utils.singleElement(): Selector '" + selector +
                    "' has selected " + $elem.length +
                    " elements. Must select only one DOM element!"
                );
            }
            return $elem[0];
        },
        
        getJsData: function(objElem, dataName) {
            var optionsEle,
                dataD2h = $(objElem).data(dataName);
            if (dataD2h) {
                if (/^\{.*\}$/.test(dataD2h)) {
                    try {
                        optionsEle = eval('[(' + dataD2h + ')]')[0];
                    } catch(e) {
                        $.error(
                            "d2h_utils.getJsData(): " +
                            "jsData of attribute 'data-" + dataName + "' have a not valid js syntax on " + 
                            _getElementPath(objElem)
                        );
                    }
                } else {
                    optionsEle = dataD2h;
                }
            }
            return optionsEle;
        },
        
        getElementPath: _getElementPath
    };
})(jQuery);
