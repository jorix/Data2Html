/**
 * 
 */
 
var d2h_serverSelectivity = (function ($) {

    var d2h_serverSelectivity = function(objElem, options) {
        this._init(objElem, options);
    };
    
    $.extend(d2h_serverSelectivity.prototype, d2h_serverBase.prototype, {
        _init: function(objElem, options) {
            
            var dataInput = d2h_utils.getJsData(objElem, 'd2h-input');
            
            // Prepare options
            this.defaults = {
                // When single request is immediate load and return a $.ajax
                singleRequest: true, 
                pageSize: 250
            };
            var selectivityOptions = {
                allowClear: true,
                placeholder: options.placeholder ? options.placeholder : '...',
                multiple: !!options.multiple
            };
            if (options.url && options.filterName) {
                var _this = this;
                $.extend(selectivityOptions, {
                    ajax: {
                        url: options.url,
                        params: function(term, offset) {
                            var response = {};
                            response[
                                'pageSize=' + options.pageSize + 
                                '&' + options.filterName
                            ] = term;
                            return response;
                        },
                        minimumInputLength: 3,
                        quietMillis: 250,
                        fetch: function(currentUrl, init, queryOptions) {
                            return _this.server({
                                url: currentUrl
                            }).then(function(data) {
                                return {
                                    results: _this.transformRows(data.rows),
                                    more: false
                                };
                            });
                        }
                    },
                    templates: {
                        resultItem: function(item) {
                            return (
                                '<div class="selectivity-result-item" data-item-id="' +
                                item.id + '">' + 
                                item.text + '</div>'
                            );
                        }
                    }
                });
            } else if (options.url) {
                options['auto'] = 'load';
            }
            
            // List of items form properties on data-d2h-input
            if (dataInput.list) {
                var list = dataInput.list,
                    items = [];
                for (var key in list) {
                  items.push({id:key, text:list[key]});
                }
                $.extend(selectivityOptions, {items: items});
            }
            
            // Create server
            d2h_serverBase.prototype._init.apply(this, [objElem, null, options]);
            
            // Create selectivity
            $(objElem).selectivity(selectivityOptions);
            
            this._initEnd(null);
        },
    
        transformRows: function(rows) {
            if (!rows) {
                return null;
            } else {
                // get name of first column items
                var _key0;
                if (rows.length) {
                    _key0 = Object.keys(rows[0])[0];
                }
                return $.map(rows, function(item) {
                    // Use values as string
                    return {
                        id: item['_keys_'][0] + '',
                        text: item[_key0] + ''
                    };
                });
            }
        },
        
        load: function(options) {
            var sortSelector = this.settings.sort,
                data = {};
            if (sortSelector) {
                data.sort = $(sortSelector, this.objElem).val();
            }
            data.pageSize = this.defaults.pageSize;
            
            var _objElem = this.getElem(),
                _this = this;
            this.server({
                ajaxType: 'GET',
                data: data
            }).then(function(data) {
                var items = _this.transformRows(data.rows);
                $(_objElem).selectivity('setOptions', {'items': items});
            });
        },
        
        putValues: function(values) {
            this.then(function() {
                $(this.objElem).selectivity('val', values + '', {triggerChange: false});
                $(this.objElem).selectivity('rerenderSelection');
            });
        }
    });
    
    return d2h_serverSelectivity;
})(jQuery);
