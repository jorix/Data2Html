// Static
var d2h_messages = (function ($) {
    var _options = {
        messageTime: 3000
    };
    var _timers = [];
    
    var msg = {
        _show: function(elemSelector, message, visualClass) {
            var elem;
            if (elemSelector.getElem) {
                elem = elemSelector.getElem();
            } else {
                elem = $(elemSelector);
            }
            var $msgElem = $('.d2h_message', elem),
                _timer = null;
            $msgElem.html(
                '<div class="alert alert-dismissible ' + visualClass + ' fade in">' +
                '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a><b>' +
                message +
                '</b></div>'
            ).show();
            _timers.push(setTimeout(
                function() {
                    d2h_messages.clear(elemSelector);
                },
                _options.messageTime
            ));
        },
        done: function(elem, message) {
            msg._show(elem, message, 'alert-success');
        },
        removed: function(elem, message) {
            msg._show(elem, message, 'alert-warning');
        },
        fail: function(elem, message) {
            msg._show(elem, message, 'alert-danger');
        },
        info: function(elem, message) {
            msg._show(elem, message, 'alert-info');
        },
        clear: function(elemSelector) {
            var elem;
            if (elemSelector.getElem) {
                elem = elemSelector.getElem();
            } else {
                elem = $(elemSelector);
            }
            var $msgElem = $('.d2h_message', elem);
            $msgElem.html('').hide();
            while (_timers.length > 0) {
                clearTimeout(_timers.pop());
            }
        }
    };
    return msg;
})(jQuery);
