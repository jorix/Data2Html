var d2h_display = (function($) {
    
    var _COMPONENT_NAME = "Data2Html_display";
    var _events = new d2h_events('d2h_display');
    
    // Class
    // -----
    var _displayClass = function(options) {
        this._options = options;
        this._selectors = {};
        this._currentName = null;
        if ('items' in options) {
            var elems = options.items;
            for (iName in elems) {
                this.add(iName, elems[iName]);
            }
        }
        
        // Manage branch or auto option
        var branch = this._options.branch;
        if (branch) {
            var _this = this
            _on(branch, 'hideLeaves',  function() { 
                _this.hide();
            });
            this.hide();
        } else {
            switch (this._options.auto) {
                case 'hide':
                    this.hide();
                    break;
                case 'loadGrid':
                    this.loadGrid();
                    break;
                case 'showDetail':
                    this.show('block');
                    break;
                case 'showGrid':
                default:
                    this.show('grid');
            }
        }
    };

    _displayClass.prototype = {
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
                        "[d2h_display].add(): If options is a plain object 'selector' key is required!"
                    );
                }
                if (options.leafKeys) {
                    var branch = this._options.branch;
                    if (!branch) {
                        $.error(
                            "[d2h_display].add(): If 'leafKeys' is used needs a 'branch' global options."
                        );
                    }
                    var _this = this,
                        _leafKeys = options.leafKeys;
                    if (!$.isArray(_leafKeys)) {
                        _leafKeys = [_leafKeys];
                    }
                    var _applyKeys = function(server, branchKeys) {
                            if (!server) { return; }
                            for (var i = 0, l = _leafKeys.length; i < l; i++) {
                                server.$('[data-d2h-name=' + _leafKeys[i] + ']').val(
                                    (branchKeys ? branchKeys[i] : '')
                                );
                            }
                        };
                    switch (name) {
                        case 'grid':
                            _on(branch,
                                'applyFormLeafKeys',  
                                function(branchKeys) {
                                    var gridServer = _this.getServer('grid'),
                                        filter = gridServer.getComponent('filter');
                                    _applyKeys(filter, branchKeys);
                                    // save branch keys on grid
                                    gridServer.branchKeys(branchKeys);
                                    if (branchKeys) {
                                        gridServer.load();
                                    } else {
                                        gridServer.clear();
                                    }
                                    _this.show('grid');
                                }
                            );
                            break;
                        case 'block':
                            _on(selector,
                                'applyBranchKeys', 
                                function() {
                                    // retrieve branch keys from grid
                                    _applyKeys(
                                        d2h_server(d2h_utils.getSingleElement(selector)),
                                        _this.getServer('grid').branchKeys()
                                    );
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
            $.data(d2h_utils.getSingleElement(selector), _COMPONENT_NAME, this);
            this._selectors[name] = selector;
            return this;
        },
        
        getSelector: function(name) {
            if (!name in this._selectors) {
                $.error(
                    '[d2h_display].getSelector(): Name "' + name + 
                    '" not exist on items. Add it firts!'
                );
            }
            return this._selectors[name];
        },
        
        getServer: function(name) {
            return d2h_server(d2h_utils.getSingleElement(this.getSelector(name)));
        },
        
        /**
         * Loads and show grid on a d2h_server associated with d2h_display object.
         * 
         * NOTE: Load grid is asynchronous called when d2h_server is not yet 
         *      created, then wait to server creation (since it is possible that
         *      exist js code pending to execute)
         */
        loadGrid: function() {
            var _this = this,
                _gridElem = d2h_utils.getSingleElement(this.getSelector('grid'));
            d2h_server.whenCreated(_gridElem, function() {
                d2h_server(_gridElem).load();
                _this.show('grid');
            });
        },
        
        show: function(name) {
            var iName,
                sels = this._selectors,
                serverSelector = this.getSelector(name);
           //     serverObj = this.getServer(name);
            switch (name) {
                case 'block':
                    _trigger(serverSelector, 'applyBranchKeys');
                    break;
                case 'grid':
                    _trigger(serverSelector, 'hideLeaves');
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
            return serverSelector;
          //  return serverObj;
        },
        
        hide: function() {
            var iName,
                sels = this._selectors;
            for (iName in sels) {
                $(sels[iName]).hide();
            }
        }
    };

        
    // Static private methods
    // --------------
    var _get = function(serverSelector) {
        var elem, elemSelector;
        if (typeof serverSelector === 'string' ) {
            elem = d2h_utils.getSingleElement(serverSelector);
            elemSelector = serverSelector;
        } else {
            elem = serverSelector.getElem();
            elemSelector = 'd2h_server(#' + elem.id + ')';
        }
        var displayObj = $.data(elem, _COMPONENT_NAME);
        if (!displayObj) {
            $.error(
                "_get(): '" + elemSelector + 
                "' not found!"
            );
        }
        return displayObj;
    };
    
    // Events
    // --------------
    // We define our own system of events since we monitor events before
    // defining the object server.
    var _on = function(selector, eventName, handlerFn) {
        return _events.on(selector, null, eventName, handlerFn);
    };
    
    var _eventIsUsed = function(selector, eventName) {
        return _events.isUsed(selector, eventName);
    };
        
    var _trigger = function(selector, eventName, args) {
        return _events.trigger(selector, eventName, args);
    };
    
    // Static public
    // --------------
    var d2h_display = function(options) {
        return new _displayClass(options);
    };

    d2h_display.goGridAction = function(server, action) {
        var _displayObj = _get(server),
            blockServer = _displayObj.getServer('block');
        switch (action) {
            case 'read-previous':
            //TODO
                break;
            case 'read-next':
            //TODO
                break;
            case 'save': 
                blockServer.save({
                    errorSave: function(message) {
                        d2h_message.danger(blockServer, __('display/save-error'));
                        return false;
                    },
                    afterSave: function(){
                        var gridServer = _displayObj.getServer('grid');
                        gridServer.load({
                            afterLoadGrid: function() {
                                d2h_message.success(
                                    _displayObj.show('grid'),
                                    __('display/saved')
                                );
                            }
                        });
                    }
                });
                break;
            case 'create':
                blockServer.save({
                    errorSave: function(message) {
                        d2h_message.danger(blockServer, __('display/create-error'));
                        return false;
                    },
                    afterSave: function(jsonData) {
                        var gridServer = _displayObj.getServer('grid'),
                            keys = jsonData.keys,
                            gridSelector = _displayObj.getSelector('grid');
                        gridServer.selectedKeys(keys);
                        if (_eventIsUsed(gridSelector, 'applyFormLeafKeys')) {
                            gridServer.load(); // To show new record in the grid
                            d2h_display.goFormAction(blockServer, 'show-edit', keys, {
                                after: function() {
                                    d2h_message.success(
                                        blockServer,
                                        __('display/created-leafs')
                                    );
                                }
                            });
                        } else {
                            gridServer.load({
                                afterLoadGrid: function() {
                                    d2h_message.success(
                                        _displayObj.show('grid'),
                                        __('display/created')
                                    );
                                }
                            });
                        }
                    }
                });
                break;
            case 'delete':
                blockServer.delete({
                    errorDelete: function(message) {
                        d2h_message.danger(
                            blockServer,
                            __('display/delete-error')
                        );
                        return false;
                    },
                    afterDelete: function(){
                        var gridServer = _displayObj.getServer('grid');
                        gridServer.load({
                            afterLoadGrid: function() {
                                d2h_message.warning(
                                    _displayObj.show('grid'),
                                    __('display/deleted')
                                );
                            }
                        });
                    }
                });
                break;
            case 'show-grid':
                d2h_message.clear(_displayObj.show('grid'));
                break;
        }
    };

    d2h_display.goFormAction = function(server, action, _keys, _options) {
        var _displayObj = _get(server),
            formSelector = _displayObj.getSelector('block'),
            blockServer = _displayObj.getServer('block'),
            blockElem = blockServer.getElem();
        _options = _options ? _options : {};
        switch (action) {
            case 'show-edit':
                blockServer.load({
                    keys: _keys,
                    afterLoadElement: function() {
                        var gridSelector = _displayObj.getSelector('grid');
                        _trigger(gridSelector, 'applyFormLeafKeys', [_keys]);
                        $('.d2h_dsp_delete,.d2h_dsp_insert', blockElem).hide();
                        $('.d2h_dsp_update,.d2h_dsp_move', blockElem).show();
                        d2h_message.clear(_displayObj.show('block'));
                        if (_options.after) {
                            _options.after.call(this);
                        }
                    }
                });
                break;
            case 'show-delete':
                blockServer.load({
                    keys: _keys,
                    afterLoadElement: function() {
                        var gridSelector = _displayObj.getSelector('grid');
                        _trigger(gridSelector, 'applyFormLeafKeys', [_keys]);
                        $('.d2h_dsp_update,.d2h_dsp_insert', blockElem).hide();
                        $('.d2h_dsp_delete,.d2h_dsp_move', blockElem).show();
                        d2h_message.clear(_displayObj.show('block'));
                        if (_options.after) {
                            _options.after.call(this);
                        }
                    }
                });
                break;
            case 'show-copy':
                blockServer.load({
                    keys: _keys,
                    afterLoadElement: function() {
                        blockServer.clear({onlyWithDefault: true});
                        _trigger(formSelector, 'hideLeaves');
                        $('.d2h_dsp_update,.d2h_dsp_delete,.d2h_dsp_move', blockElem).hide();
                        $('.d2h_dsp_insert', blockElem).show();
                        d2h_message.clear(_displayObj.show('block'));
                        if (_options.after) {
                            _options.after.call(this);
                        }
                    }
                });
                break;
            case 'show-create':
                blockServer.clear();
                _trigger(formSelector, 'hideLeaves');
                $('.d2h_dsp_update,.d2h_dsp_delete,.d2h_dsp_move', blockElem).hide();
                $('.d2h_dsp_insert', blockElem).show();
                d2h_message.clear(_displayObj.show('block'));
                if (_options.after) {
                    _options.after.call(this);
                }
                break;
        }
    };
    
    // 
    return d2h_display;
})(jQuery);
