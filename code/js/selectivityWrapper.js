function selectivityWrapper(objElem, options) {
    options['singleRequest'] = true;
    this._init(objElem, this, options);
    var _this = this;
    $(objElem).selectivity({
        placeholder: 'Search uuuuuuuuuuuuuuu',
        multiple: true,
        ajax: {
            url: options.url,
            params: function(term, offset) {
                var response = {};
                response['d2h_filter=' + options.filterName] = term;
                return response;
            },
            minimumInputLength: 3,
            XXXquietMillis: 250,
            fetch: function(curreentUrl, init, queryOptions) {
                return _this.server({
                    url: curreentUrl
                }).then(function(data) {
                    return {
                        results: $.map(data.rows, function(item) {
                            return {
                                id: item['[keys]'][0],
                                text: item[options.textName]
                            };
                        }),
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
}
$.extend(selectivityWrapper.prototype, d2h_server.server.prototype, {
    
});
