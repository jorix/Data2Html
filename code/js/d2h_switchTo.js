function d2h_switchTo(selector, name) {
    this._selectors = {};
    this._currentName = null;
    if (selector) {
        this.add(selector, name);
    }
}

// Static
d2h_switchTo.create = function(selector, name) {
    return new this(selector, name);
};

d2h_switchTo.go = function(d2h_data, name) {
    var switchToObj = $.data(d2h_data.getElem(), "Data2Html_switchTo");
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

d2h_switchTo.goFormAction = function(d2h_data, name, action, elemKeys) {
    var keys = null;
    if (elemKeys) {
        var keys = d2h_data.getRowKeys(elemKeys, 'info');
    }
    d2h_switchTo.get(d2h_data, name).goFormAction(action, keys);
};

d2h_switchTo.get = function(d2h_data, name) {
    var switchToObj = $.data(d2h_data.getElem(), "Data2Html_switchTo");
    return switchToObj.get(name);
};

// Class
d2h_switchTo.prototype = {
    _selectors: null,
    
    add: function(selector, name) {
        $elem = $(selector);
        if ($elem.length !== 1) {
            $.error(
                "d2h_switchTo.add(): Selector '" + selector + 
                "' has selected " + $elem.length +
                "  elements. Must select only one element!"
            );
        }
        $.data($elem[0], "Data2Html_switchTo", this);
        this._selectors[name] = selector;
        return this;
    },
    
    get: function(name) {
        var $selected = $(this._selectors[name]);
        if (!$selected) {
            $.error(
                "d2h_switchTo.get(): Name '" + name + 
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
