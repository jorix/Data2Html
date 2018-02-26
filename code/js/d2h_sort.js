// Class
function d2h_sort() {
    this._init.apply(this, arguments);
}
d2h_sort.prototype = {
    _init: function(dataObj, sortSelector) {
        var $sort = $(sortSelector);
        if ($sort.length !== 1) {
            $.error(
                "d2h_sort.create(): Selector '" + sortSelector + 
                "' has selected " + $sort.length +
                "  elements. Must select only one element!"
            );
        }
        this.dataObj = dataObj;
        this.sortElem = $sort[0];
        
        var dataElem = this.dataObj.getElem();
        $.data(dataElem, "Data2Html_sort", this);
        
        var _this = this;
        $('[data-d2h-sort]', dataElem).each(function() {
            var _sortName = $(this).attr('data-d2h-sort');
            $('.d2h_sortIco_no, .d2h_sortIco_desc', this).on('click', function() {
                _this.show(_sortName);
                _this.dataObj.loadGrid();
            });
            $('.d2h_sortIco_asc', this).on('click', function() {
                _this.show('!' + _sortName);
                _this.dataObj.loadGrid();
            });
        });
    },
    show: function(sort) {
        var dataElem = this.dataObj.getElem();
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
                case '$': // ERROR on server template
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
d2h_sort.create = function(dataObj, sortSelector) {
    return new this(dataObj, sortSelector);
};
d2h_sort.show = function(dataObj, sort) {
    return $.data(dataObj.getElem(), "Data2Html_sort").show(sort);
};
