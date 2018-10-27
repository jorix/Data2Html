// Static
var d2h_message = (function ($) {
    var _options = {
        messageTime: 0 //10000 
    };
    
    function _getElement(elemSelector) {
        if (elemSelector.getElem) { // is a server
            return elemSelector.getElem();
        } else {
            return d2h_utils.getSingleElement(elemSelector);
        }
    }
    
    function _getInfoMessage(elemSelector) { 
        var elem = _getElement(elemSelector),
            response = $.data(elem, 'Data2Html_message');
        $(elem).addClass('d2h_has_message')
        if (!response) {
            var fromId = $(elem).attr('data-d2h-from-id');
            if (fromId) {
                var elemFrom = d2h_utils.getSingleElement('#' + fromId);
                if (!$.data(elem, 'Data2Html_message')) {
                    _create(elemFrom);
                    response = $.data(elem, 'Data2Html_message');
                }
            } else {
                _create($(elem).parent());
                response = $.data(elem, 'Data2Html_message')
            }
        }
        if (!response) {
            $.error(
                "d2h_message(_create()): Element " +
                d2h_utils.getElementPath(elem) +
                " used without a 'data-d2h-message' attribute!"
            );
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
                    "d2h_message(_create()): Invalid jQuery selector \"" +
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
                infoMessage = $.data(elemMessages, 'Data2Html_message');
            if (!infoMessage) {
                infoMessage = {poppers: [], timers: []};
                // Create the infoMessage object.
                $.data(elemMessages, 'Data2Html_message', infoMessage);   
            }
            $(this).hide().html(
                    '<span></span>' +
                    '<a href="#" class="close" aria-label="' + __('message/close') + '">&times;</a>' +
                    '<div class="d2h-message-arrow" x-arrow></div>'
                )
                .addClass('d2h-message');
            $('.close', this).on('click', function() {
                _clear(elemMessages);
                return false;
            });
            pos = pos ? pos : 'top-start';
            infoMessage.poppers.push(
                new Popper(elemPopper, this, {
                    placement: pos,
                    boundariesElement: elemPopper.parentNode
                })
            );
        });
    }
    
    function _show(elemSelector, message, visualClass) {
        var infoMessage = _getInfoMessage(elemSelector);
        if (!infoMessage) {
            alert(message); // There is no element to show the message
        } else {
            var _timers = infoMessage.timers;
            // cancel previous timers
            while (_timers.length > 0) {
                clearTimeout(_timers.pop());
            }
            // show all messages for a element
            $.each(infoMessage.poppers, function() {
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
                if (_options.messageTime) {
                    _timers.push(setTimeout(
                        function() {
                            $pElem.hide();
                        },
                        _options.messageTime
                    ));
                }
            });
        }
    }
    
    function _clear(elemSelector) {
        var infoMessage = _getInfoMessage(elemSelector);
        if (infoMessage) {
            var timers = infoMessage.timers, 
                poppers = infoMessage.poppers;
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
            _show(elemSelector, message, 'd2h-message-success');
        },
        warning: function(elemSelector, message) {
            _show(elemSelector, message, 'd2h-message-warning');
        },
        danger: function(elemSelector, message) {
            _show(elemSelector, message, 'd2h-message-danger');
        },
        info: function(elemSelector, message) {
            _show(elemSelector, message, 'd2h-message-info');
        },
        clear: function(elemSelector) {
            $('.d2h_has_message', _getElement(elemSelector)).each(function() {
                _clear(this);
            });
        }
    };
})(jQuery);
