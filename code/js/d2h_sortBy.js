function d2h_sortBy() {
    this._init.apply(this, arguments);
}

// Static
d2h_sortBy.create = function(d2h_data, sortSelector) {
    return new this(d2h_data, sortSelector);
};
d2h_sortBy.show = function(d2h_data, sortBy) {
    var sortByObj = $.data(d2h_data.getElem(), "Data2Html_sortBy");
    return sortByObj.show(sortBy);
};

// Class
d2h_sortBy.prototype = {
    _init: function(d2h_data, sortSelector) {
        var $sort = $(sortSelector);
        if ($sort.length !== 1) {
            $.error(
                "d2h_sortBy.create(): Selector '" + sortSelector + 
                "' has selected " + $sort.length +
                "  elements. Must select only one element!"
            );
        }
        
        this.dataObj = d2h_data;
        this.dataElem = d2h_data.getElem();
        this.sortElem = $sort[0];
        $.data(this.dataElem, "Data2Html_sortBy", this);
        
        var _this = this;
        $('[data-d2h-sort]', this.dataElem).each(function() {
            var _sortByName = $(this).attr('data-d2h-sort');
            $('.d2h_sort_no, .d2h_sort_desc', this).on('click', function() {
                _this.show(_sortByName).load();
            });
            $('.d2h_sort_asc', this).on('click', function() {
                _this.show('!' + _sortByName).load();
            });
        });
    },
    show: function(sortBy) {
        if (!sortBy) {
            $.error("d2h_sortBy.show(): Argument 'sortBy' is required!");
        }
        $('.d2h_sortBy_asc, .d2h_sortBy_desc', this.dataElem)
            .removeClass('d2h_sortBy_asc d2h_sortBy_desc')
            .addClass('d2h_sortBy_no');
        var sortByName = sortBy,
            order = 'd2h_sortBy_asc';
        switch (sortBy.substr(0, 1)) {
            case '!': case '-': case '>':
                sortByName = sortBy.substr(1);
                order = 'd2h_sortBy_desc';
                break;
            case '+': case '<':
                sortByName = sortBy.substr(1);
                break;
            case '$': // ERROR on server template
                return this;
        }
        $('[data-d2h-sort=' + sortByName + ']', this.dataElem)
            .removeClass('d2h_sortBy_no')
            .addClass(order);
        $(this.sortElem).val(sortBy);
        return this;
    },
    load: function() {
        this.dataObj.load();
        return this;
    }
};
