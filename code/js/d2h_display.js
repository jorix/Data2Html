// Class
// -----
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
    var branch = this._options.branch;
    if (branch) {
        var _this = this
        var pServer = d2h_display.getServer(branch, 'grid');                               
        pServer.on('hideLeafs',  function() { _this.hide(); });
    }
};

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
                            server.$('[name=' + _leafKeys[i] + ']', server.getElem()).val(
                                (branchKeys ? branchKeys[i] : '')
                            );
                        }
                    };
                switch (name) {
                    case 'grid':
                        var pServer = d2h_display.getServer(branch, 'detail');                               
                        pServer.on(
                            'applyLeafKeys',  
                            function(branchKeys) {
                                var grid = _this.getServer('grid');
                                _applyKeys(grid, branchKeys);
                                // save branch keys on grid
                                grid.branchKeys(branchKeys);
                                if (branchKeys) {
                                    grid.loadGrid();
                                } else {
                                    grid.clearGrid();
                                }
                                _this.show('grid');
                            }
                        );
                        break;
                    case 'detail':
                        var _detail = $(selector).d2h_server('get');
                        _detail.on(
                            'applyBranchKeys', 
                            function() {
                                // retrieve branch keys from grid
                                _applyKeys(_detail, _this.getServer('grid').branchKeys());
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
    
    getServer: function(name) {
        if (!name in this._selectors) {
            $.error(
                "d2h_display.getServer(): Name " + name + 
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
            serverObj = this.getServer(name);
        switch (name) {
            case 'detail':
                serverObj.trigger('applyBranchKeys');
                break;
            case 'grid':
                serverObj.trigger('hideLeafs');
                break;
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
    },
    
    hide: function() {
        var iName,
            sels = this._selectors;
        for (iName in sels) {
            $(sels[iName]).hide();
        }
    }
};

// Static methods
// --------------
d2h_display.show = function(serverObj, name) {
    var displayObj = d2h_display.get(serverObj);
    if (!name) {
        name = displayObj.getServerName(serverObj);
    }
    return displayObj.show(name);
};

d2h_display.hide = function(serverObj) {
    d2h_display.get(serverObj).hide();
};

d2h_display.loadGrid = function(gridServer) {
    var grid = d2h_display.getServer(gridServer, 'grid');
    grid.loadGrid();
    d2h_display.show(grid);
};

d2h_display.goFormAction = function(gridServer, action, elemKeys) {
    var _keys = gridServer.getSelectedKeys(elemKeys),
        formServer = d2h_display.getServer(gridServer, 'detail'),
        formElem = formServer.getElem();
    switch (action) {
        case 'edit':
            formServer.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formServer.trigger('applyLeafKeys', [_keys]);
                    $('.d2h_delete,.d2h_insert', formElem).hide();
                    $('.d2h_update', formElem).show();
                    d2h_display.show(formServer);
                }
            });
            break;
        case 'delete':
            formServer.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formServer.trigger('applyLeafKeys', [_keys]);
                    $('.d2h_update,.d2h_insert', formElem).hide();
                    $('.d2h_delete', formElem).show();
                    d2h_display.show(formServer);
                }
            });
            break;
        case 'copy':
            formServer.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formServer
                        .clearForm({onlyWithDefault: true})
                        .trigger('applyLeafKeys', [null]);
                    $('.d2h_update,.d2h_delete', formElem).hide();
                    $('.d2h_insert', formElem).show();
                    d2h_display.show(formServer);
                }
            });
            break;
        case 'create':
            formServer.clearForm().trigger('applyLeafKeys', [null]);
            $('.d2h_update,.d2h_delete', formElem).hide();
            $('.d2h_insert', formElem).show();
            d2h_display.show(formServer);
            break;
    }
};

d2h_display.get = function(serverObj) {
    var elem, elemSelector;
    if (typeof serverObj === 'string' ) {
        elemSelector = serverObj;
        elem = d2h_display.singleElement(serverObj);
    } else {
        elem = serverObj.getElem();
        elemSelector = 'd2h_server(#' + elem.id + ')';
    }
    var displayObj = $.data(elem, "Data2Html_display");
    if (!displayObj) {
        $.error(
            "d2h_display.get(): '" + elemSelector + 
            "' not found!"
        );
    }
    return displayObj;
};


d2h_display.getServer = function(serverObj, name) {
    return d2h_display.get(serverObj).getServer(name);
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
