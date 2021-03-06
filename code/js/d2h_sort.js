// Class
var d2h_sort = (function($) {
    
    var _COMPONENT_NAME = "Data2Html_sort";
    
    function d2h_sort() {
        this._init.apply(this, arguments);
    }
    
    d2h_sort.prototype = {
        _init: function(gridServer, sortSelector) {
            var $sort = $(sortSelector);
            if ($sort.length !== 1) {
                $.error(
                    "d2h_sort.create(): Selector '" + sortSelector + 
                    "' has selected " + $sort.length +
                    "  elements. Must select only one element!"
                );
            }
            this.gridServer = gridServer;
            this.sortElem = $sort[0];
            
            var dataElem = this.gridServer.getElem();
            $.data(dataElem, _COMPONENT_NAME, this);
            
            var _this = this;
            $('[data-d2h-sort]', dataElem).each(function() {
                var _sortName = $(this).attr('data-d2h-sort');
                $('.d2h_sortIco_no, .d2h_sortIco_desc', this).on('click', function() {
                    _this.show(_sortName);
                    _this.gridServer.load();
                });
                $('.d2h_sortIco_asc', this).on('click', function() {
                    _this.show('!' + _sortName);
                    _this.gridServer.load();
                });
            });
        },
        show: function(sort) {
            var dataElem = this.gridServer.getElem();
            $('.d2h_sort_asc, .d2h_sort_desc', dataElem)
                .removeClass('d2h_sort_asc d2h_sort_desc')
                .addClass('d2h_sort_no');
            if (sort) {
                var sortName = sort,
                    order = 'd2h_sort_asc';
                switch (sort.substr(0, 1)) {
                    case '!': case '-': case '>':
                        sortName = sort.substr(1);
                        order = 'd2h_sort_desc';
                        break;
                    case '+': case '<':
                        sortName = sort.substr(1);
                        break;
                    case '$': // ERROR on gridServer template
                        return this;
                }
                $('[data-d2h-sort=' + sortName + ']', dataElem)
                    .removeClass('d2h_sort_no')
                    .addClass(order);
            }
            $(this.sortElem).val(sort);
            return this;
        }
    };

    // Static
    d2h_sort.create = function(gridServer, sortSelector) {
        return new this(gridServer, sortSelector);
    };
    d2h_sort.show = function(gridServer, sort) {
        return $.data(gridServer.getElem(), _COMPONENT_NAME).show(sort);
    };
    
    // 
    return d2h_sort;
})(jQuery);