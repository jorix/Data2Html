 // Events
    // --------------
    // We define our own system of events since we monitor events before
    // defining the object.
var d2h_events = (function($) {
    
    var d2h_events =  function (prefix) {
        this.events = {};
        this.prefix = prefix;
    };
    
    d2h_events.prototype = {
        on: function(selector, scope, eventName, handlerFn) {
            var elem = d2h_utils.getSingleElement(selector),
                _elemId = '#' + elem.id,
                _prefix = this.prefix,
                events = this.events;
            if (!_elemId) {
                $.error(
                    "To use d2h_events the '" + 
                    d2h_utils.getElementPath(elem) + 
                    "' must be a id."
                );
            }
            if (!events[_elemId]) {
                events[_elemId] = {};
            }
            var elemEvents = events[_elemId];
            if (!elemEvents[eventName]) {
                elemEvents[eventName] = 0;
            }
            elemEvents[eventName]++;
            console.log(
                'listen->', 
                _elemId + ': ' + _prefix + '[ ' +  eventName + ' ]'
            );
            $(elem).on(
                _prefix  + '_' + eventName,
                function() {
                    console.log(
                        'execute->', 
                        _elemId + ': ' + _prefix + '[ ' +  eventName + ' ]', 
                        args ? args : ''
                    );
                    var args = [];
                    Array.prototype.push.apply(args, arguments);
                    args.shift();
                    return handlerFn.apply(scope, args);
                }
            );
            return scope;
        },
        
        isUsed: function(selector, eventName) {
            var elem = d2h_utils.getSingleElement(selector),
                elemId = '#' + elem.id;
            var elemEvents = this.events[elemId];
            if (!elemEvents) {
                return false;
            } else {
                return !!elemEvents[eventName];
            }
        },
            
        trigger: function(selector, eventName, args) {
            var elem = d2h_utils.getSingleElement(selector),
            elemId = '#' + elem.id;
            console.log(
                elemId + ': ' + this.prefix + '[ ' +  eventName + ' ]', 
                args ? args : ''
            );
            if (args) {
                return $(elem).triggerHandler(this.prefix + '_' + eventName, args);
            } else {
                return $(elem).triggerHandler(this.prefix + '_' + eventName);
            }
        }
    };

    return d2h_events;
})(jQuery);    
    