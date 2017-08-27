function d2h_switchTo(selector) {
    this._selectors = {};
    if (selector) {
        this.add(selector);
    }
}
d2h_switchTo.prototype = {
    _selectors: null,
    
    add: function(selector) {
        this._selectors[$(selector).data2html('addSwitch', this)] = selector;
        return this;
    },
    
    show: function(name) {
        var iName,
            sels = this._selectors;
        for (iName in sels) {
            if (iName === name) {
                $(sels[iName]).show();
            } else {
                $(sels[iName]).hide();
            }
        }
        return this;
    }
};