var d2h_display = function(options) {
    this._options = options;
    this._selectors = {};
    this._currentName = null;
    if ('items' in options) {
        var elems = options.items;
        for (iName in elems) {
            this.add(iName, elems[iName]);
        }
    }
};

d2h_display.go = function(d2h_data, name) {
    var switchToObj = $.data(d2h_data.getElem(), "Data2Html_display");
    if (!name) {
        name = switchToObj.getDataName(d2h_data);
    }
    return switchToObj.go(name);
};

d2h_display.goFormAction = function(gridObj, action, elemKeys) {
    var keys = gridObj.getSelectedKeys(elemKeys),
        formObj = d2h_display.get(gridObj, 'detail'),
        formElem = formObj.getElem();
    switch (action) {
        case 'edit':
            var keysElements = formObj.keysElements;
            if (keysElements) {
                keysElements[0].$(keysElements[1]).val(keys);
                keysElements[0].loadGrid();
            }
            formObj.loadForm({keys:keys});
            $('.d2h_delete,.d2h_insert', formElem).hide();
            $('.d2h_update', formElem).show();
            break;
        case 'delete':
            formObj.loadForm({keys:keys});
            $('.d2h_update,.d2h_insert', formElem).hide();
            $('.d2h_delete', formElem).show();
            break;
        case 'copy':
            var keysElements = formObj.keysElements;
            if (keysElements) {
                keysElements[0].$(keysElements[1]).val(0);
                keysElements[0].loadGrid();
            }
            formObj.loadForm({
                keys: keys,
                afterLoadForm: function() {
                    formObj.clear({onlyWithDefault: true});
                }
            });
            $('.d2h_update,.d2h_delete', formElem).hide();
            $('.d2h_insert', formElem).show();
            break;
        case 'create':
            var keysElements = formObj.keysElements;
            if (keysElements) {
                keysElements[0].loadGrid();
            }
            formObj.clear();
            $('.d2h_update,.d2h_delete', formElem).hide();
            $('.d2h_insert', formElem).show();
            break;
    }
    d2h_display.go(formObj);
};

d2h_display.get = function(d2h_data, name) {
    var switchToObj = $.data(d2h_data.getElem(), "Data2Html_display");
    return switchToObj.get(name);
};

// Class
d2h_display.prototype = {
    _selectors: null,
    
    add: function(name, selector) {
        var $elem;
        if ($.isPlainObject(selector)) {
            if (!selector.selector) {
                $.error(
                    "d2h_display.add(): If selector is a plain object a 'selector' key is required!"
                );
            }
            selector = selector.selector;
        }
        $elem = $(selector);
        if ($elem.length !== 1) {
            $.error(
                "d2h_display.add(): Selector '" + selector + 
                "' has selected " + $elem.length +
                "  elements. Must select only one DOM element!"
            );
        }
        $.data($elem[0], "Data2Html_display", this);
        this._selectors[name] = selector;
        return this;
    },
    
    get: function(name) {
        if (!name in this._selectors) {
            $.error(
                "d2h_display.get(): Name " + name + 
                '" not exist on items. Add it firts!'
            );
        }
        var $selected = $(this._selectors[name]);
        if ($selected.length !== 1) {
            $.error(
                'd2h_display.get(): Selector "' + this._selectors[name] + '" of "' + name +
                '" must select only one DOM element!'
            );
        }
        return $selected.d2h_server('get');
    },
    
    getDataName: function(d2h_data) {
        var dataSelector = '#' + d2h_data.getElem().id,
            sels = this._selectors,
            response = null;
            iName;
        for (iName in sels) {
            if (sels[iName] === dataSelector) {
                return iName;
            }
        }
    },
    
    go: function(name) {
        var iName,
            sels = this._selectors;
        for (iName in sels) {
            if (iName === name) {
                $(sels[iName]).show();
            } else {
                $(sels[iName]).hide();
            }
        }
        this._currentName = name;
        return this.get(name);
    }
};
