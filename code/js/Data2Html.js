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
        resultsPerPage: 0, //default results per page, if paginationNav is active
        loadAnim: true, //show loading animation and removed after loading has finished
        beforeSend: function(){},
        complete: function(row_count){}, //called, once loop through xml has finished
        rowComplete: function(current_row_index, row){}//called, after each row 
    };
	var methods = {
        init: function(_options){
            var _data =$.extend({
                dataArray: null,        //the data once loaded/received
                tpl: '',                //template HTML string if no template is provided in the HTML.
                rowCount: 0,       //nr of total result rows
                _pageIndex: 0
            }, _defaults);
            if ( _options ) { 
                $.extend(_data, _options);
            }
            return this.each( function(){
                var $this = $(this);
				data = $this.data('data2html');	   	
                if ( !data ) {	
				//if an offset is specified, then we look for a list header
					//the container element can have a list header which can be specified with the lt(index) which counts
					//back to 0. 
                    if (_data.offSet > 0){
                        var listHeader =  $this.children(':lt('+_data.offSet+')').detach(); 
                        if (listHeader.size()>0){
                            _data.listHeader = listHeader;
                        } 
                    }
                    _data.tpl = $this.html();
                    //construct pagination nav if a selector has been specified
                    if (_data.paginationNav != '') {
                        _constructPagination.call($this,_data.paginationNav);
                        $('#selectResultsPP').val(_data.resultsPerPage);
                        
                    }
                    $this.data('data2html', $.extend({}, _data));
                }
                _removeAll($this);
            });
 		},
        removeAll: function (){
            return this.each(function(){
                _removeAll($(this))
            });
        },
        load: function( options ) {
            if ( options ) {
                $.extend( $(this).data('data2html'), options );
            }
            return this.each(function() {
                var $this = $(this);
                    _data = $this.data('data2html');
                $.ajax({
                    type: _data.type,
                    url: _data.url + "?" + _data.params,		
                    dataType: "json", 
                    beforeSend: function(){
                        _data.beforeSend.call(this, 0);
                    },
                    success: function(jsonData){
                        _data.dataArray = jsonData;
                        _data.rowCount = jsonData.rows.length;
                        for (var i=0, len= jsonData.cols.length; i <len; i++) {
                            _indexCols[jsonData.cols[i]] = i;
                        }
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
                        if (_data.loadAnim){
                            $('.loadAnimRemoveMe').remove();
                        }
                    }
                });	//end ajax
                //alert("in "+rowIndex )
            }); //end this.each
        } //end reload
    };	

	function _constructPagination(sel){
		var $this = $(this);
		var str = '<div id="pagNav">'; 
		str += '<span class="ui-icon ui-icon-seek-first"></span><span class="ui-icon ui-icon-seek-prev"></span>';
		str += '<span><input class="pageIndex ui-widget-content ui-corner-all"  type="text" size="2"/> /</span> <span class="totalResultPages"></span>';
		str += '<span class="ui-icon ui-icon-seek-next"></span><span class="ui-icon ui-icon-seek-end"></span>';
		str += '<select id="selectResultsPP"><option value="10">10</option><option value="20">20</option><option value="30">30</option><option value="40">40</option><option value="50">50</option></select>';
		str += '</div>';
		
		$(sel).append(str);
		
		$('#pagNav .ui-icon')
			.bind('mouseenter', function(){
				$(this).addClass('ui-highlight');
			})
			.bind('mouseleave', function(){
				$(this).removeClass('ui-highlight');
			});
		
		$('#pagNav .ui-icon-seek-first')
			.bind('click', function(){
				$this.data('data2html')._pageIndex = 0; 
				_loopRows.call($this);
			});

		$('#pagNav .ui-icon-seek-prev')
			.bind('click', function(){
				if ($this.data('data2html')._pageIndex >=1) $this.data('data2html')._pageIndex--;
				_loopRows.call($this);
			});
		
		$('#pagNav .ui-icon-seek-next')
			.bind('click', function(){
				var totalPages = Math.floor($this.data('data2html').rowCount / $this.data('data2html').resultsPerPage); 
				if ($this.data('data2html')._pageIndex < totalPages) $this.data('data2html')._pageIndex++;
				_loopRows.call($this);
			});
		
		$('#pagNav .ui-icon-seek-end')
			.bind('click', function(){
				var totalPages = Math.floor($this.data('data2html').rowCount / $this.data('data2html').resultsPerPage);
				$this.data('data2html')._pageIndex = totalPages; 
				_loopRows.call($this);
			});
		
		$('#selectResultsPP').change(function(){
			$this.data('data2html').resultsPerPage = parseInt($("option:selected", this).val()); 
			_calcResultPages.call($this);
			$this.data('data2html')._pageIndex = 0; 
			_loopRows.call($this);
			
		});
		
		$('#pagNav .pageIndex').change(function(){
			var totalPages = Math.floor($this.data('data2html').rowCount / $this.data('data2html').resultsPerPage);
			var index = parseInt($(this).val())-1;
			if (index > totalPages || isNaN(index)) return false; 
			
			$this.data('data2html')._pageIndex = index; //(index == totalPages)? index:(index-1);
			_loopRows.call($this);
			
		});
	}
    function _removeAll($this) {
        $this.empty();
        $this.append($this.data('data2html').listHeader);
    }
	function _calcResultPages() {
        var totalPages = Math.floor(
            this.data('data2html').rowCount /
            this.data('data2html').resultsPerPage);
        if ($(this).data('data2html').paginationNav){
            $('#pagNav .totalResultPages').html(totalPages+1);
        }
    }
    
    var _indexCols = {};
	function _loopRows() {
        var data2htmlObj = $(this).data('data2html'),
            dataArray = data2htmlObj.dataArray,
            xmlSize = data2htmlObj.rowCount,
            resultsPP = ( data2htmlObj.paginationNav ?
                          data2htmlObj.resultsPerPage : xmlSize),
            startIndex = data2htmlObj._pageIndex * resultsPP; 
        var nextSet = startIndex + resultsPP;

        // append header
		$(this).empty();
		$(this).append(data2htmlObj.listHeader)
		$('.pageIndex').val(data2htmlObj._pageIndex + 1);
        
        // loop rows
		for (var i=startIndex; (i<xmlSize && i<nextSet); i++){
			var row = dataArray.rows[i];
			var templateStr = data2htmlObj.tpl; 	
            for (var tagName in _indexCols) {
				var value = row[_indexCols[tagName]];
				var pattern = new RegExp('\{'+tagName+'\}','gi');		
				templateStr = templateStr.replace(pattern, value);
			}			
			$(this).append(templateStr);
			data2htmlObj.rowComplete.call(this,i,$(this).children(":last"));
		}
        data2htmlObj.complete.call(this,startIndex+resultsPP);
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
