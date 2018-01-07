function d2h_sort() {
    this._init.apply(this, arguments);
}

// Static
d2h_sort.create = function(d2h_data, sortSelector) {
    return new this(d2h_data, sortSelector);
};
d2h_sort.show = function(d2h_data, sort) {
    return $.data(d2h_data.getElem(), "Data2Html_sort").show(sort);
};

// Class
d2h_sort.prototype = {
    _init: function(d2h_data, sortSelector) {
        var $sort = $(sortSelector);
        if ($sort.length !== 1) {
            $.error(
                "d2h_sort.create(): Selector '" + sortSelector + 
                "' has selected " + $sort.length +
                "  elements. Must select only one element!"
            );
        }
        
        this.dataObj = d2h_data;
        this.dataElem = d2h_data.getElem();
        this.sortElem = $sort[0];
        $.data(this.dataElem, "Data2Html_sort", this);
        
        var _this = this;
        $('[data-d2h-sort]', this.dataElem).each(function() {
            var _sortName = $(this).attr('data-d2h-sort');
            $('.d2h_sortIco_no, .d2h_sortIco_desc', this).on('click', function() {
                _this.show(_sortName).load();
            });
            $('.d2h_sortIco_asc', this).on('click', function() {
                _this.show('!' + _sortName).load();
            });
        });
    },
    show: function(sort) {
        $('.d2h_sort_asc, .d2h_sort_desc', this.dataElem)
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
            $('[data-d2h-sort=' + sortName + ']', this.dataElem)
                .removeClass('d2h_sort_no')
                .addClass(order);
        }
        $(this.sortElem).val(sort);
        return this;
    },
    load: function() {
        this.dataObj.load();
        return this;
    }
};
