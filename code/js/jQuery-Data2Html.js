/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
(function( $ ){
    var _defaults = {
        url: '',				
        params: '',
        type: 'GET',
        offSet: 0, //indicates the offset when deleting dynamic rows. 
        pageSize: 0, //default results per page
        elementData: 'table tbody',
        elementWaiting: '.d2h_waiting',
        beforeSend: function(){},
        complete: function(row_count){}, //called, once loop through data has finished
        rowComplete: function(current_row_index, row){}//called, after each row 
    };
	var methods = {
        init: function(options){
            var _options = null;
            if (options) {
                _options = $.extend(
                    $.extend({}, _defaults), // to preserve defaults
                    options
                );
            }
            var response = this.each( function(){
                var $this = $(this),
                    dataObj = $this.data('data2html'),
                    $thisData = null;
                if ( dataObj ) { // Object 'data2html' already exists
                    $thisData = $(dataObj.elementData, $this);
                } else { // Create object 'data2html'
                    if (_options === null) {
                        $.error("The options are required to first start");
                        return;
                    }
                    dataObj = $.extend({}, _options);
                    dataObj = $.extend({
                        _dataArray: null, //the data once loaded/received
                        _tpl: '',       // template HTML string
                        rowCount: 0,    // number of total result rows
                        _listHeader: null,
                        _pageIndex: 0,
                        _indexCols: {}
                    }, dataObj);
                    $thisData = $(dataObj.elementData, $this);
                    if (dataObj.offSet > 0){
                        var listHeader = $thisData.children(
                            ':lt('+dataObj.offSet+')'
                        ).detach(); 
                        if (listHeader.size() > 0){
                            dataObj._listHeader = listHeader;
                        } 
                    }
                    dataObj._tpl = $thisData.html();
                    $this.data('data2html', dataObj); // set dataObj
                }
                _removeAll($thisData, dataObj._listHeader);
            });
            if (response.length == 0) {
                throw "Data2Html: Can not initialize, DOM object not found.";
            }
            return response;
 		},
        load: function( options ) {
            if ( options ) {
                $.extend( $(this).data('data2html'), options );
            }
            return this.each(function() {
                var $this = $(this);
                    _dataObj = $this.data('data2html');
                if (!_dataObj) {
                    throw "Data2Html: Can not call 'load' without first initialize DOM: " +
                        _getElementDesc(this);
                }
                $.ajax({
                    type: _dataObj.type,
                    url: _dataObj.url + "?" + _dataObj.params,		
                    dataType: "json", 
                    beforeSend: function(){
                        if (_dataObj.elementWaiting) {
                            $(_dataObj.elementWaiting, $this).show();
                        }
                        _dataObj.beforeSend.call(this, 0);
                    },
                    success: function(jsonData){
                        _dataObj._dataArray = jsonData;
                        _dataObj.rowCount = jsonData.rows.length;
                        var _indexCols = null;
                        if (jsonData.dataRows) {
                            _indexCols = {};
                            for (var i=0, len= jsonData.dataTypes.length; i <len; i++) {
                                _indexCols[jsonData.dataTypes[i]] = i;
                            }
                            
                        }
                        _dataObj._indexCols = _indexCols;
                        _loopRows.call($this);
                    },//end success
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
                        if (_dataObj.elementWaiting) {
                            $(_dataObj.elementWaiting, $this).hide();
                        }
                    }
                });
            });
        }
    };
    function _getElementDesc(elem) {
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
    function _removeAll($thisData, listHeader) {
        $thisData.empty();
        $thisData.append(listHeader);
    }
	function _loopRows() {
        var dataObj = $(this).data('data2html'),
            _indexCols = dataObj._indexCols,
            dataArray = dataObj._dataArray,
            rowCount = dataObj.rowCount,
            resultsPP = (dataObj.pageSize ? dataObj.pageSize : rowCount),
            startIndex = dataObj._pageIndex * resultsPP; 
        var nextSet = startIndex + resultsPP;

        // append header
        $thisData = $(dataObj.elementData, this);
        _removeAll($thisData, dataObj._listHeader);
		$('.pageIndex').val(dataObj._pageIndex + 1);
        
        // loop rows
		for (var i=startIndex; (i<rowCount && i<nextSet); i++){
			var row = dataArray.rows[i];
			var templateStr = dataObj._tpl;
            if (_indexCols) { // by array width cols declaration
                for (var tagName in _indexCols) {
                    var value = row[_indexCols[tagName]];
                    var pattern = new RegExp('\{'+tagName+'\}','gi');		
                    templateStr = templateStr.replace(pattern, value);
                }
            } else {
                for (tagName in row) {
                    var pattern = new RegExp('\{'+tagName+'\}','gi');		
                    templateStr = templateStr.replace(pattern, row[tagName]);
                }
            }
			$thisData.append(templateStr);
			dataObj.rowComplete.call(this,
                i, $thisData.children(":last"));
		}
        dataObj.complete.call(this,startIndex+resultsPP);
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
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method "' +  method + '" does not exist on jQuery.data2html' );
        }
    };
})( jQuery );
