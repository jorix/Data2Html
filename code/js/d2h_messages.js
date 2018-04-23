// Static
var d2h_messages = (function ($) {
    var _options = {
        messageTime: 10000 
    };
    
    function _getElement(elemSelector) {
        if (elemSelector.getElem) { // is a server
            return elemSelector.getElem();
        } else {
            return d2h_utils.getSingleElement(elemSelector);
        }
    }
    
    function _getInfoMessages(elemSelector) { 
        var elem = _getElement(elemSelector),
            response = $.data(elem, 'Data2Html_messages');
        if (!response) {
            var fromId = $(elem).attr('data-d2h-from-id');
            if (fromId) {
                var elemFrom = d2h_utils.getSingleElement('#' + fromId);
                if (!$.data(elem, 'Data2Html_messages')) {
                    _create(elemFrom);
                    response = $.data(elem, 'Data2Html_messages');
                }
            } else {
                response = _create(elem);
                if (false && response) {
                    $.error(
                        "d2h_messages(_create()): Element " +
                        d2h_utils.getElementPath(elem) +
                        " is used without a 'data-d2h-messages' attribute!"
                    );
                }
            }
        }
        return response;
    }
    
    function _create(elemFrom) {
        // Create all data for sub-elements or self with messages 
        $('[data-d2h-message]', elemFrom).each(function() {
            var options = d2h_utils.getJsData(this, 'd2h-message');
            if (!options) {
                return; // EXIT
            }
            
            var selectorRef, pos;
            if (typeof options === 'string') {
                selectorRef = options;
            } else {
                selectorRef = options['for'];
                pos = options.position;
            }
            if (!selectorRef || typeof selectorRef !== 'string') {
                return; // EXIT
            }
            
            selectorRef = selectorRef.trim();
            var $elemRef;
            try {
                $elemRef = $(selectorRef);
            } catch(e) {
                $.error(
                    "d2h_messages(_create()): Invalid jQuery selector \"" +
                    selectorRef +
                    "\""
                );
            }
            if ($elemRef.length === 0)  {
                return; // EXIT
            }
            
            // Get element container, e.g.: 
            //      '#myId div' show messages refereed to first div on #myId and
            //      stores info messages on #myDiv
            var elemMessages = d2h_utils.getSingleElement(selectorRef.split(' ')[0]),
                infoMessages = $.data(elemMessages, 'Data2Html_messages');
            if (!infoMessages) {
                infoMessages = {poppers: [], timers: []};
                // Create the infoMessages object.
                $.data(elemMessages, 'Data2Html_messages', infoMessages);   
            }
            $(this).hide().html(
                    '<span></span>' +
                    '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' +
                    '<div class="popper__arrow" x-arrow></div>'
                )
                .addClass('popper alert alert-dismissible');
            pos = pos ? pos : 'top-start';
            infoMessages.poppers.push(
                new Popper($elemRef[0], this, {
                    placement: pos,
                    boundariesElement: $elemRef[0].parentNode
                })
            );
        });
        return $.data(elemFrom, 'Data2Html_messages');
    }
    
    function _show(elemSelector, message, visualClass) {
        var infoMessages = _getInfoMessages(elemSelector);
        if (infoMessages) {
            var _timers = infoMessages.timers;
            // cancel previous timers
            while (_timers.length > 0) {
                clearTimeout(_timers.pop());
            }
            // show all messages for a element
            $.each(infoMessages.poppers, function() {
                var $popper = $(this.popper),
                    curClass = $popper.attr('data-d2h-message-class');
                $popper
                    .removeClass(curClass)
                    .attr('data-d2h-message-class', visualClass)
                    .addClass(visualClass);
                if (message) {
                    $('span', $popper).text(message);
                }
                this.update();
                $popper.show();
                _timers.push(setTimeout(
                    function() {
                        $popper.hide();
                    },
                    _options.messageTime
                ));
            });
        }
    }
    
    function _hide(elemSelector) {
        var infoMessages = _getInfoMessages(elemSelector);
        if (infoMessages) {
            var timers = infoMessages.timers, 
                poppers = infoMessages.poppers;
            while (timers.length > 0) {
                clearTimeout(timers.pop());
            }
            for (var i = 0, l = poppers.length; i < l; i++) {
                var $pElem = $(poppers[i].popper),
                    curClass = $pElem.attr('data-d2h-message-class');
                $pElem.hide();
                if (curClass) {
                    $pElem.removeClass(curClass);
                }
            };
        }
    }
    
    return {
        success: function(elemSelector, message) {
            _show(elemSelector, message, 'd2h-success');
        },
        warning: function(elemSelector, message) {
            _show(elemSelector, message, 'd2h-warning');
        },
        danger: function(elemSelector, message) {
            _show(elemSelector, message, 'd2h-danger');
        },
        info: function(elemSelector, message) {
            _show(elemSelector, message, 'd2h-info');
        },
        hide: _hide,
        clear: function(elemSelector) {
            $('[data-d2h-message]', _getElement(elemSelector)).each(function() {
                _hide(this);
            });
        }
    };
})(jQuery);
