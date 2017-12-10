/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
jQuery.ajaxSetup({ cache: false });

(function ($) {

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
    function dataBase(objElem, container, options) {
        this._init(objElem, container, options);
    }
    dataBase.prototype = {
        defaults: {
            url: '',
            ajaxType: 'GET',
            auto: null,
            
            beforeRead: function() { return true; },
            afterRead: function(row_count) {} //called, once loop through data has finished
        },
        
        settings: null,
        objElem: null, // The DOM element
        _container: null,        
        _initId: 0,
        
        _visualData: null,
        _rows: null, //the data once loaded/received
        
        _init: function(objElem, container, options) {
            this.defaults =
                $.extend({}, dataBase.prototype.defaults, this.defaults);
            this.objElem = objElem;
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
                this._visualData = _getElementJsData(objElem, 'd2h-visual');;
            }
            if (settings.actions) {
                this.listen(objElem, settings.actions);
            }
            
            // Add pluguin
            $.data(this.objElem, "Data2Html_data", this);
        },
        
        get: function() {
            return this;
        },
        getElem: function() {
            return this.objElem;
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
            $('[data-d2h-on]', handlerEle).each(function() {
                _fnAction.call(this);
            });
            // self element
            if ($(handlerEle).attr('data-d2h-on')) {
                _fnAction.call(handlerEle);
            }
        },

        // Data manage
        getKeys: function(elem, visualClass) {
            var $parent = $(elem).closest('[data-d2h-keys]');
            $('.' + visualClass, this.objElem).removeClass(visualClass);
            return $parent.addClass(visualClass).attr('data-d2h-keys');
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
        }
    };
    
    function dataGrid(objElem, container, options) {
        this._init(objElem, container, options);
        var autoCall = this.settings.auto;
        if (autoCall) {
            this[autoCall].call(this, this.settings);
        }
    }

    // ------
    // Grid
    // ------
    $.extend(dataGrid.prototype, dataBase.prototype, {
        defaults: {
            type: 'grid',
            auto: 'load',
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

        _init: function(objElem, container, options) {
            dataBase.prototype._init.apply(this, [objElem, container, options]);
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
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(gridEle) +
                    "': Does not contain a '" +
                    settings.repeat +
                    "' selector."
                );
                return;
            }
            if ($itemRepeat.length > 1) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(gridEle) +
                    "': Contains more than one '" +
                    settings.repeat +
                    "' selector."
                );
                return;
            }

            // Mark repeat and parent elements.
            $itemRepeat.addClass(iClassRepeat);
            var $parentContainer = $itemRepeat.parent();
            if ($(this._selectorRepeatParent, gridEle).length > 0) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(gridEle) +
                    "': Contains selector '" +
                    this._selectorRepeatParent +
                    "' which is for internal use only!"
                );
                return;
            }
            $parentContainer.addClass(iClassRepeatParent);
            if ($(this._selectorRepeat, $parentContainer).length > 1) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath($parentContainer[0]) +
                    "': Contains more than one '" +
                    this._selectorRepeat +
                    "' selector."
                );
                return;
            }
                
            // Set template
            this._repeatHtml = $itemRepeat.get(0).outerHTML;
            this._repeatStart = $parentContainer.children().index($itemRepeat);
            
            // additional calls
            if (settings.filter) {
                this._initComponent('filter', settings.filter);
            }
            if (settings.page) {
                this._initComponent('page', settings.page);
            }
            // sortBy
            if (settings.sort) {
                d2h_sortBy.create(this, settings.sort)
                    .show($(settings.sort).val());
            }
            
            // clear
            this.clear();
        },
        
        _initComponent: function(compomentName, selector) {
            // Check arguments
            var compomentOptions = null;
            if (!selector) { return; }
            if ($.isArray(selector)) {
                if (selector.length < 1 || selector.length > 2) {
                    $.error(
                        "Data2Html can not initialize compoment '" + 
                        compomentName +
                        "'. When selector is array must have 1 or 2 items!"
                    );
                    return;
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
            if ($elem.length !== 1) {
                $.error(
                    "Data2Html: Selector '" + selector + 
                  //  "' of component '" + componentName +
                    "' has selected " + $elem.length +
                    " elements. Must select only one element!"
                );
                return;
            }
            this.components[compomentName] =
                new dataForm($elem[0], this, compomentOptions);
        },
        
        load: function(options) {
            var _settings = $.extend({}, this.settings, options);
            var data = {},
                pageStart = 1;
            if (this.components.filter) {
                var values =
                    d2h_values.serialize($(this.components.filter.getElem()));
                data['d2h_filter'] = values.replace(/&/g, '[,]');
            }
            if (this.components.page) {
                if (_settings.add) {
                    pageStart = this._rows ? this._rows.length + 1 : 1;
                }
                data['d2h_page'] = 'pageStart=' + pageStart + '[,]' +
                    d2h_values.serialize($(this.components.page.getElem()))
                        .replace(/&/g, '[,]');
            }
            if (_settings.sort) {
                data['d2h_sort'] = $(_settings.sort, this.objElem).val();
            }
            var _this = this,
                _gridEle = this.objElem;
            if (!_settings.add) {
                _this._rows = null;
            }
            $.ajax({
                type: _settings.ajaxType,
                url: _settings.url,
                data: data,
                dataType: "json", 
                beforeSend: function(){
                    var response = _settings.beforeRead.call(_this);
                    if (response !== false) {
                        _wait.show();
                    }
                    return response;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    if (typeof bootbox != 'undefined'){
                        bootbox.alert({
                            title : "Error",
                            message : "<div class='alert alert-warning'>Ops! Something went wrong while loading data: <strong>" + 
                                XMLHttpRequest.responseText + "</strong></div>",												
                        });
                    } else {
                        alert(
                            'An error "' + errorThrown + '", status "' + 
                            textStatus + '" occurred during loading data: ' + 
                            XMLHttpRequest.responseText
                        );
                    }
                },
                success: function(jsonData){
                    _this.setRows(jsonData, _settings.add);
                    _this.showGridData(jsonData.cols);
                    _settings.afterRead.call(_this);
                },
                complete: function(msg){
                    _wait.hide();
                    $("*", _gridEle).removeClass(_globalDefaults.classFormChanged);
                }
            });
        },

        // Manage grid HTML
        clear: function () {
            var $parentContainer = $(this._selectorRepeatParent, this.objElem);
            if ($parentContainer.length === 0) {
                $parentContainer = $(this.objElem);
            }
            $(this._selectorRepeat, $parentContainer).remove();
        },
    
        showGridData: function (cols) {
            this.clear();
            
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
            var patt = /\$\{(\[keys\]|[\w\d]+|[\w\d]+\s*\|[\s\w\d,;:\(\)\.\|\-+'"]+)\}/g,
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
                visualData = this._visualData;
            for (var i = 0, l = rows.length; i < l; i++){
                var html = this._repeatHtml,
                    row = rows[i];
                for (var ii = 0, ll = replaces.length; ii < ll; ii++) {
                    var replItem = replaces[ii],
                        iName = replItem.name,
                        visualEle = visualData[iName],
                        dataType =  visualEle ? visualEle.type : null,
                        val;
                    if (iName === '[keys]') {
                        html = html.replace(replItem.repl, row['[keys]'].join(','));
                    } else {
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
                _settings.afterShowGridRow.call(this, i, lastItem);
            }
        }
    });
        
    // ------
    // Form
    // ------
    function dataForm(objElem, container, options) {
        this._init(objElem, container, options);
        var autoCall = this.settings.auto;
        if (autoCall) {
            autoCall.call(this, this.settings);
        }
    }
    $.extend(dataForm.prototype, dataBase.prototype, {
        defaults: {
            type: 'form',            
            beforeSave: function(data) { return true; },
            afterSave: function(data) {},
            beforeDelete: function(data) { return true; },
            afterDelete: function(data) {}
        },
        _init: function(objElem, container, options) {
            dataBase.prototype._init.apply(this, [objElem, container, options]);
            
            // prevent submit
            var formEle = this.objElem,
                $formEle = $(formEle);
            if (formEle.tagName === 'FORM')  {
                $formEle.on('submit', function() {
                    return false;
                });
            }
            $formEle.change(function() {
                $formEle.addClass(_globalDefaults.classFormChanged);
            });
                   
            // clear
            this.clear();
        },
        load: function(options) {
            if (!options) {
                $.error(
                    "Data2Html: Can not load form without options, it must exist 'keys' parameter."
                );
            }
            var _settings = $.extend({}, this.settings, options);
            var _this = this,
                _formEle = this.objElem,
                keys;
            if (options.keys) {
                keys = options.keys;
            } else {
                $.error(
                    "Data2Html: Can not load form without 'keys' parameter."
                );
            }
            $.ajax({
                type: _settings.ajaxType,
                url: _settings.url,		
                data: {d2h_keys: keys},
                dataType: "json", 
                beforeSend: function(){
                    var response = _settings.beforeRead.call(_this, 0);
                    if (response !== false) {
                        _wait.show();
                    }
                    return response;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown){
                    if (typeof bootbox != 'undefined'){
                        bootbox.alert({
                            title : "Error",
                            message : "<div class='alert alert-warning'>Ops! Something went wrong while loading data: <strong>" + 
                                XMLHttpRequest.responseText + "</strong></div>",												
                        });
                    } else {
                        alert(
                            'An error "' + errorThrown + '", status "' + 
                            textStatus + '" occurred during loading data: ' + 
                            XMLHttpRequest.responseText
                        );
                    }
                },
                success: function(jsonData){
                    _this.setRows(jsonData);
                    if (_this._rows.length > 0) {
                        _this.showFormData(_this._rows[0]);
                    } else {
                        _this.clear();
                    }
                    _settings.afterRead.call(_this);
                },
                complete: function(msg){
                    _wait.hide();
                    $(_formEle).removeClass(_globalDefaults.classFormChanged);
                }
            });
        },
        save: function(options) {
            var visualData = this._visualData,
                d2h_oper,
                data = {};
            for (iName in this._visualData) {
                var visualEle = visualData[iName];
                data[iName] = d2h_values.get(
                    $('[name=' + iName + ']', this.objElem),
                    visualEle ? visualEle.type : null
                );
            }
            data['[keys]'] = $(this.objElem).data('d2h-keys');
            if (data['[keys]']) {
                d2h_oper = 'update';
            } else {
                d2h_oper = 'insert';
            }
            
            this._rows = null;
            var _settings = $.extend({}, this.settings, options),
                _this = this,
                _formEle = this.objElem;
            if (_settings.beforeSave.call(this, data) !== false) {
                $.ajax({
                    type: 'POST',
                    url: _settings.url,		
                    data: JSON.stringify({
                        d2h_oper: d2h_oper,
                        d2h_data: data
                    }),
                    dataType: 'json',
                    beforeSend: function(){
                        _wait.show();
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        if (typeof bootbox != 'undefined'){
                            bootbox.alert({
                                title : "Error",
                                message : "<div class='alert alert-warning'>Ops! Something went wrong while loading data: <strong>" + 
                                    XMLHttpRequest.responseText + "</strong></div>",												
                            });
                        } else {
                            alert(
                                'An error "' + errorThrown + '", status "' + 
                                textStatus + '" occurred during loading data: ' + 
                                XMLHttpRequest.responseText
                            );
                        }
                    },
                    success: function(jsonData){
                        _settings.afterSave.call(_this);
                    },
                    complete: function(msg){
                        _wait.hide();
                        $(_formEle).removeClass(_globalDefaults.classFormChanged);
                    }
                });
            }
        },
        'delete': function(options) {
            var visualData = this._visualData,
                d2h_oper,
                data = {};
            for (iName in _this._visualData) {
                var visualEle = visualData[iName];
                data[iName] = d2h_values.get(
                    $('[name=' + iName + ']', this.objElem),
                    visualEle ? visualEle.type : null
                );
            }
            data['[keys]'] = $(this.objElem).data('d2h-keys');
            
            this._rows = null;
            var _settings = $.extend({}, this.settings, options);
                _this = this,
                _formEle = this.objElem;
            if (_settings.beforeDelete.call(_this, data) !== false) {
                $.ajax({
                    type: 'POST',
                    url: _settings.url,		
                    data: JSON.stringify({
                        d2h_oper: 'delete',
                        d2h_data: data
                    }),
                    dataType: 'json',
                    beforeSend: function(){
                        _wait.show();
                    },
                    error: function(XMLHttpRequest, textStatus, errorThrown){
                        if (typeof bootbox != 'undefined'){
                            bootbox.alert({
                                title : "Error",
                                message : "<div class='alert alert-warning'>Ops! Something went wrong while loading data: <strong>" + 
                                    XMLHttpRequest.responseText + "</strong></div>",												
                            });
                        } else {
                            alert(
                                'An error "' + errorThrown + '", status "' + 
                                textStatus + '" occurred during loading data: ' + 
                                XMLHttpRequest.responseText
                            );
                        }
                    },
                    success: function(jsonData){
                        _settings.afterDelete.call(_this);
                    },
                    complete: function(msg){
                        _wait.hide();
                        $(_formEle).removeClass(_globalDefaults.classFormChanged);
                    }
                });
            }
        },
        clear: function(options) {
            if (options && options.switchTo) {
                this.switchTo(this.type);
            }
            var tagName,
                visualData = this._visualData;
            for (tagName in visualData) {
                var val = "",
                    visualEle = visualData[tagName];
                if (visualEle.default) {
                    val = visualEle.default;
                };
                d2h_values.put($('[name=' + tagName + ']', this.objElem), val, visualEle.type);
            }
            $(this.objElem).data('d2h-keys', '');
        },
        showFormData: function(row) {
            var tagName,
                visualData = this._visualData;
            for (tagName in visualData) {
                var val = row[tagName] !== undefined ? row[tagName] : "",
                    visualEle = visualData[tagName];
                d2h_values.put($('[name=' + tagName + ']', this.objElem), val, visualEle.type);
            }
            $(this.objElem).data('d2h-keys', row['[keys]'].join(','));
        }
    });
    
    
    /**
     * Utilities
     */
     
    // scope none
    function _getElementPath(elem) {
        if (elem === undefined) {
            return "undefined";
        }
        var selectorArr = [
            elem.tagName.toLowerCase() +
            (elem.id ? '#' + elem.id : '')
        ];
        $(elem).parents().map(
            function() {
                selectorArr.push(
                    this.tagName.toLowerCase() +
                    (this.id ? '#' + this.id : '')
                );
            }
        );
        return selectorArr.reverse().join(">");
    }
    
    function _getElementOptions(objElem, defaultOptions, options) {
        var optionsEle = _getElementJsData(objElem, 'd2h');
        if (!optionsEle && !options) {
            $.error(
                "Can not initialize a data2html handler: " +
                "Options or HTML attribute 'data-d2h' are required on '" + 
                    _getElementPath(objElem) + "'"
            );
            return;
        }
        return $.extend(optionsEle, options); 
    }
    
    function _getElementJsData(objElem, dataName) {
        var optionsEle = {},
            dataD2h = $(objElem).data(dataName);
        if (dataD2h) {
            if (dataD2h.substr(0, 1) !== '{') {
                dataD2h = '{' + dataD2h + '}';
            }
            try {
                optionsEle = eval('[(' + dataD2h + ')]')[0];
            } catch(e) {
                $.error(
                    "Can not initialize a data2html handler: " +
                    "HTML attribute 'data-' " + dataName + "' have a not valid js syntax on '" + 
                        _getElementPath(objElem) + "'" 
                );
                return null;
            }
        }
        return optionsEle;
    }
    
    /**
     * Plugin declaration
     */
    $.fn.data2html = function() {
        if (this.length == 0) {
            $.error(
                "Data2Html: Can not find a DOM object with the selector!"
            );
            return this;
        }
        var _method = '',
            _options = null;
        switch (arguments.length) {
        case 0:
                break;
        case 1:
            if ($.isPlainObject(arguments[0])) {
                _options = arguments[0];
            } else if (typeof arguments[0] === "string") {
                _method = arguments[0];
            } else {
                $.error(
                    "Data2Html: Can not find a plainObject or string as single argument!"
                );
                return this;
            }
            break;
        case 2:
            if (typeof arguments[0] === "string") {
                _method = arguments[0];
                _options = arguments[1];
            } else {
                $.error(
                    "Data2Html: Can not find a: string, plainObject as arguments!"
                );
                return this;
            }
            break;
        default:
            $.error(
                "Data2Html: Excess number of arguments!"
            );
            return this;
        }
        
        var _response;
        this.each(function() {
            if (!$.data(this, "Data2Html_data") ) {
                var opData = _getElementOptions(this, _options);
                switch (opData.type) {
                    case 'form':
                        new dataForm(this, null, opData);
                        break;
                    default:
                        new dataGrid(this, null, opData);
                        break;
                }
            }
            if (_method) {
                var thisObj = $.data(this, "Data2Html_data");
                _response = thisObj[_method].call(thisObj, _options);
            }
        });
        if (this.length === 1 && _response !== undefined) {
            return _response; // is a function to retrieve a value.
        } else {
            return this; // chain jQuery functions
        }
    };
})(jQuery);
