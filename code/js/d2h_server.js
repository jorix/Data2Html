/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
jQuery.ajaxSetup({ cache: false });

var d2h_server = (function ($) {
    
    var _events = new d2h_events('d2h_server');
    
    /**
     * Public d2h_server
     * @parameter elemSelector
     * @parameter _options: 
     *      * object to use to start server.
     *      * false: to start server only when options are in a data-d2h attribute else returns null without error.
     *
     */
    var d2h_server = function(elemSelector, _options) {
        var $elem = $(elemSelector);
        if ($elem.length === 0) {
            $.error(
                "d2h_server: Can not find a DOM object with the selector!"
            );
        }
        if ($elem.length !== 1 && _options === false) {
            $.error(
                "d2h_server: Can not find only a DOM object with the selector when options are =false!"
            );
        }
        var _response = [];
        $elem.each(function() {
                if (elemSelector === '#d2h_1_Grid_main') {
                    console.log(".");
                }
            if (!$.data(this, "Data2Html_server") ) {
                // Create a data for "Data2Html_server"
                var optionsEle = d2h_utils.getJsData(this, 'd2h');
                if ((!optionsEle && !_options) || typeof optionsEle === 'string') {
                    if (_options === false) {
                        _response = [null];
                        return;
                    }
                    $.error(
                        "d2h_server can not initialize: attribute 'data-d2h' or js options are required for " + 
                        d2h_utils.getElementPath(this)
                    );
                }
                var opData = $.extend(optionsEle, _options);
                if ($(this).hasClass("selectivity-input")) {
                    new d2h_serverSelectivity(this, opData);
                } else if (opData.type === 'block') {
                    new d2h_serverBlock(this, null, opData);
                } else {
                    new d2h_serverGrid(this, opData);
                }
            }
            _response.push($.data(this, "Data2Html_server"));
        });
        if (_response.length === 1) {
            return _response[0];
        } else {
            return _response;
        }
    };
    
    $.extend(d2h_server, {
        on: function(selector, scope, eventName, handlerFn) {
            return _events.on(selector, scope, eventName, handlerFn);
        },
        
        trigger: function(selector, eventName, args) {
            return _events.trigger(selector, eventName, args);
        },
        
        whenCreated: function(selector, handlerFn) {
            var server = $(selector).data('Data2Html_server');
            if (server) {
                handlerFn.call(null);
            } else {
                return _events.on(selector, null, 'create', handlerFn);
            }
        }
    });
    
    return d2h_server;
})(jQuery);
