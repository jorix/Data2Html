/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
(function( $ ){
    var _initCounter = 0;
    var _defaults = {
        url: '',				
        params: '',
        type: 'GET',
        pageSize: 0, //default results per page
        classRepeat: 'd2h_repeat',
        classWaiting: 'd2h_waiting',        
        selectorPageIndex: '.pageIndex',
        
        beforeSend: function(){},
        complete: function(row_count){}, //called, once loop through data has finished
        rowComplete: function(current_row_index, row) {}, //called, after each row 
        
        _classRepeatParent: 'd2h_repeatParent'
    };
	var methods = {
        init: function(options){
            var _options = $.extend(
                {},
                _defaults, // to preserve defaults
                options
            );
            var response = this.each( function(){
                var dataObj = $(this).data('data2html');
                if (!dataObj) { // Create object 'data2html'
                    if (options === null) {
                        $.error("Options are required to initialize a DOM object '" +
                            _getElementPath(this) +
                            "'.");
                        return;
                    }
                    _initCounter++;
                    var classRepeatParent = 'i_' + _options.classRepeat +
                                            'Parent_' + _initCounter;
                    dataObj = $.extend({}, _options, {
                            _rows: null, //the data once loaded/received
                            _repeatHtml: '',       // template HTML string
                            _pageIndex: 0,
                            _selectorWaiting: (_options.classWaiting ?
                                '.' + _options.classWaiting : ''),
                            _selectorRepeat: '.' + _options.classRepeat,
                            _selectorRepeatParent: '.' + classRepeatParent
                    });
                    $itemRepeat = $(dataObj._selectorRepeat + ':first', this);
                    if ($itemRepeat.length == 0) {
                        $.error("Data2Html: Can not initialize, DOM object '" +
                            _getElementPath(this) +
                            "' does not contain a '" +
                            dataObj._selectorRepeat +
                            "' selector."
                        );
                        return;
                    }
                    if ($(dataObj._selectorRepeat, this).length > 1) {
                        $.error("Data2Html: Can not initialize, DOM object '" +
                            _getElementPath(this) +
                            "' contains more than one '" +
                            dataObj._selectorRepeat +
                            "' selector."
                        );
                        return;
                    }

                    // Mark then parent.
                    var $parentContainer = $itemRepeat.parent();
                    if ($(dataObj._selectorRepeatParent, this).length > 0) {
                        $.error("Data2Html: Can not initialize, DOM object '" +
                            _getElementPath(this) +
                            "' contains selector '" +
                            dataObj._selectorRepeatParent +
                            "' which is for internal use only!"
                        );
                        return;
                    }
                    $parentContainer.addClass(classRepeatParent);
                    if ($(dataObj._selectorRepeat, $parentContainer).length > 1) {
                        $.error("Data2Html: Can not initialize, DOM object '" +
                            _getElementPath($parentContainer[0]) +
                            "' contains more than one '" +
                            dataObj._selectorRepeat +
                            "' selector."
                        );
                        return;
                    }
                    
                    // Set template
                    dataObj._repeatHtml = $itemRepeat.get(0).outerHTML;
                    dataObj._repeatStart = $parentContainer.children().index($itemRepeat);
                    $(this).data('data2html', dataObj); // set dataObj
                }
                _clearHtml.call(this);
            });
            return response;
 		},
        
        load: function(options) {
            return this.each(function() {
                var _this = this;
                    _dataObj = $(this).data('data2html');
                if (options) {
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
                $.ajax({
                    type: _dataObj.type,
                    url: _dataObj.url + "?" + _dataObj.params,		
                    dataType: "json", 
                    beforeSend: function(){
                        if (_dataObj._selectorWaiting) {
                            $(_dataObj._selectorWaiting, _this).show();
                        }
                        _dataObj.beforeSend.call(_this, 0);
                    },
                    success: function(jsonData){
                        var dataTypes = jsonData.dataTypes,
                            rowsCount = 0;
                        _dataObj._dataTypes = dataTypes;
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
                            _dataObj._rows = rows;
                        } else {
                            _dataObj._rows = jsonData.rows;
                        }
                        _showRows.call(_this);
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
                    complete: function(msg){
                        if (_dataObj._selectorWaiting) {
                            $(_dataObj._selectorWaiting, _this).hide();
                        }
                    }
                });
            });
        }
    };
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
    function _clearHtml() {
        var dataObj = $(this).data('data2html'),
            $parentContainer = $(dataObj._selectorRepeatParent, this);
        $(dataObj._selectorRepeat, $parentContainer).remove();
    }
	function _showRows() {
        var dataObj = $(this).data('data2html'),
            rows = dataObj._rows,
            rowsCount = rows.length;
        var resultsPP = (dataObj.pageSize ? dataObj.pageSize : rowsCount),
            startIndex = dataObj._pageIndex * resultsPP,
            nextSet = startIndex + resultsPP;
        
        _clearHtml.call(this);
		$(dataObj.selectorPageIndex).val(dataObj._pageIndex + 1);
        
        var $parentContainer = $(dataObj._selectorRepeatParent, this),
            lastItem = null;
        if (dataObj._repeatStart > 0) {
            lastItem = $(
                $parentContainer.children()[dataObj._repeatStart - 1]
            );
        }
        
        // loop rows
		for (var i=startIndex; (i<rowsCount && i<nextSet); i++){
			var row = rows[i];
			var templateStr = dataObj._repeatHtml;
            for (tagName in row) {
                var pattern = new RegExp('\{'+tagName+'\}','gi');		
                templateStr = templateStr.replace(pattern, row[tagName]);
            }
            if (lastItem) {
                lastItem.after(templateStr);
            } else {
                $parentContainer.append(templateStr);
            }
            lastItem = $(
                dataObj._selectorRepeat + ':last',
                $parentContainer
            );
			dataObj.rowComplete.call(this, i, lastItem);
		}
        dataObj.complete.call(this, startIndex + resultsPP);
	}
    /**
     * Method calling logic
     */
	$.fn.data2html = function(method) {
        if ( methods[method] ) {
            return methods[ method ].apply(
                this, Array.prototype.slice.call(arguments, 1)
            );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply(this, arguments);
        } else {
            $.error( 'Method "' +  method + '" does not exist on jQuery.data2html' );
        }
    };
})( jQuery );
