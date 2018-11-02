 // Events
    // --------------
    // We define our own system of events since we monitor events before
    // defining the object.
var d2h_events = (function($) {
    
    var d2h_events =  function (prefix) {
        this.events = {};
        this.prefix = prefix;
    }
    d2h_events.prototype = {
        on: function(selector, scope, eventName, handlerFn) {
            var elem = d2h_utils.getSingleElement(selector),
                elemId = '#' + elem.id,
                events = this.events;
            if (!elemId) {
                $.error(
                    "To use d2h_events the '" + 
                    d2h_utils.getElementPath(elem) + 
                    "' must be a id."
                );
            }
            if (!events[elemId]) {
                events[elemId] = {};
            }
            var elemEvents = events[elemId];
            if (!elemEvents[eventName]) {
                elemEvents[eventName] = 0;
            }
            elemEvents[eventName]++;
            $(elem).on(
                this.prefix + eventName,
                function() {
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
            var elem = d2h_utils.getSingleElement(selector);
            console.log(
                '#' + elem.id + ': ' + this.prefix + '[ ' +  eventName + ' ]', 
                args ? args : ''
            );
            return $(elem).triggerHandler(this.prefix + eventName, args);
        }
    };

    return d2h_events;
})(jQuery);    
    