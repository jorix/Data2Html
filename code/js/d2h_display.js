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
        var gridServer = d2h_display.get(branch).getServer('grid');                               
        gridServer.on('hideLeafs',  function() { _this.hide(); });
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
                        var detailServer = d2h_display.get(branch).getServer('detail');                               
                        detailServer.on(
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
    
    show: function(name, message) {
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
d2h_display.loadGrid = function(gridSelector) {
    var display = d2h_display.get(gridSelector);
    display.getServer('grid').loadGrid();
    display.show('grid');
};

d2h_display.goGridAction = function(formServer, action) {
    var display = d2h_display.get(formServer),
        gridServer = display.getServer('grid');
    switch (action) {
        case 'read-previous':
        //TODO
            break;
        case 'read-next':
        //TODO
            break;
        case 'save': 
            formServer.save({
                afterSave: function(){
                    gridServer.loadGrid();
                    d2h_messages.done(
                        display.show('grid'),
                        __('display/saved')
                    );
                }
            });
            break;
        case 'create':
            formServer.save({
                afterSave: function(jsonData) {
                    var gridServer = display.getServer('grid'),
                        keys = jsonData.keys;
                    gridServer.selectedKeys(keys);
                    if (formServer.isEventUsed('applyLeafKeys')) {
                        d2h_display.goFormAction(formServer, 'show-edit', keys, {
                            after: function() {
                                d2h_messages.done(
                                    formServer,
                                    __('display/created-leafs')
                                );
                            }
                        });
                    } else {
                        gridServer.loadGrid();
                        d2h_messages.done(
                            display.show('grid'),
                            __('display/created')
                        );
                    }
                }
            });
            break;
        case 'delete':
            formServer.delete({
                afterDelete: function(){
                    gridServer.loadGrid();
                    d2h_messages.removed(
                        display.show('grid'),
                        __('display/deleted')
                    );
                }
            });
            break;
        case 'show-grid':
            d2h_messages.clear(display.show('grid'));
            break;
    }
};

d2h_display.goFormAction = function(server, action, _keys, _options) {
    var display = d2h_display.get(server),
        formServer = display.getServer('detail'),
        formElem = formServer.getElem();
    _options = _options ? _options : {};
    switch (action) {
        case 'show-edit':
            formServer.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formServer.trigger('applyLeafKeys', [_keys]);
                    $('.d2h_delete,.d2h_insert', formElem).hide();
                    $('.d2h_update,.d2h_move', formElem).show();
                    d2h_messages.clear(display.show('detail'));
                    if (_options.after) {
                        _options.after.call(this);
                    }
                }
            });
            break;
        case 'show-delete':
            formServer.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formServer.trigger('applyLeafKeys', [_keys]);
                    $('.d2h_update,.d2h_insert', formElem).hide();
                    $('.d2h_delete,.d2h_move', formElem).show();
                    d2h_messages.clear(display.show('detail'));
                    if (_options.after) {
                        _options.after.call(this);
                    }
                }
            });
            break;
        case 'show-copy':
            formServer.loadForm({
                keys: _keys,
                afterLoadForm: function() {
                    formServer
                        .clearForm({onlyWithDefault: true})
                        .trigger('hideLeafs');
                    $('.d2h_update,.d2h_delete,.d2h_move', formElem).hide();
                    $('.d2h_insert', formElem).show();
                    d2h_messages.clear(display.show('detail'));
                    if (_options.after) {
                        _options.after.call(this);
                    }
                }
            });
            break;
        case 'show-create':
            formServer.clearForm().trigger('hideLeafs');
            $('.d2h_update,.d2h_delete,.d2h_move', formElem).hide();
            $('.d2h_insert', formElem).show();
            d2h_messages.clear(display.show('detail'));
            if (_options.after) {
                _options.after.call(this);
            }
            break;
    }
};

d2h_display.get = function(serverSelector) {
    var elem, elemSelector;
    if (typeof serverSelector === 'string' ) {
        elem = d2h_display.singleElement(serverSelector);
        elemSelector = serverSelector;
    } else {
        elem = serverSelector.getElem();
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
};
