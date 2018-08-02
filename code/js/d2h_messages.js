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
            $(elem).addClass('d2h_has_messages')
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
            var elemPopper = $elemRef[0];
            
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
                    '<a href="#" class="close" aria-label="' + __('messages/close') + '">&times;</a>' +
                    '<div class="popper__arrow" x-arrow></div>'
                )
                .addClass('popper alert alert-dismissible');
            $('.close', this).on('click', function() {
                _clear(elemMessages);
            });
            pos = pos ? pos : 'top-start';
            infoMessages.poppers.push(
                new Popper(elemPopper, this, {
                    placement: pos,
                    boundariesElement: elemPopper.parentNode
                })
            );
        });
        return $.data(elemFrom, 'Data2Html_messages');
    }
    
    function _show(elemSelector, message, visualClass) {
        var infoMessages = _getInfoMessages(elemSelector);
        if (!infoMessages) {
            alert(message); // There is no element to show the message
        } else {
            var _timers = infoMessages.timers;
            // cancel previous timers
            while (_timers.length > 0) {
                clearTimeout(_timers.pop());
            }
            // show all messages for a element
            $.each(infoMessages.poppers, function() {
                var $pElem = $(this.popper),
                    curClass = $pElem.attr('data-d2h-message-class');
                $pElem
                    .removeClass(curClass)
                    .attr('data-d2h-message-class', visualClass)
                    .addClass(visualClass);
                if (message) {
                    $('span', $pElem).text(message);
                }
                this.update();
                $pElem.show();
                _timers.push(setTimeout(
                    function() {
                        $pElem.hide();
                    },
                    _options.messageTime
                ));
            });
        }
    }
    
    function _clear(elemSelector) {
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
                $('span', $pElem).text(''); // clear message text
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
        clear: function(elemSelector) {
            $('.d2h_has_messages', _getElement(elemSelector)).each(function() {
                _clear(this);
            });
        }
    };
})(jQuery);
