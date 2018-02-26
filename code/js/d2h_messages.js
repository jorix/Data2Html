// Static
var d2h_messages = (function ($) {
    var _options = {
        messageTime: 2000
        
    };
    var msg = {
        _show: function(elem, message, visualClass) {
            // if (typeof serverObj === 'string' ) {
        // elemSelector = serverObj;
            var $elem = $(elem);
            $elem.html(
                '<div class="alert alert-dismissible fade in">' +
                '<a href="#" class="close" data-dismiss="alert" aria-label="close">&times;</a>' +
                message +
                '</div>'
            ).addClass(visualClass).show();
            setTimeout(
                function() {
                    $elem.hide().removeClass(visualClass);
                },
                _options.messageTime
            );
        },
        done: function(elem, message) {
            msg._show(elem, message, 'alert-success');
        },
        fail: function(elem, message) {
            msg._show(elem, message, 'alert-danger');
        },
        info: function(elem, message) {
            msg._show(elem, message, 'alert-info');
        }
    };
    return msg;
})(jQuery);
