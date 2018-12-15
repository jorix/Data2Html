/**
 * 
 */
 
var d2h_serverSelectivity = (function ($) {

    var d2h_serverSelectivity = function(objElem, options) {
        this._init(objElem, options);
    };
    
    $.extend(d2h_serverSelectivity.prototype, d2h_serverBase.prototype, {
        _init: function(objElem, options) {
            
            // Prepare options
            this.defaults = {
                // When single request is immediate load and return a $.ajax
                singleRequest: true, 
                pageSize: 200
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
                                'd2h_page=pageSize=' + options.pageSize + 
                                '&d2h_filter=' + options.filterName
                            ] = term;
                            return response;
                        },
                        minimumInputLength: 3,
                        XXXquietMillis: 250,
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
                        id: item['[keys]'][0] + '',
                        text: item[_key0] + ''
                    };
                });
            }
        },
        
        load: function(options) {
            var sortSelector = this.settings.sort,
                data = {};
            if (sortSelector) {
                data['d2h_sort'] = $(sortSelector, this.objElem).val();
            }
            data['d2h_page'] = {pageSize: 250};
            
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
