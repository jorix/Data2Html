/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
(function ($) {
    var _defaults = {
        url: '',
        type: 'GET',
        pageSize: 0, //default results per page
        classFormChanged: 'd2h_formChanged',
        
        repeat: '.d2h_repeat',
        filter: '',
        page: '',
        
        beforeSend: function(){ return true; },
        complete: function(row_count){}, //called, once loop through data has finished
        rowComplete: function(current_row_index, row) {} //called, after each row
    };
    var _initCounter = 0;

    // The actual plugin constructor
    function data2html(element, options) {
            this._ele = element;
            
            this._init(options);
    }

    data2html.prototype = {
        settings: null,
        groups: {},
        _ele: null, // The DOM element
        
        _rows: null, //the data once loaded/received
        _dataTypes: null,
        
        _repeatHtml: '',       // template HTML string
        _startIndex: 0,
        
        
        _selectorRepeat: '',
        _selectorWaiting: '',
        _selectorRepeatParent: '',
        _repeatHtml: '', // Template
        _repeatStart: 0,
        
        _init: function(options) {
            var ele = this._ele;
            
            // settings
            var optionsEle = null,
                dataD2h = $(ele).attr('data-d2h');
            if (dataD2h) {
                try {
                    var optionsEle = eval('[({' + dataD2h + '})]')[0];
                } catch(e) {
                    $.error(
                        "Can not initialize Data2Html: HTML Attribute 'data-d2h' on '" + 
                        _getElementPath(ele) +
                        "' have a not valid js syntax." 
                    );
                    return;
                }
            } else if (options === null) {
                $.error("Options are required to initialize a DOM object '" +
                    _getElementPath(ele) +
                    "'.");
                return;
            }
            var settings = $.extend({}, _defaults, optionsEle, options);
            if (!settings.repeat) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(ele) +
                    "': Option 'repeat' is missing."
                );
                return;
            }
            
            // Set internal selectors
            _initCounter++;
            var iClassRepeat = 'i_d2h_repeat_' + _initCounter,
                iClassRepeatParent = iClassRepeat + '_parent',
                iClassWaiting = iClassRepeat + '_waiting';
            this._selectorRepeat = '.' + iClassRepeat;
            this._selectorWaiting = '.' + iClassWaiting;
            this._selectorRepeatParent = '.' + iClassRepeatParent;

            // Check repeat selector
            var $itemRepeat = $(settings.repeat, ele);
            if ($itemRepeat.length == 0) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(ele) +
                    "': Does not contain a '" +
                    settings.repeat +
                    "' selector."
                );
                return;
            }
            if ($itemRepeat.length > 1) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(ele) +
                    "': Contains more than one '" +
                    settings.repeat +
                    "' selector."
                );
                return;
            }

            // Mark repeat and parent elements.
            $itemRepeat.addClass(iClassRepeat);
            var $parentContainer = $itemRepeat.parent();
            if ($(this._selectorRepeatParent, ele).length > 0) {
                $.error("Data2Html can not initialize DOM object '" +
                    _getElementPath(ele) +
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
                this._initGroup('filter', settings.filter, settings);
            }
            if (settings.page) {
                this._initGroup('page', settings.page, settings);
            }
            
            // All ok, so save settings
            this.settings = settings;
        },
        _initGroup: function(groupName, groupSelector, groupOptions, settings) {
            // Check arguments
            if (!groupSelector) { return; }
            if ($.isArray(groupSelector)) {
                if (groupSelector.length < 1 || groupSelector.length > 2) {
                    $.error(
                        "Data2Html can not initialize group '" + groupName +
                        "'. If selector is array must have 1 or 2 items!"
                    );
                    return;
                }
                if (groupSelector.length >= 2) {
                    groupOptions = groupSelector[1];
                }
                groupSelector = groupSelector[0];
            }
            
            // To set up the group element
            var $group,
                _ele = this._ele,
                groupDataObj = {selector: groupSelector};
            if (groupSelector.substr(0,1) === "#") {
                $group = $(groupSelector);
            } else {
                $group = $(groupSelector, _ele);
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
            this.groups[groupName] = groupDataObj;
            
            // Actions
            var _this = this;
            if (groupOptions) {
                if (groupOptions.actions) {
                    var _actions = groupOptions.actions;
                    groupDataObj.actions = _actions;
                    $('[data-d2h-on]', _ele).each(function() {
                        var $this = $(this),
                            _onAction = $this.attr('data-d2h-on').split(':');
                        if (_onAction.length === 2) {
                            $this.on(_onAction[0], function(event) {
                                _actions[_onAction[1]].call(this, event, _this);
                            });
                        }
                    });
                }
            }
            $group.change(function() {
                $group.addClass(classFormChanged);
                _this.load();
            });
        },
        
        load: function(options) {
            if (!this.settings) {
                $.error(
                    "Data2Html: Can not call 'load' without bat initialization"
                );
                return;
            }
            var _settings = $.extend({}, this.settings, options);
            
            var url = _settings.url;
            if (this.groups.filter) {
                url += '&d2h_filter=' +  $(this.groups.filter, this).serialize()
                    .replace('&', '[,]');
            }
            if (this.groups.page) {
                url += '&d2h_page=' +  $(this.groups.page, this).serialize()
                    .replace('&', '[,]');
            }
            url += '&d2h_sort=' +  $('.d2h_sort', this).val();
            var _this = this;
            $.ajax({
                type: _settings.type,
                url: url,		
                dataType: "json", 
                beforeSend: function(){
                    var response = _settings.beforeSend.call(_this, 0);
                    if (response !== false) {
                        if (_this._selectorWaiting) {
                            $(_this._selectorWaiting, _this).show();
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
                        alert('An error "' + errorThrown + '", status "' + textStatus + '" occurred during loading data: ' + XMLHttpRequest.responseText);
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
                        _this._rows = rows;
                    } else {
                        _this._rows = jsonData.rows;
                    }
                    _this._dataTypes = dataTypes;
                    
                    _this._showRows(true);
                },
                complete: function(msg){
                    if (_this._selectorWaiting) {
                        $(_this._selectorWaiting, _this).hide();
                        
                    }
                }
            });
        },

        // Manage HTML
        _clearHtml: function () {
            var $parentContainer = $(this._selectorRepeatParent, this._ele);
            $(this._selectorRepeat, $parentContainer).remove();
            this._startIndex = 0;
        },
    
        _showRows: function (clear) {
            if (clear) {
                this._clearHtml();
            }
            
            var _settings = this.settings,
                rows = this._rows,
                rowsCount = rows.length;
            var resultsPP = (this._pageSize ? this._pageSize : rowsCount),
                startIndex = this._startIndex,
                nextSet = startIndex + resultsPP;
            
            
            var $parentContainer = $(this._selectorRepeatParent, this._ele),
                lastItem = null;
            if (this._repeatStart > 0) {
                lastItem = $(
                    $parentContainer.children()[this._repeatStart - 1]
                );
            }
            if (startIndex) { // add lines
                lastItem = $(
                    this._selectorRepeat + ':last',
                    $parentContainer
                );
            }
        
            // loop rows
            for (var i = 0; i < rowsCount && i + startIndex < nextSet; i++){
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
            this._startIndex = i + startIndex;
            _settings.complete.call(this, this._startIndex);
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
     * Declare plugin
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
                $.data(this, "plugin_data2html", new data2html(this, _options) );
            }
            if (_method) {
                var thisObj = $.data(this, "plugin_data2html");
                thisObj[_method].call(thisObj, _options);
            }
        });
        return this; // chain jQuery functions
    };
})(jQuery);
