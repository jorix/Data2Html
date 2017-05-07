/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
(function ($) {

    var _initCounter = 0,
        _waitCounter = 0; // Only one wait
    
    var _formDefaults = {
        url: '',
        type: 'POST',
        classChanged: 'd2h_formChanged', // Only set at initial declaration
        classWaiting: 'd2h_waiting',
        
        afterChange: function() { }
    };
    function formHandler(element, container, options) {
        this._formInit(element, container, options);
    }
    formHandler.prototype = {
        defaults: {
        },
        formSettings: null,
        formEle: null, // The DOM element
        _parent: null,
        
        _formInit: function(formEle, _parent, formOptions) {
            this.formEle = formEle;
            this._parent = _parent;
            var settings = $.extend({}, this.defaults, formOptions);
            
            if (settings.actions) {
                var _actions = settings.actions;
                var _fnAction = function() {
                    var $this = $(this),
                        _onAction = $this.attr('data-d2h-on').split(':');
                    if (_onAction.length === 2) {
                        $this.on(_onAction[0], function(event) {
                            console.log(_onAction.join('->'));
                            _actions[_onAction[1]].call(_parent, $this, event);
                            return false;
                        });
                    }
                };
                // all sub-elements
                $('[data-d2h-on]', formEle).each(function() {
                    _fnAction.call(this);
                });
                // self element
                if (formEle.attr('data-d2h-on')) {
                    _fnAction.call(formEle);
                }
            }
            formEle.change(function() {
                formEle.addClass(_parent._classFormChanged);
            });
            this.formSettings = settings;
        }
    };
    
    function gridHandler(element, options) {
        this._init(element, options);
    }
    gridHandler.prototype = {
        defaults: {
            url: '',
            type: 'GET',
            pageSize: 0, //default results per page
            classFormChanged: 'd2h_formChanged', // Only set at initial declaration
            classWaiting: 'd2h_waiting',
            
            repeat: '.d2h_repeat',
            filter: '',
            page: '',
            
            beforeSend: function(){ return true; },
            rowComplete: function(current_row_index, row) {}, //called, after each row
            complete: function(row_count){} //called, once loop through data has finished
        },
        
        settings: null,
        groups: null,
        _ele: null, // The DOM element
        
        _rows: null, //the data once loaded/received
        _dataTypes: null,
        
        _classFormChanged: '',
        
        _repeatHtml: '',       // template HTML string
        _repeatStart: 0,
        _selectorRepeat: '',
        _selectorRepeatParent: '',
        
        // The constructor
        _init: function(gridEle, options) {
            this._ele = gridEle;
            this.groups = {};
            
            // settings
            var optionsEle = null,
                dataD2h = $(gridEle).attr('data-d2h');
            if (dataD2h) {
                try {
                    var optionsEle = eval('[({' + dataD2h + '})]')[0];
                } catch(e) {
                    $.error(
                        "Can not initialize Data2Html: HTML Attribute 'data-d2h' on '" + 
                        _getElementPath(gridEle) +
                        "' have a not valid js syntax." 
                    );
                    return;
                }
            } else if (options === null) {
                $.error("Options are required to initialize a DOM object '" +
                    _getElementPath(gridEle) +
                    "'.");
                return;
            }
            var settings = $.extend({}, this.defaults, optionsEle, options);
            if (!settings.repeat) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(gridEle) +
                    "': Option 'repeat' is missing."
                );
                return;
            }
            
            // Set internal selectors
            _initCounter++;
            var iClassRepeat = 'i_d2h_repeat_' + _initCounter,
                iClassRepeatParent = iClassRepeat + '_parent';
            this._selectorRepeat = '.' + iClassRepeat;
            this._selectorRepeatParent = '.' + iClassRepeatParent;
            this._classFormChanged = settings.classFormChanged;

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
            new formHandler(this._selGroup(groupSelector), this, groupOptions);
            this.groups[groupName] = groupSelector;
        },
        
        _selGroup: function(groupSelector) {
            if (groupSelector.substr(0,1) === "#") {
                $group = $(groupSelector);
            } else {
                $group = $(groupSelector, this._ele);
            }
            if ($group.length != 1) {
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
        loadNext: function(options) {
            
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
                _gridEle = this._ele;
            $.ajax({
                type: _settings.type,
                url: url,		
                dataType: "json", 
                beforeSend: function(){
                    var response = _settings.beforeSend.call(_this, 0);
                    if (response !== false) {
                        if (_settings.classWaiting) {
                            _waitCounter++;
                            $('.' + _settings.classWaiting, _gridEle).show();
                        }
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
                    var dataTypes = jsonData.dataTypes,
                        rowsCount = 0;
                    if (jsonData.rowsAsArray) {
                        var rows = [],
                            indexCols = {};
                        for (var i = 0, len = dataTypes.length; i < len; i++) {
                            indexCols[dataTypes[i]] = i;
                        }
                        var rowsAsArray = jsonData.rowsAsArray;
                        rowsCount = rowsAsArray.length;
                        for (var i = 0; i < rowsCount; i++) {
                            var item = rowsAsArray[i];
                            for (var tagName in indexCols) {
                                var row = {};
                                row[tagName] = item[indexCols[tagName]];
                                var pattern = new RegExp('\{'+tagName+'\}','gi');		
                                templateStr = templateStr.replace(pattern, value);
                            }
                            rows.push(row);
                        }
                    } else {
                        rows = jsonData.rows;
                    }
                    if (_settings.add) {
                        Array.prototype.push.apply(_this._rows, rows);
                    } else {
                        _this._rows = rows;
                    }
                    _this._dataTypes = dataTypes;
                    _this._showRows();
                    _settings.complete.call(_this);
                },
                complete: function(msg){
                    if (_settings.classWaiting) {
                        _waitCounter--;
                        if (_waitCounter <=0) {
                            _waitCounter = 0;
                            $('.' + _settings.classWaiting, _gridEle).hide();
                        }
                    }
                    $("*", _gridEle).removeClass(_this._classFormChanged);
                }
            });
        },

        // Manage HTML
        _clearHtml: function () {
            var $parentContainer = $(this._selectorRepeatParent, this._ele);
            if ($parentContainer.length === 0) {
                $parentContainer = $(this._ele);
            }
            $(this._selectorRepeat, $parentContainer).remove();
        },
    
        _showRows: function () {
            this._clearHtml();
           
            var _settings = this.settings,
                rows = this._rows,
                rowsCount = rows.length;
            
            var $parentContainer = $(this._selectorRepeatParent, this._ele),
                lastItem = null;
            if ($parentContainer.length === 0) {
                $parentContainer = $(this._ele);
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
                    var pattern = new RegExp('\{'+tagName+'\}','gi');		
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
    function _getElementPath($elem) {
        if ($elem === undefined) {
            return "undefined";
        }
        var selectorArr = [
            $elem.tagName.toLowerCase() +
            ($elem.id ? '#' + $elem.id : '')
        ];
        $($elem).parents().map(
            function() {
                selectorArr.push(
                    this.tagName.toLowerCase() +
                    (this.id ? '#' + this.id : '')
                );
            }
        );
        return selectorArr.reverse().join(">");
    };
    
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
                $.data(this, "plugin_data2html", new gridHandler(this, _options) );
            }
            if (_method) {
                var thisObj = $.data(this, "plugin_data2html");
                thisObj[_method].call(thisObj, _options);
            }
        });
        return this; // chain jQuery functions
    };
})(jQuery);
