function selectivityWrapper(objElem, options) {
    
    this.defaults = {
        // When single request is immediate load and return a $.ajax
        singleRequest: true, 
        pageSize: 200
    };
    var optionsSel = {
        allowClear: true,
        placeholder: options.placeholder,
        multiple: !!options.multiple
    };
    if (options.url && options.filterName) {
        var _this = this;
        $.extend(optionsSel, {
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
    
    
    this._init(objElem, this, options);
    
    $(objElem).selectivity(optionsSel);
    
    this._initEnd();
}
$.extend(selectivityWrapper.prototype, d2h_server.server.prototype, {
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
                return {
                    id: item['[keys]'][0],
                    text: item[_key0]
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
    }
});
