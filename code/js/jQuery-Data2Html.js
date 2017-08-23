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

    // FORM handler
    function formHandler(element, container, options) {
        this._formInit(element, container, options);
        $.data(this.formEle, "plugin_data2html", this);
    }
    formHandler.prototype = {
        defaults: {
            url: '',
            type: 'POST',
            
            beforeSend: function(){ return true; },
            rowComplete: function(current_row_index, row) {}, //called, after each row
            complete: function(row_count){}, //called, once loop through data has finished
                
            afterChange: function() { }
        },
        formSettings: null,
        formEle: null, // The DOM element
        
        _parent: null,
        
        _rows: null, //the data once loaded/received
        _visualData: null,
        
        _formInit: function(formEle, _parent, formOptions) {
            this.formEle = formEle;
            this._parent = _parent ? _parent : this;
            
            var $formEle = $(formEle);
            var settings = _getElementOptions(
                formEle,
                'data-d2h-form',
                this.defaults,
                formOptions
            );
            var _thisForm = this;
            if (settings.visual) {
                this._visualData = settings.visual;
                delete settings.visual;
            }
            if (settings.actions) {
                var _actions = settings.actions;
                var _fnAction = function() {
                    var $thisEle = $(this),
                        _onAction = $thisEle.attr('data-d2h-on').split(':');
                    if (_onAction.length === 2) {
                        $thisEle.on(_onAction[0], function(event) {
                            console.log(_onAction.join('->'));
                            _actions[_onAction[1]].call(_thisForm._parent, this, event);
                            return false;
                        });
                    }
                };
                // all sub-elements
                $('[data-d2h-on]', formEle).each(function() {
                    _fnAction.call(this);
                });
                // self element
                if ($formEle.attr('data-d2h-on')) {
                    _fnAction.call(formEle);
                }
            }
            if (formEle.tagName === 'FORM')  {
                $formEle.on('submit', function() {
                    return false;
                });
            }
            $formEle.change(function() {
                $formEle.addClass(_globalDefaults.classFormChanged);
            });
            this.formSettings = settings;
        },
        
        load: function(options) {
            if (!this.formSettings) {
                $.error(
                    "Data2Html: Can not call 'load' without bat initialization"
                );
                return;
            }
            if (!options || !options.keys) {
                return;
            }
            var _settings = $.extend({}, this.formSettings, options);
            
            var url = _settings.url;
            var _this = this,
                _formEle = this.formEle;
            $.ajax({
                type: _settings.type,
                data: {d2h_keys: options.keys},
                url: url,		
                dataType: "json", 
                beforeSend: function(){
                    var response = _settings.beforeSend.call(_this, 0);
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
                    _this._rows = _readRows(jsonData);
                    _this._showData();
                    _settings.complete.call(_this);
                },
                complete: function(msg){
                    _wait.hide();
                    $(_formEle).removeClass(_globalDefaults.classFormChanged);
                }
            });
        },
        
        save: function(options) {
            if (!this.formSettings) {
                $.error(
                    "Data2Html: Can not call 'save' without bat initialization"
                );
                return;
            }
            var _settings = $.extend({}, this.formSettings, options);
            
            var url = _settings.url;
            var _this = this,
                _formEle = this.formEle;
            var data = {};
            for (tagName in _this._visualData) {
                data[tagName] = $('[name=' + tagName + ']', this.formEle).val();
            }
            $.ajax({
                type: 'POST',
                url: url,		
                data: JSON.stringify({
                    d2h_oper: 'save',
                    d2h_data: data
                }),
                dataType: 'json',
                beforeSend: function(){
                    var response = _settings.beforeSend.call(_this, 0);
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
                    _settings.complete.call(_this);
                },
                complete: function(msg){
                    _wait.hide();
                    $(_formEle).removeClass(_globalDefaults.classFormChanged);
                }
            });
        },
        clear: function () {
            var visualData = this._visualData;
            for (tagName in visualData) {
                var val = "",
                    visualEle = visualData[tagName];
                if (visualEle.default) {
                    val = visualEle.default;
                };
                $('[name=' + tagName + ']', this.formEle).val(val);
            }
        },
        _showData: function () {
            this.clear();
            var rows = this._rows,
                rowsCount = rows.length;
            // loop rows
            for (var i = 0; i < rowsCount; i++){
                var row = rows[i];
                for (tagName in row) {
                    $('[name=' + tagName + ']', this.formEle).val(row[tagName]);
                }
                break;
            }
        }
    };
    
    function gridHandler(element, options) {
        this._init(element, options);
        $.data(this.gridEle, "plugin_data2html", this);
    }
    gridHandler.prototype = {
        defaults: {
            url: '',
            type: 'GET',
            pageSize: 0, //default results per page
            
            repeat: '.d2h_repeat',
            filter: '',
            page: '',
            
            beforeSend: function(){ return true; },
            rowComplete: function(current_row_index, row) {}, //called, after each row
            complete: function(row_count){} //called, once loop through data has finished
        },
        
        settings: null,
        groups: null,
        gridEle: null, // The DOM element
        
        _rows: null, //the data once loaded/received
        _visualData: null,
        
        _repeatHtml: '',       // template HTML string
        _repeatStart: 0,
        _selectorRepeat: '',
        _selectorRepeatParent: '',
        
        // The constructor
        _init: function(gridEle, options) {
            this.gridEle = gridEle;
            
            // settings
            var settings = _getElementOptions(
                gridEle, 
                'data-d2h-grid',
                this.defaults,
                options
            );
            if (!settings.repeat) {
                $.error("Data2Html can not initialize a gridHanfler on DOM object '" +
                    _getElementPath(gridEle) +
                    "': Option 'repeat' is missing."
                );
                return;
            }

            // Set internal selectors
            this.groups = {};
            _initCounter++;
            var iClassRepeat = 'i_d2h_repeat_' + _initCounter,
                iClassRepeatParent = iClassRepeat + '_parent';
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
            // clear
            this._clearHtml();
            
            // additional calls
            if (settings.filter) {
                this._initGroup('filter', settings.filter);
            }
            if (settings.page) {
                this._initGroup('page', settings.page);
            }
            
            // All ok, so save settings
            this.settings = settings;
        },
        _initGroup: function(groupName, groupSelector) {
            // Check arguments
            var groupOptions = null;
            if (!groupSelector) { return; }
            if ($.isArray(groupSelector)) {
                if (groupSelector.length < 1 || groupSelector.length > 2) {
                    $.error(
                        "Data2Html can not initialize group '" + groupName +
                        "'. When selector is array must have 1 or 2 items!"
                    );
                    return;
                }
                if (groupSelector.length >= 2) {
                    groupOptions = groupSelector[1];
                }
                groupSelector = groupSelector[0];
            }
            
            // To set up the group element
            var $group = this._selGroup(groupSelector);
            new formHandler($group[0], this, groupOptions);
            this.groups[groupName] = groupSelector;
        },
        
        _selGroup: function(groupSelector) {
            var $group;
            if (groupSelector.substr(0,1) === "#") {
                $group = $(groupSelector);
            } else {
                $group = $(groupSelector, this.gridEle);
            }
            if ($group.length !== 1) {
                $.error(
                    "Data2Html selector '" + groupSelector + 
                    "' of group '" + groupName +
                    "' has selected " + $group.length +
                    "  elements. Must select only one element!"
                );
                return;
            }
            return $group;
        },
        
        load: function(options) {
            if (!this.settings) {
                $.error(
                    "Data2Html: Can not call 'load' without bat initialization"
                );
                return;
            }
            var _settings = $.extend({}, this.settings, options);
            
            var url = _settings.url,
                pageStart = 1;
            if (this.groups.filter) {
                url += '&d2h_filter=' + this._selGroup(this.groups.filter).serialize()
                    .replace('&', '[,]');
            }
            if (this.groups.page) {
                if (_settings.add) {
                    pageStart = this._rows ? this._rows.length + 1 : 1;
                }
                url += '&d2h_page=pageStart=' + pageStart + '[,]' +
                    this._selGroup(this.groups.page).serialize().replace('&', '[,]');
            }
            url += '&d2h_sort=' +  $('.d2h_sort', this).val();
            var _this = this,
                _gridEle = this.gridEle;
            $.ajax({
                type: _settings.type,
                url: url,		
                dataType: "json", 
                beforeSend: function(){
                    var response = _settings.beforeSend.call(_this, 0);
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
                    if (_settings.add) {
                        Array.prototype.push.apply(
                            _this._rows,
                            _readRows(jsonData)
                        );
                    } else {
                        _this._rows = _readRows(jsonData);
                    }
                    _this._showRows();
                    _settings.complete.call(_this);
                },
                complete: function(msg){
                    _wait.hide();
                    $("*", _gridEle).removeClass(_globalDefaults.classFormChanged);
                }
            });
        },

        // Manage HTML
        _clearHtml: function () {
            var $parentContainer = $(this._selectorRepeatParent, this.gridEle);
            if ($parentContainer.length === 0) {
                $parentContainer = $(this.gridEle);
            }
            $(this._selectorRepeat, $parentContainer).remove();
        },
    
        _showRows: function () {
            this._clearHtml();
           
            var _settings = this.settings,
                rows = this._rows,
                rowsCount = rows.length;
            
            var $parentContainer = $(this._selectorRepeatParent, this.gridEle),
                lastItem = null;
            if ($parentContainer.length === 0) {
                $parentContainer = $(this.gridEle);
            }
            if (this._repeatStart > 0) {
                lastItem = $(
                    $parentContainer.children()[this._repeatStart - 1]
                );
            }
        
            // loop rows
            for (var i = 0; i < rowsCount; i++){
                var row = rows[i];
                var templateStr = this._repeatHtml;
                for (tagName in row) {
                    var pattern = new RegExp('\\$\\{' + tagName + '\\}', 'gi');	
                    templateStr = templateStr.replace(pattern, row[tagName]);
                }
                if (lastItem) {
                    lastItem.after(templateStr);
                } else {
                    $parentContainer.prepend(templateStr);
                }
                lastItem = $(
                    this._selectorRepeat + ':last',
                    $parentContainer
                );
                _settings.rowComplete.call(this, i, lastItem);
            }
        }
    };
    
    /**
     * Utilities
     */
    function _readRows(jsonData) {
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
            return rows;
        } else {
            return jsonData.rows;
        }
    };
    
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
    };
    function _getElementOptions(elem, attName, defaultOptions, options) {
        var optionsEle = null,
            dataD2h = $(elem).attr(attName);
        if (dataD2h) {
            try {
                var optionsEle = eval('[({' + dataD2h + '})]')[0];
            } catch(e) {
                $.error(
                    "Can not initialize a data2html handler: " +
                    "HTML attribute '" + attName + "' have a not valid js syntax on '" + 
                        _getElementPath(elem) + "'" 
                );
                return null;
            }
        }
        if (!optionsEle && !options) {
            $.error(
                "Can not initialize a data2html handler: " +
                "Options or HTML attribute '" + attName + "' are required on '" + 
                    _getElementPath(elem) + "'"
            );
            return;
        }
        return $.extend({}, defaultOptions, optionsEle, options);
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
            if (typeof arguments[0] === "string" && $.isPlainObject(arguments[1])) {
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
        
        this.each(function() {
            if (!$.data(this, "plugin_data2html") ) {
                if ($(this).attr('data-d2h-grid')) {
                    new gridHandler(this, _options);
                } else if ($(this).attr('data-d2h-form')) {
                    new formHandler(this, null, _options);
                }
            }
            if (_method) {
                var thisObj = $.data(this, "plugin_data2html");
                thisObj[_method].call(thisObj, _options);
            }
        });
        return this; // chain jQuery functions
    };
})(jQuery);
