/**
 * TODO: right now, the same page can only have one pagination. Should be that pagination with all associated fields is instance specific!!!
 * 
 */
(function( $ ){
	var methods = {
        init: function(_options){
            var settings = {
                url         	: 'php/ctrl/ShopAndOrder.php',				
                params 			: 'oper=listProviders',
                rowName			: 'row', 
                dataArray			: null,							//the dataArray once loaded/received
                offSet			: 0,							//indicates the offset when deleting dynamic rows. 
                listHeader		: null,							//fixed content that always should be insert before the actual dynamic content
                tpl				: '',							//template HTML string if no template is provided in the HTML.
                paginationNav	: '',							//the selector-element for pagination of results. 
                type			: 'GET',
                resultsPerPage	: 10,							//default results per page, if paginationNav is active
                loadOnInit		: false, 						//if the xml is loaded on "ini" call
                rowCount		: 0,							//nr of total xml result rows (specified by rowName)
                autoReload		: 0,							//milliseconds before this.reload is issued. 0 = never
                loadAnim 		: true,							//show loading animation
                                                                //a HTML string to be used as loading animation. automatically 
                                                                //inserted before loading and removed after loading has finished
                loadAnimHTMLStr	: '<p style="text-align:center"><img src="img/ajax-loader.gif"></p>',
                beforeLoad	: function(){},
                complete	: function(row_count){},			//called, once loop through xml has finished
                rowComplete : function(current_row_index, row){}//called, after each row 
            };
            return this.each( function(){
                if (_options) { 
                    $.extend(settings, _options);
                }
                var $this = $(this);
				data = $this.data('data2html');	   	
					//if an offset is specified, then we look for a list header
					//the container element can have a list header which can be specified with the lt(index) which counts
					//back to 0. 
					if (settings.offSet > 0){
						var listHeader =  $this.children(':lt('+settings.offSet+')').detach(); 
						if (listHeader.size()>0){
							settings.listHeader = listHeader;
						} 
					}

					
					//a template is either directly provided as string, if not, everything that 
					//remains after a potential header is detached from the DOM and used as template. 
					//set the template string
					settings.tpl = (settings.tpl != '')? settings.tpl:$this.html();
					
					//delete all content / templates in the original DOM
					$this.empty();
				   
					
					//construct pagination nav if a selector has been specified
					if (settings.paginationNav != ''){
						_constructPagination.call($this,settings.paginationNav);
						$('#selectResultsPP').val(settings.resultsPerPage);
						
					}
					
					
					// If the plugin hasn't been initialized yet
					if ( ! data ) {		
						$(this).data('data2html', {
							url				: settings.url,
							params 			: settings.params,
							rowName			: settings.rowName,
							dataArray				: settings.dataArray,
							offSet			: settings.offSet,
							listHeader		: settings.listHeader,
							tpl				: settings.tpl,	
							type			: settings.type,
							resultsPerPage	: settings.resultsPerPage,
							paginationNav	: (settings.paginationNav == '')? false:true,
							loadOnInit		: settings.loadOnInit,
							rowCount		: settings.rowCount,
							loadAnim 		: settings.loadAnim,
							loadAnimHTMLStr : settings.loadAnimHTMLStr,
							
							autoReload		: settings.autoReload,
							beforeLoad		: settings.beforeLoad,
							complete 		: settings.complete,
							rowComplete 	: settings.rowComplete,
							_pageIndex		: 0
							
						});
					}//end if
					if (settings.loadOnInit) {
                        $this.data2html("reload");
                    }
				}); //end for each
 		}, //end init
        removeAll : function (){
            return this.each(function(){
                $(this).empty();
                $(this).append($(this).data('data2html').listHeader);
            });
        },
        getXML : function(){
            return $(this).data('data2html').dataArray;
        },
	  	getTemplate: function(){
            return $(this).data('data2html').tpl;
        },
        reload: function( options ) {
            if ( options ) {
                $.extend( $(this).data('data2html'), options );
            }
            return this.each(function() {
                var $this = $(this);
                    _data = $this.data('data2html'),
                    rowName =  _data.rowName;
                $.ajax({
                    type: _data.type,
                    url: _data.url + "?" + _data.params,		
                    dataType: "json", 
                    beforeSend: function(jqXHR, settings){
                        lstr = _data.loadAnimHTMLStr
                        if ( _data.loadAnim){
                            if ($this.parents('table').is('table')){
                                $this.parents('table')
                                     .after(lstr);
                                $this.parents('table')
                                     .next()
                                     .addClass("loadAnimRemoveMe")
                            } else {
                                $this.append(lstr);
                                $this.children(":last-child")
                                     .addClass("loadAnimRemoveMe")
                            }
                        }
                        _data.beforeLoad.call(this,0);
                    },
                    success: function(jsonData){
                        //save xml result set
                        _data.dataArray = jsonData;
                        //determine its size
                        _data.rowCount = jsonData.rows.length;
                        //extract all available tag names
                        for (var i=0, len= jsonData.cols.length; i <len; i++) {
                            _indexCols[jsonData.cols[i]] = i;
                        }
                        //calculate how many results pages this makes
                        _calcResultPages.call($this);
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
                if (_data.autoReload > 1){
                    setTimeout(function(){
                        $this.data2html("reload");
                    }, _data.autoReload);
                }
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
	function _calcResultPages() {
        var totalPages = Math.floor(
            this.data('data2html').rowCount /
            this.data('data2html').resultsPerPage);
        if ($(this).data('data2html').paginationNav){
            $('#pagNav .totalResultPages').html(totalPages+1);
        }
    }
    var _indexCols = {}
	function _loopRows() {
		 
        var data2htmlObj = $(this).data('data2html'),
            rowName = data2htmlObj.rowName,
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
			//get the current row in the xml result set
			var row = dataArray.rows[i];
			//reset the template string
			var templateStr = data2htmlObj.tpl; 	

			//for each tag	of the row
            for (var tagName in _indexCols) {
				//get the actual value from the xml for the given tag
				var xmlValue = row[_indexCols[tagName]];
				//construct the string we are searching for
				var pattern = new RegExp('\{'+tagName+'\}','gi');		
				templateStr = templateStr.replace(pattern, xmlValue);
			}			
			$(this).append(templateStr);
			data2htmlObj.rowComplete.call(this,i,$(this).children(":last"));
		}
        data2htmlObj.complete.call(this,startIndex+resultsPP);
	} //end loop
    /**
     * Method calling logic
     */
	$.fn.data2html = function(method) {
        if ( methods[method] ) {
            return methods[ method ].apply(
                this, Array.prototype.slice.call( arguments, 1 )
            );
        } else if ( typeof method === 'object' || ! method ) {
            return methods.init.apply( this, arguments );
        } else {
            $.error( 'Method ' +  method + ' does not exist on jQuery.data2html' );
        }
    };
})( jQuery );
