/**
 * 
 */
 
var d2h_serverGrid = (function ($) {

    function d2h_serverGrid(objElem, options) {
        this._init(objElem, null, options);
    }
    
    $.extend(d2h_serverGrid.prototype, d2h_serverBase.prototype, {
        events: ["beforeLoadGrid", "errorLoadGrid", "afterLoadGrid"],
        defaults: {
            type: 'grid',
            auto: 'loadGrid',
            repeat: '.d2h_repeat',
            pageSize: 0, //default results per page
            
            filter: null,
            page: null,
            
            //called, after show each row
            afterShowGridRow: function(row_index, elemRow) {},
        },
        
        components: null,

        // repeat
        _repeatHtml: '',       // template HTML string
        _repeatStart: 0,
        _selectorRepeat: '',
        _selectorRepeatParent: '',
        _branchKeys: null,

        _init: function(objElem, container, options) {
            d2h_serverBase.prototype._init.apply(this, [objElem, container, options]);
            var settings = this.settings,
                gridEle = this.objElem;

            // Set internal selectors
            this.components = {};
            var iClassRepeat = 'i_d2h_repeat_' + this._initId,
                iClassRepeatParent = iClassRepeat + '_container';
            this._selectorRepeat = '.' + iClassRepeat;
            this._selectorRepeatParent = '.' + iClassRepeatParent;

            // Check repeat selector
            var $itemRepeat = $(settings.repeat, gridEle);
            if ($itemRepeat.length == 0) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath(gridEle) +
                    "': Does not contain a '" +
                    settings.repeat +
                    "' selector."
                );
            }
            if ($itemRepeat.length > 1) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath(gridEle) +
                    "': Contains more than one '" +
                    settings.repeat +
                    "' selector."
                );
            }

            // Mark repeat and parent elements.
            $itemRepeat.addClass(iClassRepeat);
            var $parentContainer = $itemRepeat.parent();
            if ($(this._selectorRepeatParent, gridEle).length > 0) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath(gridEle) +
                    "': Contains selector '" +
                    this._selectorRepeatParent +
                    "' which is for internal use only!"
                );
            }
            $parentContainer.addClass(iClassRepeatParent);
            if ($(this._selectorRepeat, $parentContainer).length > 1) {
                $.error("d2h_server: Can not initialize DOM object '" +
                    d2h_utils.getElementPath($parentContainer[0]) +
                    "': Contains more than one '" +
                    this._selectorRepeat +
                    "' selector."
                );
                return;
            }
                
            // Set template
            this._repeatHtml = $itemRepeat.get(0).outerHTML;
            this._repeatStart = $parentContainer.children().index($itemRepeat);
            
            // initialize components
            // page
            if (settings.page) {
                this._initComponent('page', settings.page);
            }
            // sort
            if (settings.sort) {
                d2h_sort.create(this, settings.sort).show($(settings.sort).val());
            }
            // filter
            var promises = null;
            if (settings.filter) {
                this._initComponent('filter', settings.filter);
                if (this.components.filter) {
                    promises = this.components.filter.getPromise();
                }
            }
            
            this._initEnd(promises);
        },
        
        _initComponent: function(compomentName, selector) {
            // Check arguments
            var compomentOptions = null;
            if (!selector) { return; }
            if ($.isArray(selector)) {
                if (selector.length < 1 || selector.length > 2) {
                    $.error(
                        "d2h_server: Can not initialize component '" + 
                        compomentName +
                        "'. When selector is array must have 1 or 2 items!"
                    );
                }
                if (selector.length >= 2) {
                    compomentOptions = selector[1];
                }
                selector = selector[0];
            }
            
            // To set up the components
            var $elem;
            if (selector.substr(0, 1) === "#") {
                $elem = $(selector);
            } else {
                $elem = $(selector, this.objElem);
            }
            if ($elem.length === 0) {
                this.components[compomentName] = null;
                return;
            } else if ($elem.length !== 1) {
                $.error(
                    "d2h_server: Selector '" + selector +
                    "' has selected " + $elem.length +
                    " elements. Must select only one element!"
                );
            }
            this.components[compomentName] =
                new d2h_serverBlock($elem[0], this, compomentOptions);
        },
        
        getComponent: function(compomentName) {
            return this.components[compomentName];
        },
        
        loadGrid: function(options) {
            var _settings= this.settings,
                sortSelector = _settings.sort,
                _add = false,
                data = {},
                pageStart = 1;
            if (this.components.filter) {
                var dataFilter = d2h_values.validateServer(this.components.filter);
                if (dataFilter === false) {
                    this._rows = null;
                    this.clearGrid();
                    return this;
                }
                data['d2h_filter'] = $.param(dataFilter).replace(/&/g, '{and}');
            }
            if (sortSelector) {
                data['d2h_sort'] = $(sortSelector, this.objElem).val();
            }
            
            if (options && options.add) {
                _add = true;
            } else {
                this._rows = null;
            }
            if (this.components.page) {
                if (_add) {
                    pageStart = this._rows ? this._rows.length + 1 : 1;
                }
                var aux = d2h_values.getData(this.components.page);
                aux['pageStart'] = pageStart;
                data['d2h_page'] = $.param(aux).replace(/&/g, '{and}');
            }
            this.server({
                ajaxType: 'GET',
                data: data,
                before: [
                    "beforeLoadGrid",
                    options && options.beforeLoadGrid
                ],
                error: [
                    "errorLoadGrid",
                    options && options.errorLoadGrid
                ],
                after: [
                    function(jsonData) {
                        this.setRows(jsonData, _add);
                        this.showGrid();
                    },
                    "afterLoadGrid",
                    options && options.afterLoadGrid
                ],
                complete: function(msg) {
                    $.each(this.components, function(k, v) {
                        v.changedOff();
                    }); 
                }
            });
            return this;
        },

        // Manage grid HTML
        clear: function() {
            this.clearGrid();
        },
        clearGrid: function() {
            var $parentContainer = $(this._selectorRepeatParent, this.objElem);
            if ($parentContainer.length === 0) {
                $parentContainer = $(this.objElem);
            }
            $(this._selectorRepeat, $parentContainer).remove();
            return this;
        },
        
        getSelectedKeys: function(elem) {
            if (elem === undefined) {
                return null;
            }
            var $parent = $(elem).closest('[data-d2h-keys]'),
                selectedClass = this.settings.selectedClass;
            if (selectedClass) {
                $('.' + selectedClass, this.objElem).removeClass(selectedClass);
                $parent.addClass(selectedClass);
            }
            return this.selectedKeys(JSON.parse($parent.attr('data-d2h-keys'))); // Store keys
        },
        
        selectedKeys: function(newKeys) {
            if (arguments.length > 0) {
                this.status.selectedKeys = newKeys;
            }
            return this.status.selectedKeys;
        },
        
        branchKeys: function(branchKeys) {
            if (arguments.length > 0) {
                this._branchKeys = branchKeys;
            }
            return this._branchKeys;
        },
        
        showGrid: function () {
            this.clearGrid();
            
            var $parentContainer = $(this._selectorRepeatParent, this.objElem),
                $lastItem = null;
            if ($parentContainer.length === 0) {
                $parentContainer = $(this.objElem);
            }
            if (this._repeatStart > 0) {
                $lastItem = $(
                    $parentContainer.children()[this._repeatStart - 1]
                );
            }
            
            // repeat html
            var repManager = new d2h_values.repeatHtml(this._repeatHtml, this._visualData);
            var _settings = this.settings,
                rows = this._rows,
                selectedKeys = JSON.stringify(this.selectedKeys());
            for (var i = 0, l = rows.length; i < l; i++) {
                var row = rows[i],
                    rowKeys = JSON.stringify(row['[keys]']);
                if ($lastItem) {
                    $lastItem.after(repManager.apply(row));
                } else {
                    $parentContainer.prepend(repManager.apply(row));
                }
                $lastItem = $(
                    this._selectorRepeat + ':last',
                    $parentContainer
                );
                if (_settings.actions) {
                    this.listen($lastItem, _settings.actions);
                }
                if (selectedKeys === rowKeys && _settings.selectedClass) {
                    $lastItem.addClass(_settings.selectedClass);
                }
                _settings.afterShowGridRow.call(this, i, $lastItem);
            }
            return this;
        }
    });
    
    return d2h_serverGrid;
})(jQuery);
