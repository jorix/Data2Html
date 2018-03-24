var d2h_display = (function($) {
    
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
            _on(branch, 'hideLeafs',  function() { 
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
                    this.show('element');
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
                        _leafKeys = options.leafKeys,
                        _applyKeys = function(server, branchKeys) {
                            for (var i = 0, l = _leafKeys.length; i < l; i++) {
                                server.$('[name=' + _leafKeys[i] + ']').val(
                                    (branchKeys ? branchKeys[i] : '')
                                );
                            }
                        };
                    switch (name) {
                        case 'grid':
                            _on(branch,
                                'applyFormLeafKeys',  
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
                        case 'element':
                            _on(selector,
                                'applyBranchKeys', 
                                function() {
                                    // retrieve branch keys from grid
                                    var _detail = $(selector).d2h_server('get');
                                    _applyKeys(_detail, _this.getServer('grid').branchKeys());
                                }
                            );
                            break;
                    }
                }
            } else {
                $.error(
                    "[d2h_display].add(): If selector must be a plain object or a string!"
                );
            }
            $.data(_singleElement(selector), "Data2Html_display", this);
            this._selectors[name] = selector;
            return this;
        },
        
        getSelector: function(name) {
            if (!name in this._selectors) {
                $.error(
                    "[d2h_display].getServer(): Name " + name + 
                    '" not exist on items. Add it firts!'
                );
            }
            return this._selectors[name];
        },
        
        getServer: function(name) {
            return $(_singleElement(this.getSelector(name))).d2h_server('get');
        },
        
        loadGrid: function() {
            this.getServer('grid').loadGrid();
            this.show('grid');
        },
        
        show: function(name) {
            var iName,
                sels = this._selectors,
                serverSelector = this.getSelector(name);
                serverObj = this.getServer(name);
            switch (name) {
                case 'element':
                    _trigger(serverSelector, 'applyBranchKeys');
                    break;
                case 'grid':
                    _trigger(serverSelector, 'hideLeafs');
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

        
    // Static private methods
    // --------------
    var _get = function(serverSelector) {
        var elem, elemSelector;
        if (typeof serverSelector === 'string' ) {
            elem = _singleElement(serverSelector);
            elemSelector = serverSelector;
        } else {
            elem = serverSelector.getElem();
            elemSelector = 'd2h_server(#' + elem.id + ')';
        }
        var displayObj = $.data(elem, "Data2Html_display");
        if (!displayObj) {
            $.error(
                "_get(): '" + elemSelector + 
                "' not found!"
            );
        }
        return displayObj;
    };

    var _singleElement = function(selector) {
        var $elem = $(selector);
        if ($elem.length !== 1) {
            $.error(
                "_singleElement(): Selector '" + selector +
                "' has selected " + $elem.length +
                " elements. Must select only one DOM element!"
            );
        }
        return $elem[0];
    };
    
    // Events
    // --------------
    // We define our own system of events since we monitor events before
    // defining the object server.
    var _events = {};
    
    var _on = function(onSelector, eventName, handlerFn) {
        if (!_events[onSelector]) {
            _events[onSelector] = {};
        }
        var eventsSel = _events[onSelector];
        if (!eventsSel[eventName]) {
            eventsSel[eventName] = 0;
        }
        eventsSel[eventName]++;
        $(onSelector).on(
            'd2h_dsp_' + eventName,
            function() {
                var args = [];
                Array.prototype.push.apply(args, arguments);
                args.shift();
                return handlerFn.apply(null, args);
            }
        );
    };
    
    var _isEventUsed = function(onSelector, eventName) {
        var eventsSel = _events[onSelector];
        if (!eventsSel) {
            return false;
        } else {
            return !!eventsSel[eventName];
        }
    };
        
    var _trigger = function(onSelector, eventName, args) {
        return $(onSelector).triggerHandler('d2h_dsp_' + eventName, args);
    };
    
    // Static public methods
    // --------------
    _displayClass.create = function(options) {
        return new _displayClass(options);
    };

    _displayClass.goGridAction = function(server, action) {
        var _displayObj = _get(server),
            formServer = _displayObj.getServer('element');
        switch (action) {
            case 'read-previous':
            //TODO
                break;
            case 'read-next':
            //TODO
                break;
            case 'save': 
                formServer.save({
                    errorSave: function(message) {
                        d2h_messages.fail(
                            formServer,
                            __('display/save-error')
                        );
                        return false;
                    },
                    afterSave: function(){
                        var gridServer = _displayObj.getServer('grid');
                        gridServer.loadGrid({
                            afterLoadGrid: function() {
                                d2h_messages.done(
                                    _displayObj.show('grid'),
                                    __('display/saved')
                                );
                            }
                        });
                    }
                });
                break;
            case 'create':
                formServer.save({
                    errorSave: function(message) {
                        d2h_messages.fail(
                            formServer,
                            __('display/create-error')
                        );
                        return false;
                    },
                    afterSave: function(jsonData) {
                        var gridServer = _displayObj.getServer('grid'),
                            keys = jsonData.keys,
                            gridSelector = _displayObj.getSelector('grid');
                        gridServer.selectedKeys(keys);
                        if (_isEventUsed(gridSelector, 'applyFormLeafKeys')) {
                            gridServer.loadGrid(); // To show new record in the grid
                            _displayClass.goFormAction(formServer, 'show-edit', keys, {
                                after: function() {
                                    d2h_messages.done(
                                        formServer,
                                        __('display/created-leafs')
                                    );
                                }
                            });
                        } else {
                            gridServer.loadGrid({
                                afterLoadGrid: function() {
                                    d2h_messages.done(
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
                formServer.delete({
                    errorDelete: function(message) {
                        d2h_messages.fail(
                            formServer,
                            __('display/delete-error')
                        );
                        return false;
                    },
                    afterDelete: function(){
                        var gridServer = _displayObj.getServer('grid');
                        gridServer.loadGrid({
                            afterLoadGrid: function() {
                                d2h_messages.removed(
                                    _displayObj.show('grid'),
                                    __('display/deleted')
                                );
                            }
                        });
                    }
                });
                break;
            case 'show-grid':
                d2h_messages.clear(_displayObj.show('grid'));
                break;
        }
    };

    _displayClass.goFormAction = function(server, action, _keys, _options) {
        var _displayObj = _get(server),
            formSelector = _displayObj.getSelector('element'),
            formServer = _displayObj.getServer('element'),
            formElem = formServer.getElem();
        _options = _options ? _options : {};
        switch (action) {
            case 'show-edit':
                formServer.loadForm({
                    keys: _keys,
                    afterLoadForm: function() {
                        var gridSelector = _displayObj.getSelector('grid');
                        _trigger(gridSelector, 'applyFormLeafKeys', [_keys]);
                        $('.d2h_delete,.d2h_insert', formElem).hide();
                        $('.d2h_update,.d2h_move', formElem).show();
                        d2h_messages.clear(_displayObj.show('element'));
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
                        var gridSelector = _displayObj.getSelector('grid');
                        _trigger(gridSelector, 'applyFormLeafKeys', [_keys]);
                        $('.d2h_update,.d2h_insert', formElem).hide();
                        $('.d2h_delete,.d2h_move', formElem).show();
                        d2h_messages.clear(_displayObj.show('element'));
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
                        formServer.clearForm({onlyWithDefault: true});
                        _trigger(formSelector, 'hideLeafs');
                        $('.d2h_update,.d2h_delete,.d2h_move', formElem).hide();
                        $('.d2h_insert', formElem).show();
                        d2h_messages.clear(_displayObj.show('element'));
                        if (_options.after) {
                            _options.after.call(this);
                        }
                    }
                });
                break;
            case 'show-create':
                formServer.clearForm();
                _trigger(formSelector, 'hideLeafs');
                $('.d2h_update,.d2h_delete,.d2h_move', formElem).hide();
                $('.d2h_insert', formElem).show();
                d2h_messages.clear(_displayObj.show('element'));
                if (_options.after) {
                    _options.after.call(this);
                }
                break;
        }
    };
    
    // 
    return _displayClass;
})(jQuery);
