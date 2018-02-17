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

d2h_display.show = function(serverObj, name) {
    var displayObj = $.data(serverObj.getElem(), "Data2Html_display");
    if (!name) {
        name = displayObj.getServerName(serverObj);
    }
    return displayObj.show(name);
};

d2h_display.goFormAction = function(gridObj, action, elemKeys) {
    var _keys = gridObj.getSelectedKeys(elemKeys),
        formObj = d2h_display.get(gridObj, 'detail'),
        formElem = formObj.getElem();
    switch (action) {
        case 'edit':
            formObj.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formObj.trigger('applyLeafKeys', [null]);
                }
            });
            $('.d2h_delete,.d2h_insert', formElem).hide();
            $('.d2h_update', formElem).show();
            break;
        case 'delete':
            formObj.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formObj.trigger('applyLeafKeys', [null]);
                }
            });
            $('.d2h_update,.d2h_insert', formElem).hide();
            $('.d2h_delete', formElem).show();
            break;
        case 'copy':
            formObj.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formObj
                        .clearForm({onlyWithDefault: true})
                        .trigger('applyLeafKeys', [null]);
                }
            });
            $('.d2h_update,.d2h_delete', formElem).hide();
            $('.d2h_insert', formElem).show();
            break;
        case 'create':
            formObj.clearForm().trigger('applyLeafKeys', [null]);
            $('.d2h_update,.d2h_delete', formElem).hide();
            $('.d2h_insert', formElem).show();
            break;
    }
    d2h_display.show(formObj);
};

d2h_display.get = function(serverObj, name) {
    if (typeof serverObj === 'string' ) {
        elem = d2h_display.singleElement(serverObj);
    } else {
        elem = serverObj.getElem();
    }
    var displayObj = $.data(elem, "Data2Html_display");
    if (!displayObj) {
        $.error(
            "d2h_display.get(): name '" + name + 
            "' not found!"
        );
    }
    return displayObj.get(name);
};

d2h_display.singleElement = function(selector) {
    var $elem = $(selector);
    if ($elem.length !== 1) {
        $.error(
            "d2h_display.singleElement(): Selector '" + selector + 
            "' has selected " + $elem.length +
            " elements. Must select only one DOM element!"
        );
    }
    return $elem[0];
}

// Class
d2h_display.prototype = {
    _selectors: null,
    _options: null,
    _currentName: '',
    
    add: function(name, options) {
        var selector;
        if (typeof options === 'string' ) {
            selector = options;
        } else if ($.isPlainObject(options)) {
            selector = options.selector;
            if (!options.selector) {
                $.error(
                    "d2h_display.add(): If options is a plain object 'selector' key is required!"
                );
            }
            if (options.leafKeys) {
                var branch = this._options.branch;
                if (!branch) {
                    $.error(
                        "d2h_display.add(): If 'leafKeys' is used needs a 'branch' global options."
                    );
                }
                var _this = this,
                    _leafKeys = options.leafKeys,
                    _applyKeys = function(server, branchKeys) {
                        for (var i = 0, l = _leafKeys.length; i < l; i++) {
                            server.$('[name=' + _leafKeys[i] + ']', ).val(
                                (branchKeys ? branchKeys[i] : '')
                            );
                        }
                    };
                switch (name) {
                    case 'grid':
                        var pServer = d2h_display.get(branch, 'detail');                               
                        pServer.on(
                            'applyLeafKeys',  
                            function(branchKeys) {
                                var grid = _this.get('grid');
                                _applyKeys(grid, branchKeys);
                                // save branch keys on grid
                                grid.branchKeys(branchKeys);
                            }
                        );
                        break;
                    case 'detail':
                        var _detail = $(selector).d2h_server('get');
                        _detail.on(
                            'applyBranchKeys', 
                            function() {
                                // retrieve branch keys from grid
                                _applyKeys(_detail, _this.get('grid').branchKeys());
                            }
                        );
                        break;
                }
            }
        } else {
            $.error(
                "d2h_display.add(): If selector must be a plain object or a string!"
            );
        }
        $.data(d2h_display.singleElement(selector), "Data2Html_display", this);
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
        var $selected = $(d2h_display.singleElement(this._selectors[name]));
        return $selected.d2h_server('get');
    },
    
    getServerName: function(serverObj) {
        var dataSelector = '#' + serverObj.getElem().id,
            sels = this._selectors,
            response = null;
            iName;
        for (iName in sels) {
            if (sels[iName] === dataSelector) {
                return iName;
            }
        }
    },
    
    show: function(name) {
        var iName,
            sels = this._selectors,
            serverObj = this.get(name);
        if (name === 'detail') {
            serverObj.trigger('applyBranchKeys');
        }
        for (iName in sels) {
            if (iName === name) {
                $(sels[iName]).show();
            } else {
                $(sels[iName]).hide();
            }
        }
        this._currentName = name;
        return serverObj;
    }
};
