function d2h_display(selector, name) {
    this._selectors = {};
    this._currentName = null;
    if (selector) {
        this.add(selector, name);
    }
}

// Static
d2h_display.create = function(selector, name) {
    return new this(selector, name);
};

d2h_display.go = function(d2h_data, name) {
    var switchToObj = $.data(d2h_data.getElem(), "Data2Html_display");
    if (!name) {
        var selector = '#' + d2h_data.getElem().id,
            sels = switchToObj._selectors,
            iName;
        for (iName in sels) {
            if (sels[iName] === selector) {
                name = iName;
                break;
            }
        }
    }
    return switchToObj.go(name);
};

d2h_display.goFormAction = function(d2h_data, action, elemKeys) {
    var name = 'form-edit',
        keys = null;
    if (elemKeys) {
        var keys = d2h_data.getRowKeys(elemKeys, 'info');
    }
    
    var formObj = d2h_display.get(d2h_data, name),
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
                keysElements[0].$(keysElements[1]).val(0);
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
    
    add: function(selector, name) {
        $elem = $(selector);
        if ($elem.length !== 1) {
            $.error(
                "d2h_display.add(): Selector '" + selector + 
                "' has selected " + $elem.length +
                "  elements. Must select only one element!"
            );
        }
        $.data($elem[0], "Data2Html_display", this);
        this._selectors[name] = selector;
        return this;
    },
    
    get: function(name) {
        var $selected = $(this._selectors[name]);
        if (!$selected) {
            $.error(
                "d2h_display.get(): Name '" + name + 
                "' not exist on selectors. Must add it!"
            );
        }
        return $selected.data2html('get');
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
