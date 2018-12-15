/**
 * 
 */
var d2h_serverBase = (function ($) {

    var _initCounter = 0,
        _classWaiting = 'd2h_waiting';

    function waitHandler() {
    }
    waitHandler.prototype = {
        _waitCounter: 0, // Only one wait
        waitClassUsed: '',
        hide: function() {
            if (this.waitClassUsed) {
                this._waitCounter--;
                if (this._waitCounter <=0) {
                    this._waitCounter = 0;
                    $('.' + this.waitClassUsed).hide();
                    this.waitClassUsed = '';
                }
            }   
        },
        show: function() { 
            if (_classWaiting) {
                if (this._waitCounter === 0) {
                    this.waitClassUsed = _classWaiting;
                    $('.' + this.waitClassUsed).show();
                }
                this._waitCounter++;
            }
        }
    };
    var _wait = new waitHandler();

    // ----------------
    // Data handler
    // ----------------

    // Base class
    function d2h_serverBase(objElem, container, options) {
        this._init(objElem, container, options);
    }
    d2h_serverBase.prototype = {
        events: [],
        defaults: {
            url: '',
            ajaxType: 'GET',
            singleRequest: false, // If is single request is immediate load and return a $.ajax
            auto: null
        },
        
        settings: null,
        status: null,
        promise: null,
        objElem: null, // The DOM element
        _container: null,  
        _loadCount: 0,       
        _initId: 0,
        
        _visualData: null,
        _rows: null, //the data once loaded/received
        
        _init: function(objElem, container, options) {
            this.defaults =
                $.extend({}, d2h_serverBase.prototype.defaults, this.defaults);
            this.objElem = objElem;
            this.status = {};
            this._initId = _initCounter++;
            this._container = container ? container : this;
            
            // settings
            var settings = $.extend({}, this.defaults, options); 
            this.settings = settings;

            // 
            if (settings.visual) {
                this._visualData = settings.visual;
                delete settings.visual;
            } else {
                this._visualData = d2h_utils.getJsData(objElem, 'd2h-visual');
            }
            
            // Add pluguin
            $.data(this.objElem, d2h_server.COMPONENT_NAME, this);
 
        },
        
        _initEnd: function(promises) {
            // aditional calls
            this.whenPromise(promises, function() {

                // Issue of default value of a select:
                //  * clearGrid after filter promises are done
                this.clear();

                // Register events
                var settings = this.settings;
                if (settings.actions) {
                    this.listen(this.objElem, settings.actions);
                }
                for( var i = 0, l = this.events.length; i < l; i++) {
                    var evName = this.events[i];
                    if (settings[evName]) {
                        this.on(evName, settings[evName]);
                    }
                }
                
                // Auto call
                var autoCall = this.settings.auto;
                if (autoCall) {
                    if (!this[autoCall]) {
                        $.error(
                            "Not exist method '" + autoCall + 
                            "' on object '" + this.constructor.name + "' to use as autoCall."
                        );
                    }
                    this[autoCall].call(this, this.settings);
                }
                this.trigger('created', [this.constructor.name]);  
            });
        },
        
        set: function(options) {
            $.extend(this.settings, options);
            return this;
        },
        
        get: function() {
            return this;
        },
        
        getElem: function() {
            return this.objElem;
        },
        
        $: function(selector, elem) {
            return $(selector, (elem ? elem : this.objElem))
                .filter('[data-d2h-from-id=' + this.objElem.id + ']');
        },
        
        on: function(eventName, handlerFn) {
            return d2h_server.on(this.objElem, this, eventName, handlerFn);
        },
        
        trigger: function(eventName, args) {
            return d2h_server.trigger(this.objElem, eventName, args);
        },
        
        getPromise: function() {
            if (this.promise && this.promise.state() === 'pending') {
                return this.promise;
            } else {
                this.promise = null;
                return; // return undefined
            }
        },
        
        then: function(doneFn) {
            var _this = this;
            this.promise = $.when(this.promise).then(function() {
                doneFn.call(_this);
            });
        },
        
        whenPromise: function(promises, doneFn) {
            if (promises) {
                if (!$.isArray(promises)) { // cast to array
                    promises = [promises];
                }
                var _this = this;
                this.promise = $.when.apply($, promises).done(function() {
                    doneFn.call(_this);
                });
            } else {
                doneFn.call(this);
            }
        },
        
        // Listen actions
        listen: function(handlerEle, _actions) {
            var _container = this._container,
                _objElemId = this.objElem.id;
            // Scope handlerEle
            var _fnActionOn = function(_onAction) {
                if (_onAction.length === 2) {
                    var _function = _actions[_onAction[1]];
                    if (_function) {
                        $(this).on(_onAction[0], function(event) {
                            console.log(
                                'execute->',
                                '#' + _objElemId + ':',
                                _onAction.join('->'),
                                '#' + _container.getElem().id
                            );
                            _function.apply(_container, [this, event]);
                            return false;
                        });
                    }
                }
            };    
            // all sub-elements
            this.$('[data-d2h-on]', handlerEle).each(function() {
                _fnActionOn.call(this, $(this).attr('data-d2h-on').split(':'));
            });
            
            // self element
            var handlerOnAction = $(handlerEle).attr('data-d2h-on');
            if (handlerOnAction) {
                var _OnActionHandl = handlerOnAction.split(':');
                if (
                    handlerEle.id === this.objElem.id &&
                    _OnActionHandl[0] === 'change'
                ) {
                    // force change to sub inputs into into objElem as handler.
                    this.$('[data-d2h-input]').each(function() {
                        _fnActionOn.call(this, _OnActionHandl);
                    });
                } else {
                    _fnActionOn.call(handlerEle, _OnActionHandl);
                }
            }
        },
        
        setRows: function(jsonData, add) {
            if (jsonData.rowsAsArray) {
                var rows = [],
                    dataCols = jsonData.dataCols, // TODO
                    indexCols = {};
                for (var i = 0, len = dataCols.length; i < len; i++) {
                    indexCols[dataCols[i]] = i;
                }
                var rowsAsArray = jsonData.rowsAsArray,
                    rowsCount = rowsAsArray.length;
                for (var i = 0; i < rowsCount; i++) {
                    var item = rowsAsArray[i];
                    var row = {};
                    for (var tagName in indexCols) {
                        row[tagName] = item[indexCols[tagName]];
                    }
                    rows.push(row);
                }
            } else {
                rows = jsonData.rows;
            }
            if (add) {
                Array.prototype.push.apply(this._rows, rows);
            } else {
                this._rows = rows;
            }
            return this;
        },
        
        clear: function() {},
        
        server: function(_options) {
            var _this = this,
                _settings = this.settings;
            if (!_settings.url) {
                try {
                    console.log({ERROR: 'Request aborted', settings:_settings, options:_options});
                } catch (e) {}
                $.error("d2h_server: Request aborted, url is missing for: " +
                    d2h_utils.getElementPath(this.objElem)
                );
            };
            if (!_options) {
                _options = {};
            }
            var ajaxOptions = {
                dataType: "json",
                type: _options.ajaxType,
                url: _options.url ? _options.url :  _settings.url,
                data: _options.ajaxType === 'GET' ? _options.data : JSON.stringify(_options.data),
                beforeSend: function(jqXHR, settings) {
                    var response = true
                        beforeArray = _options.before;
                    if (beforeArray) {
                        for (var i = 0, l = beforeArray.length; i < l; i++) {
                            var before = beforeArray[i];
                            if (before) {
                                if (typeof before === "string") {
                                    response = _this.trigger(before, [jqXHR, settings]);
                                } else {
                                    response = before.apply(_this, [jqXHR, settings]);
                                }
                                if (response === false) { return false; }
                            }
                        }
                    }
                    _wait.show();
                    return response;
                },
                error: function(jqXHR, textStatus, textError){
                    var response = true,
                        errorArray = _options.error,
                        jsonError;
                    try {
                        jsonError = JSON.parse(jqXHR.responseText);
                    } catch (e) {
                        jsonError = {
                            'response-text-is-not-json': jqXHR.responseText
                        };
                    }
                    var errorMessage = jqXHR.status + ' ' + textError;
                    try {
                        console.log(errorMessage, jsonError);
                    } catch (e) {}
                    if (jsonError['user-errors']) {
                        if (d2h_display.showErrors(_this, jsonError['user-errors'])) {
                            return false;
                        }
                    }
                    if (errorArray) {
                        for (var i = 0, l = errorArray.length; i < l; i++) {
                            var error_ = errorArray[i];
                            if (error_) {
                                if (typeof error_ === "string") {
                                    response = _this.trigger(error_, [errorMessage, jsonError]);
                                } else {
                                    response = error_.apply(_this, [errorMessage, jsonError]);
                                }
                                if (response === false) { return false; }
                            }
                        }
                    }
                    alert(errorMessage); // Notify the user since everything has failed
                },
                success: function(jsonData) {
                    var response = true
                        afterArray = _options.after;
                    if (afterArray) {
                        for (var i = 0, l = afterArray.length; i < l; i++) {
                            var after = afterArray[i];
                            if (after) {
                                if (typeof after === "string") {
                                    response = _this.trigger(after, [jsonData]);
                                } else {
                                    response = after.apply(_this, [jsonData]);
                                }
                                if (response === false) { return false; }
                            }
                        }
                    }
                },
                complete: function(msg){
                    _wait.hide();
                    if (_options.complete) {
                        _options.complete.call(_this)
                    }
                }
            };
            if (_settings.singleRequest) {
                var ajaxPromise = $.ajax(ajaxOptions);
                this.promise = $.when(this.getPromise(), ajaxPromise);
                return ajaxPromise;
            } else {
                this.promise = $.when(this.getPromise(), $.ajax(ajaxOptions));
                return this.promise;
            }
        }
    };
     
    return d2h_serverBase;
})(jQuery);
