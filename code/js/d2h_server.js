/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
jQuery.ajaxSetup({ cache: false });

var d2h_server = (function ($) {

    var _initCounter = 0;
    var _globalDefaults = {
        classWaiting: 'd2h_waiting',
        classFormChanged: 'd2h_formChanged', // Only set at initial declaration
    };
    
    function waitHandler() {
    }
    waitHandler.prototype = {
        _classWaiting: '',
        _waitCounter: 0, // Only one wait
        hide: function() {
            if (this._classWaiting) {
                this._waitCounter--;
                if (this._waitCounter <=0) {
                    this._waitCounter = 0;
                    $('.' + this._classWaiting).hide();
                    this._classWaiting = '';
                }
            }   
        },
        show: function() { 
            if (_globalDefaults.classWaiting) {
                if (this._waitCounter === 0) {
                    this._classWaiting = _globalDefaults.classWaiting;
                    $('.' + this._classWaiting).show();
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
    function dataHtml(objElem, container, options) {
        this._init(objElem, container, options);
    }
    dataHtml.prototype = {
        events: [],
        defaults: {
            url: '',
            ajaxType: 'GET',
            auto: null
        },
        
        settings: null,
        status: null,
        promise: null,
        objElem: null, // The DOM element
        _events: null,
        _container: null,        
        _initId: 0,
        
        _visualData: null,
        _rows: null, //the data once loaded/received
        
        _init: function(objElem, container, options) {
            this.defaults =
                $.extend({}, dataHtml.prototype.defaults, this.defaults);
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
                this._visualData = d2h_utils.getJsData(objElem, 'd2h-visual');;
            }
            if (settings.actions) {
                this.listen(objElem, settings.actions);
            }
            
            // Add pluguin
            $.data(this.objElem, "Data2Html_data", this);
 
            // Register events
            this._events = {};
            for( var i = 0, l = this.events.length; i < l; i++) {
                var evName = this.events[i];
                if (settings[evName]) {
                    this.on(evName, settings[evName]);
                }
            }
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
            var _this = this;
            if (!this._events[eventName]) {
                this._events[eventName] = 0;
            }
            this._events[eventName]++;
            $(this.objElem).on(
                'd2h_srv_' + eventName,
                function() {
                    var args = [];
                    Array.prototype.push.apply(args, arguments);
                    args.shift();
                    return handlerFn.apply(_this, args);
                }
            );
            return this;
        },
        
        isEventUsed: function(eventName) {
            return !!this._events[eventName];
        },
        
        trigger: function(eventName, args) {
            return $(this.objElem).triggerHandler('d2h_srv_' + eventName, args);
        },
        
        getPromise: function() {
            if (this.promise && this.promise.state() === 'pending') {
                return this.promise;
            } 
            this.promise = null;
            return; // return undefined
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
            var _container = this._container;
            // Scope handlerEle
            var _fnAction = function() {
                var $thisEle = $(this),
                    _onAction = $thisEle.data('d2h-on').split(':');
                if (_onAction.length === 2) {
                    var _function = _actions[_onAction[1]];
                    if (_function) {
                        $thisEle.on(_onAction[0], function(event) {
                            console.log('#' + _container.getElem().id + ': ' + _onAction.join('->'));
                            _function.apply(_container, [this, event]);
                            return false;
                        });
                    }
                }
            };
            // all sub-elements
            this.$('[data-d2h-on]', handlerEle).each(
                function() {
                    _fnAction.call(this);
                }
            );
            // self element
            if ($(handlerEle).attr('data-d2h-on')) {
                _fnAction.call(handlerEle);
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
            this.promise = $.when(this.getPromise(), $.ajax({
                dataType: "json",
                type: _options.ajaxType,
                url: _settings.url,
                data: _options.ajaxType === 'GET' ? _options.data : JSON.stringify(_options.data),
                beforeSend: function(jqXHR, settings) {
                    var response = true
                        beforeArray = _options.before;
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
                    _wait.show();
                    return response;
                },
                error: function(jqXHR, textStatus, textError){
                    var response = true,
                        errorArray = _options.error,
                        jsonError,
                        userMessages,
                        message = 'An error occurred executing a database action.';
                    try {
                        jsonError = JSON.parse(jqXHR.responseText);
                        if (jsonError['user-messages']) {
                            userMessages = jsonError['user-messages'];
                        }
                    } catch (e) {
                        jsonError = {
                            'requested': {url:_settings.url, data:_options.data},
                            'response-text': jqXHR.responseText
                        };
                    }
                    console.log(jqXHR.status + ' ' + textError + ': ' + message, jsonError);
                    for (var i = 0, l = errorArray.length; i < l; i++) {
                        var error_ = errorArray[i];
                        if (error_) {
                            if (typeof error_ === "string") {
                                response = _this.trigger(error_, [message, jsonError]);
                            } else {
                                response = error_.apply(_this, [message, jsonError]);
                            }
                            if (response === false) { return false; }
                        }
                    }
                    alert(message);
                },
                success: function(jsonData) {
                    var response = true
                        afterArray = _options.after;
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
                },
                complete: function(msg){
                    _wait.hide();
                    if (_options.complete) {
                        _options.complete.call(_this)
                    }
                }
            }));
            return this;
        }
    };
    
    // ------
    // Grid
    // ------
    function dataGrid(objElem, container, options) {
        this._init(objElem, container, options);
    }

    $.extend(dataGrid.prototype, dataHtml.prototype, {
        events: ["beforeLoadGrid", "errorLoadGrid", "afterLoadGrid"],
        defaults: {
            type: 'grid',
            auto: 'loadGrid',
            repeat: '.d2h_repeat',
            pageSize: 0, //default results per page
            
            filter: null,
            page: null,
            
            //called, after show each row
            afterShowGridRow: function(row_index, elemRow) {},
        },
        
        components: null,

        // repeat
        _repeatHtml: '',       // template HTML string
        _repeatStart: 0,
        _selectorRepeat: '',
        _selectorRepeatParent: '',
        _branchKeys: null,

        _init: function(objElem, container, options) {
            dataHtml.prototype._init.apply(this, [objElem, container, options]);
            var settings = this.settings,
                gridEle = this.objElem;

            // Set internal selectors
            this.components = {};
            var iClassRepeat = 'i_d2h_repeat_' + this._initId,
                iClassRepeatParent = iClassRepeat + '_container';
            this._selectorRepeat = '.' + iClassRepeat;
            this._selectorRepeatParent = '.' + iClassRepeatParent;

            // Check repeat selector
            var $itemRepeat = $(settings.repeat, gridEle);
            if ($itemRepeat.length == 0) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath(gridEle) +
                    "': Does not contain a '" +
                    settings.repeat +
                    "' selector."
                );
            }
            if ($itemRepeat.length > 1) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath(gridEle) +
                    "': Contains more than one '" +
                    settings.repeat +
                    "' selector."
                );
            }

            // Mark repeat and parent elements.
            $itemRepeat.addClass(iClassRepeat);
            var $parentContainer = $itemRepeat.parent();
            if ($(this._selectorRepeatParent, gridEle).length > 0) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath(gridEle) +
                    "': Contains selector '" +
                    this._selectorRepeatParent +
                    "' which is for internal use only!"
                );
            }
            $parentContainer.addClass(iClassRepeatParent);
            if ($(this._selectorRepeat, $parentContainer).length > 1) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath($parentContainer[0]) +
                    "': Contains more than one '" +
                    this._selectorRepeat +
                    "' selector."
                );
                return;
            }
                
            // Set template
            this._repeatHtml = $itemRepeat.get(0).outerHTML;
            this._repeatStart = $parentContainer.children().index($itemRepeat);
            
            // initialize components
            // page
            if (settings.page) {
                this._initComponent('page', settings.page);
            }
            // sort
            if (settings.sort) {
                d2h_sort.create(this, settings.sort).show($(settings.sort).val());
            }
            // filter
            var promises = null;
            if (settings.filter) {
                this._initComponent('filter', settings.filter);
                if (this.components.filter) {
                    promises = this.components.filter.getPromise();
                }
            }
            
            // aditional calls
            this.whenPromise(promises, function() {
                // Issue of default value of a select:
                //  * clearGrid after filter promises are done
                this.clearGrid();
                
                // Auto call
                var autoCall = this.settings.auto;
                if (autoCall) {
                    this[autoCall].call(this, this.settings);
                }
            });
        },
        
        _initComponent: function(compomentName, selector) {
            // Check arguments
            var compomentOptions = null;
            if (!selector) { return; }
            if ($.isArray(selector)) {
                if (selector.length < 1 || selector.length > 2) {
                    $.error(
                        "d2h_server: Can not initialize component '" + 
                        compomentName +
                        "'. When selector is array must have 1 or 2 items!"
                    );
                }
                if (selector.length >= 2) {
                    compomentOptions = selector[1];
                }
                selector = selector[0];
            }
            
            // To set up the components
            var $elem;
            if (selector.substr(0, 1) === "#") {
                $elem = $(selector);
            } else {
                $elem = $(selector, this.objElem);
            }
            if ($elem.length === 0) {
                this.components[compomentName] = null;
                return;
            } else if ($elem.length !== 1) {
                $.error(
                    "d2h_server: Selector '" + selector +
                    "' has selected " + $elem.length +
                    " elements. Must select only one element!"
                );
            }
            this.components[compomentName] =
                new dataElement($elem[0], this, compomentOptions);
        },
        
        getComponent: function(compomentName) {
            return this.components[compomentName];
        },
        
        loadGrid: function(options) {
            var sortSelector = this.settings.sort,
                _add = false,
                data = {},
                pageStart = 1;
            if (this.components.filter) {
                data['d2h_filter'] = $.param(
                    d2h_values.getData(this.components.filter)
                ).replace(/&/g, '{and}');
            }
            if (sortSelector) {
                data['d2h_sort'] = $(sortSelector, this.objElem).val();
            }
            
            if (options && options.add) {
                _add = true;
            } else {
                this._rows = null;
            }
            if (this.components.page) {
                if (_add) {
                    pageStart = this._rows ? this._rows.length + 1 : 1;
                }
                var aux = d2h_values.getData(this.components.page);
                aux['pageStart'] = pageStart;
                data['d2h_page'] = $.param(aux).replace(/&/g, '{and}');
            }
            this.server({
                ajaxType: 'GET',
                data: data,
                before: [
                    "beforeLoadGrid",
                    options && options.beforeLoadGrid
                ],
                error: [
                    "errorLoadGrid",
                    options && options.errorLoadGrid
                ],
                after: [
                    function(jsonData) {
                        this.setRows(jsonData, _add);
                        this.showGrid();
                    },
                    "afterLoadGrid",
                    options && options.afterLoadGrid
                ],
                complete: function(msg) {
                    $("*", this.objElem).removeClass(_globalDefaults.classFormChanged);
                }
            });
            return this;
        },

        // Manage grid HTML
        clearGrid: function() {
            var $parentContainer = $(this._selectorRepeatParent, this.objElem);
            if ($parentContainer.length === 0) {
                $parentContainer = $(this.objElem);
            }
            $(this._selectorRepeat, $parentContainer).remove();
            return this;
        },
        
        getSelectedKeys: function(elem) {
            if (elem === undefined) {
                return null;
            }
            var $parent = $(elem).closest('[data-d2h-keys]'),
                selectedClass = this.settings.selectedClass;
            if (selectedClass) {
                $('.' + selectedClass, this.objElem).removeClass(selectedClass);
                $parent.addClass(selectedClass);
            }
            return this.selectedKeys(JSON.parse($parent.attr('data-d2h-keys'))); // Store keys
        },
        
        selectedKeys: function(newKeys) {
            if (arguments.length > 0) {
                this.status.selectedKeys = newKeys;
            }
            return this.status.selectedKeys;
        },
        
        branchKeys: function(branchKeys) {
            if (arguments.length > 0) {
                this._branchKeys = branchKeys;
            }
            return this._branchKeys;
        },
        
        showGrid: function () {
            this.clearGrid();
            
            var $parentContainer = $(this._selectorRepeatParent, this.objElem),
                lastItem = null;
            if ($parentContainer.length === 0) {
                $parentContainer = $(this.objElem);
            }
            if (this._repeatStart > 0) {
                lastItem = $(
                    $parentContainer.children()[this._repeatStart - 1]
                );
            }
            var patt = /\$\{(\[keys\]\s*\|?\s*|[\w\d]+|[\w\d]+\s*\|[\s\w\d,;:\(\)\.\|\-+'"]+)\}/g,
                repl,
                replaces = [];
            while (repl = patt.exec(this._repeatHtml)) {
                var formatIndex = repl[1].indexOf('|'),
                    name,
                    format = '';
                if (formatIndex >= 0) {
                    name = repl[1].substr(0, formatIndex -1).trim();
                    format = repl[1].substr(formatIndex +1).trim();
                } else {
                    name = repl[1];
                }
                replaces.push({
                    repl: repl[0],
                    name: name,
                    format: format
                });
            }
            // loop rows           
            var _settings = this.settings,
                rows = this._rows,
                visualData = this._visualData,
                selectedKeys = JSON.stringify(this.selectedKeys()),
                cols = [];
            for (var i = 0, l = rows.length; i < l; i++){
                var html = this._repeatHtml,
                    row = rows[i],
                    xName,
                    rowKeys = null;
                if (i === 0) {
                    for (xName in row) {
                        cols.push(xName);
                    }
                }
                for (var ii = 0, ll = replaces.length; ii < ll; ii++) {
                    var replItem = replaces[ii],
                        iName = replItem.name.replace(' ', ''),
                        val;
                    if (iName === '[keys]') {
                        val = row['[keys]'];
                        if (replItem.repl.indexOf('|') > 0 && val.length === 1) {
                            // When pattern ${[keys] | } force scalar if is possible
                            rowKeys = val[0];
                        } else {
                            rowKeys = JSON.stringify(val);
                        }
                        html = html.replace(replItem.repl, rowKeys);
                    } else {
                        var visualEle = visualData ? visualData[iName] : null,
                            dataType =  visualEle ? visualEle.type : null;
                        if ($.isNumeric(iName)) {
                            val = row[cols[iName]];
                        } else {
                            val = row[iName];
                        }
                        html = html.replace(replItem.repl, d2h_values.toHtml(val, dataType));
                    }
                }
                if (lastItem) {
                    lastItem.after(html);
                } else {
                    $parentContainer.prepend(html);
                }
                lastItem = $(
                    this._selectorRepeat + ':last',
                    $parentContainer
                );
                if (_settings.actions) {
                    this.listen(lastItem, _settings.actions);
                }
                if (selectedKeys === rowKeys && this.settings.selectedClass) {
                    lastItem.addClass(this.settings.selectedClass);
                }
                _settings.afterShowGridRow.call(this, i, lastItem);
            }
            return this;
        }
    });

    
    // ------
    // Form
    // ------
    function dataElement(objElem, container, options) {
        this._init(objElem, container, options);
        var autoCall = this.settings.auto;
        if (autoCall) {
            autoCall.call(this, this.settings);
        }
    }
    $.extend(dataElement.prototype, dataHtml.prototype, {
        enents: [
            'beforeSave', 'afterSave', 'errorSave',
            'beforeDelete', 'afterDelete', 'errorDelete',
            'beforeLoadElement', 'afterLoadElement','errorLoadElement'
        ],
        defaults: {
            type: 'element'
        },
        _init: function(objElem, container, options) {
            dataHtml.prototype._init.apply(this, [objElem, container, options]);
            
            // prevent submit
            var formEle = this.objElem,
                $formEle = $(formEle);
            if (formEle.tagName === 'FORM')  {
                $formEle.on('submit', function() {
                    return false;
                });
            }
            this.$('[name]').change(function() {
                $formEle.addClass(_globalDefaults.classFormChanged);
            });
                   
            // clearForm
            var promises = null,
                $subElements = $('[data-d2h]', this.objElem);
            if ($subElements.length > 0) {
                _promises = [];
                $subElements.each(function() {
                    _promises.push(d2h_server(this).getPromise());
                });
            }
            this.whenPromise(promises, function() {
                // Issue of default value of a select:
                //  - clearForm after sub element promises are done.
                this.clearForm();
            });
        },
        
        loadElement: function(options) {
            if (!options || !options.keys) {
                $.error("d2h_server: Can't load form data without 'keys' option.");
            }
            var _settings = this.settings;
            this.server({
                ajaxType: 'GET',
                data: {d2h_keys: options.keys},
                before: [
                    "beforeLoadElement",
                    options && options.beforeLoadElement
                ],
                error: [
                    "errorLoadElement",
                    options && options.errorLoadElement
                ],
                after: [
                    function(jsonData) {
                        this.setRows(jsonData);
                        if (this._rows.length > 0) {
                            this.showFormData(this._rows[0]);
                        } else {
                            this.clearForm();
                        }
                    },
                    "afterLoadElement",
                    options && options.afterLoadElement
                ],
                complete: function(msg){
                    $(this.objElem).removeClass(_globalDefaults.classFormChanged);
                }
            });
            return this;
        },
        save: function(options) {
            var data = d2h_values.getData(this, this._visualData),
                d2h_oper,
                d2hKeys = $(this.objElem).data('d2h-keys');
            if (d2hKeys) {
                data['[keys]'] = JSON.parse(d2hKeys);
                d2h_oper = 'update';
            } else {
                d2h_oper = 'insert';
            }
            this._rows = null;
            this.server({
                ajaxType: 'POST',	
                data: {
                    d2h_oper: d2h_oper,
                    d2h_data: data
                },
                before: [
                    "beforeSave",
                    options && options.beforeSave
                ],
                error: [
                    "errorSave",
                    options && options.errorSave
                ],
                after: [
                    "afterSave",
                    options && options.afterSave
                ],
                complete: function(msg) {
                    $(this.objElem).removeClass(_globalDefaults.classFormChanged);
                }
            });
            return this;
        },
        'delete': function(options) {
            var data = d2h_values.getData(this, this._visualData);
            data['[keys]'] = JSON.parse($(this.objElem).data('d2h-keys'));
            this.server({
                ajaxType: 'POST',	
                data: {
                    d2h_oper: 'delete',
                    d2h_data: data
                },
                before: [
                    "beforeDelete",
                    options && options.beforeDelete
                ],
                error: [
                    "errorDelete",
                    options && options.errorDelete
                ],
                after: [
                    "afterDelete",
                    options && options.afterDelete
                ],
                complete: function(msg) {
                    $(this.objElem).removeClass(_globalDefaults.classFormChanged);
                }
            });
            return this;
        },
        clearForm: function(options) {
            var allElements = true;
            if (options) {
                allElements = !options.onlyWithDefault;
            }
            var tagName,
                visualData = this._visualData;
            for (tagName in visualData) {
                var val = "",
                    hasDefault = false,
                    visualEle = visualData[tagName];
                if (visualEle['default'] !== undefined) {
                    hasDefault = true;
                    val = visualEle['default'];
                };
                if (allElements || hasDefault) {
                    try {
                        d2h_values.put(
                            this.$('[name=' + tagName + ']'), 
                            val,
                            visualEle.type
                        );
                    } catch(e) {}
                }
            }
            $(this.objElem).data('d2h-keys', '');
            return this;
        },
        showFormData: function(row) {
            var tagName,
                visualData = this._visualData;
            for (tagName in visualData) {
                var val = row[tagName] !== undefined ? row[tagName] : "",
                    visualEle = visualData[tagName];
                d2h_values.put(this.$('[name=' + tagName + ']'), val, visualEle.type);
            }
            $(this.objElem).data('d2h-keys', JSON.stringify(row['[keys]']));
            return this;
        }
    });
    
    /**
     * Public
     */
    return function(elemSelector, _options) {
        var $elem = $(elemSelector);
        if ($elem.length == 0) {
            $.error(
                "d2h_server: Can not find a DOM object with the selector!"
            );
            return;
        }
        var _response = [];
        $elem.each(function() {
            if (!$.data(this, "Data2Html_data") ) {
                // Create a data for "Data2Html_data"
                var optionsEle = d2h_utils.getJsData(this, 'd2h');
                if ((!optionsEle && !_options) || typeof optionsEle === 'string') {
                    $.error(
                        "d2h_server: Can not initialize, attribute 'data-d2h' or js options are required for " + 
                        d2h_utils.getElementPath(this)
                    );
                }
                var opData = $.extend(optionsEle, _options);
                switch (opData.type) {
                    case 'element':
                        new dataElement(this, null, opData);
                        break;
                    default: // create a grid
                        new dataGrid(this, null, opData);
                        break;
                }
            }
            _response.push($.data(this, "Data2Html_data"));
        });
        if (_response.length === 0) {
            return null;
        } else if (_response.length === 1) {
            return _response[0];
        } else {
            return _response;
        }
    };
})(jQuery);
