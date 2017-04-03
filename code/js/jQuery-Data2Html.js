/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
var data2html, d2h;
(function($) {
    var _initCounter = 0;
    var _defaults = {
        url: '',
        type: 'GET',
        pageSize: 0, //default results per page
        classRepeat: 'd2h_repeat',
        classWaiting: 'd2h_waiting',
        
        beforeSend: function(){ return true; },
        complete: function(row_count){}, //called, once loop through data has finished
        rowComplete: function(current_row_index, row) {} //called, after each row 
    };
    
    /**
     * Methods with scope a element
     */
    // Operational actions
    var _init = function(options){
        return this.each( function(){
            var dataObj = $(this).data('data2html');
            if (!dataObj) { // Create object 'data2html'
                var optionsEle = null;
                var dataD2h = $(this).attr('data-d2h');
                if (dataD2h) {
                    try {
                        var optionsEle = eval('[({' + dataD2h + '})]')[0];
                    } catch(e) {
                        $.error(
                            "Can not initialize Data2Html: HTML Attribute 'data-d2h' on '" + 
                            _getElementPath(this) +
                            "' have a not valid js syntax." 
                        );
                        return;
                    }
                } else if (options === null) {
                    $.error("Options are required to initialize a DOM object '" +
                        _getElementPath(this) +
                        "'.");
                    return;
                }
                var optionsItems = $.extend({}, 
                    _defaults,
                    optionsEle,
                    options
                );
                if (!optionsItems.repeat) {
                    $.error("Data2Html can not initialize DOM object '" +
                        _getElementPath(this) +
                        "': Option 'repeat' is missing."
                    );
                    return;
                }
                
                _initCounter++;
                var iClassRepeat = 'i_d2h_repeat_' + _initCounter,
                    iClassRepeatParent = iClassRepeat + '_parent',
                    iClassWaiting = iClassRepeat + '_waiting';
                dataObj = $.extend({}, optionsItems, {
                    _: {
                        rows: null, //the data once loaded/received
                        repeatHtml: '',       // template HTML string
                        pageIndex: 0,
                        selectorRepeat: '.' + iClassRepeat,
                        selectorWaiting: '.' + iClassWaiting,
                        selectorRepeatParent: '.' + iClassRepeatParent
                    }
                });
                var dataObj_ = dataObj._;

                $itemRepeat = $(dataObj.repeat, this);
                if ($itemRepeat.length == 0) {
                    $.error("Data2Html can not initialize DOM object '" +
                        _getElementPath(this) +
                        "': Does not contain a '" +
                        dataObj.repeat +
                        "' selector."
                    );
                    return;
                }
                if ($itemRepeat.length > 1) {
                    $.error("Data2Html can not initialize DOM object '" +
                        _getElementPath(this) +
                        "': Contains more than one '" +
                        dataObj.repeat +
                        "' selector."
                    );
                    return;
                }

                // Mark repeat and parent elements.
                $itemRepeat.addClass(iClassRepeat);
                var $parentContainer = $itemRepeat.parent();
                if ($(dataObj_.selectorRepeatParent, this).length > 0) {
                    $.error("Data2Html can not initialize DOM object '" +
                        _getElementPath(this) +
                        "': Contains selector '" +
                        dataObj_.selectorRepeatParent +
                        "' which is for internal use only!"
                    );
                    return;
                }
                $parentContainer.addClass(iClassRepeatParent);
                if ($(dataObj_.selectorRepeat, $parentContainer).length > 1) {
                    $.error("Data2Html can not initialize DOM object '" +
                        _getElementPath($parentContainer[0]) +
                        "': Contains more than one '" +
                        dataObj_.selectorRepeat +
                        "' selector."
                    );
                    return;
                }
                
                // Set template
                dataObj_.repeatHtml = $itemRepeat.get(0).outerHTML;
                dataObj_.repeatStart = $parentContainer.children().index($itemRepeat);
                // set dataObj
                $(this).data('data2html', dataObj); 
                // clear
                _clearHtml.call(this);
                
                // additional calls
                if (optionsItems.filter) {
                    _setGroup.call(this, 'filter', optionsItems.filter);
                }
                if (optionsItems.page) {
                    _setGroup.call(this, 'page', optionsItems.page);
                }
            }
        });
    };
    
    var _setGroup = function(groupName, groupSelector, groupOptions) {
        // Check arguments
        if (!groupSelector) { return; }
        var dataObj = $(this).data('data2html');
        if ($.isArray(groupSelector)) {
            if (groupSelector.length < 1) {
                return;
            }
            if (groupSelector.length >= 2) {
                groupOptions = groupSelector[1];
            }
            groupSelector = groupSelector[0];
        }
        
        // To set up
        dataObj._[groupName] = {selector: groupSelector};
        var $group;
        if (groupSelector.substr(0,1) === "#") {
            $group = $(groupSelector);
        } else {
            $group = $(groupSelector, this);
        }
        
        // Actions
        var _this = this;
        if (groupOptions) {
            if (groupOptions.actions) {
                for (var key in groupOptions.actions) {
                    
                }
            }
        }
        $group.change(function() {
            _load.call(_this);
        });
    };

	var _load = function(options) {
        var _dataObj = $(this).data('data2html');
        if (options) {
            if (options._) { // Preserve internal options
                delete options._;
            }
            $.extend(_dataObj, options);
        }
        if (!_dataObj) {
            $.error(
                "Data2Html: Can not call 'load' without first initialize DOM '" +
                _getElementPath(this)+
                "' object"
            );
            return;
        }
        
        var url = _dataObj.url,
            _dataObj_= _dataObj._;
        if (_dataObj_.filter) {
            url += '&d2h_filter=' +  $(_dataObj_.filter.selector, this).serialize()
                .replace('&', '[,]');
        }
        if (_dataObj_.page) {
            url += '&d2h_page=' +  $(_dataObj_.page.selector, this).serialize()
                .replace('&', '[,]');
        }
        url += '&d2h_sort=' +  $('.d2h_sort', this).val();
        var _this = this;
        $.ajax({
            type: _dataObj.type,
            url: url,		
            dataType: "json", 
            beforeSend: function(){
                var response = _dataObj.beforeSend.call(_this, 0);
                if (response !== false) {
                    if (_dataObj_.selectorWaiting) {
                        $(_dataObj_.selectorWaiting, _this).show();
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
                _dataObj_.dataTypes = dataTypes;
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
                    _dataObj_.rows = rows;
                } else {
                    _dataObj_.rows = jsonData.rows;
                }
                _showRows.call(_this);
            },
            complete: function(msg){
                if (_dataObj_.selectorWaiting) {
                    $(_dataObj_.selectorWaiting, _this).hide();
                }
            }
        });
    };

    // Manage HTML
    function _clearHtml() {
        var dataObj = $(this).data('data2html'),
            $parentContainer = $(dataObj._.selectorRepeatParent, this);
        $(dataObj._.selectorRepeat, $parentContainer).remove();
    }
    
	function _showRows() {
        var dataObj = $(this).data('data2html'),
            dataObj_ = dataObj._,
            rows = dataObj_.rows,
            rowsCount = rows.length;
        var resultsPP = (dataObj.pageSize ? dataObj.pageSize : rowsCount),
            startIndex = dataObj_.pageIndex * resultsPP,
            nextSet = startIndex + resultsPP;
        
        _clearHtml.call(this);
		$(dataObj.selectorPageIndex).val(dataObj_.pageIndex + 1);
        
        var $parentContainer = $(dataObj_.selectorRepeatParent, this),
            lastItem = null;
        if (dataObj_.repeatStart > 0) {
            lastItem = $(
                $parentContainer.children()[dataObj_.repeatStart - 1]
            );
        }
        
        // loop rows
		for (var i=startIndex; (i<rowsCount && i<nextSet); i++){
			var row = rows[i];
			var templateStr = dataObj_.repeatHtml;
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
                dataObj_.selectorRepeat + ':last',
                $parentContainer
            );
			dataObj.rowComplete.call(this, i, lastItem);
		}
        dataObj.complete.call(this, startIndex + resultsPP);
        return this;
	}
    
    /**
     * Utilities
     */
    function _getElementPath($elem) {
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
     * Do the class
     */
    var _d2h = function(selector) {
        this.selector = selector;
    };
    _d2h.prototype = {
        init: function(options) {
            var $elem = $(this.selector);
            if ($elem.length == 0) {
                $.error(
                    "Data2Html: Can not find a DOM object with the selector '" +
                    this.selector + "'."
                );
                return this;
            }
            _init.call($elem, options);
            return this;
        },
        filter: function(filterSelector, options) {
            $(this.selector).each(function() {
                _setGroup.call(this, 'filter', filterSelector, options);
            });
            return this;
        },
        page: function(pageSelector, options) {
            $(this.selector).each(function() {
                _setGroup.call(this, 'page', pageSelector, options);
            });
            return this;
        },
        load: function(options) {
            $(this.selector).each(function() {
                _load.call(this, options);
            });
            return this;
        }
    };
    d2h = data2html = function(selector, options) {
        var obj = new _d2h(selector);
        obj.init(options);
        return obj;
    };
})(jQuery);
