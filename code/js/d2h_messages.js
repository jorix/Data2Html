// Static
var d2h_messages = (function ($) {
    var _options = {
        messageTime: 5000 
    };
    
    function _getElement(elemSelector) {
        if (elemSelector.getElem) { // is a server
            return elemSelector.getElem();
        } else {
            return $(elemSelector)[0];
        }
    }
    
    function _show(elemSelector, message, visualClass) {
        var _elem = _getElement(elemSelector),
            id = _elem.id;
        if (!$.data(_elem, "Data2Html_messages")) {
            var $msgElem = $('[data-d2h-message-for=' + id + ']'),
                _poppers = [];
            $msgElem.html(
                '<span></span>' +
                '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' +
                '<div class="popper__arrow" x-arrow></div>'
            );
            $msgElem.addClass('popper alert alert-dismissible');
            $msgElem.each(function() {
                var pos = $(this).attr('data-d2h-message-pos');
                pos = pos ? pos : 'top';
                _poppers.push(
                    new Popper(_elem, this, {
                        placement: pos,
                        boundariesElement: _elem.parentNode
                    })
                );
            });
            $.data(_elem, "Data2Html_messages", {poppers: _poppers, timers: []});   
        }
        var messages = $.data(_elem, "Data2Html_messages");
        if (messages) {
            var _timers = messages.timers;
            // cancel previous timers
            while (_timers.length > 0) {
                clearTimeout(_timers.pop());
            }
            // show all messages for a element
            $.each(messages.poppers, function() {
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
        var messages = $.data(_getElement(elemSelector), "Data2Html_messages");
        if (messages) {
            var timers = messages.timers, 
                poppers = messages.poppers;
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
            $('[data-d2h-message-for]', _getElement(elemSelector)).each(function() {
                _hide(this);
            });
        }
    };
})(jQuery);
