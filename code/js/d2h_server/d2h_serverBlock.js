/**
 * 
 */

var d2h_serverBlock = (function ($) {

    function d2h_serverBlock(objElem, container, options) {
        this._init(objElem, container, options);
    }
    
    $.extend(d2h_serverBlock.prototype, d2h_serverBase.prototype, {
        enents: [
            'beforeSave', 'afterSave', 'errorSave',
            'beforeDelete', 'afterDelete', 'errorDelete',
            'beforeLoadElement', 'afterLoadElement','errorLoadElement'
        ],
        defaults: {
            type: 'block',
            classIsChanged: 'd2h_changed'
        },
        
        _init: function(objElem, container, options) {
            d2h_serverBase.prototype._init.apply(this, [objElem, container, options]);
            
            // prevent submit
            var formEle = this.objElem,
                _settings = this.settings,
                $formEle = $(formEle);
            if (formEle.tagName === 'FORM')  {
                $formEle.on('submit', function() {
                    return false;
                });
            }
            this.$('data-d2h-from-id').change(function() {
                this.changedOn();
            });
                   
            // clearBlock
            var _promises = null,
                $subElements = $('[data-d2h]', this.objElem);
            if ($subElements.length > 0) {
                _promises = [];
                $subElements.each(function() {
                    _promises.push(d2h_server(this).getPromise());
                });
            }
            
            this._initEnd(_promises);
        },
        
        changedOn: function() {
            $(this.objElem).addClass(this.settings.classIsChanged);
        },
        changedOff: function() {
            $(this.objElem).removeClass(this.settings.classIsChanged);
        },
        
        loadBlock: function(options) {
            if (!options || !options.keys) {
                $.error("d2h_server: Can't load form data without 'keys' option.");
            }
            var _settings = this.settings;
            this.server({
                ajaxType: 'GET',
                data: {d2h_keys: options.keys},
                before: [
                    "beforeLoadElement",
                    options && options.beforeLoadElement
                ],
                error: [
                    "errorLoadElement",
                    options && options.errorLoadElement
                ],
                after: [
                    function(jsonData) {
                        this.setRows(jsonData);
                        if (this._rows.length > 0) {
                            this.showFormData(this._rows[0]);
                        } else {
                            this.clearBlock();
                        }
                    },
                    "afterLoadElement",
                    options && options.afterLoadElement
                ],
                complete: function(msg){
                    this.changedOff();
                }
            });
            return this;
        },
        
        blockKeys: function(keys) {
            if (arguments.length > 0) {
                $(this.objElem).data('d2h-keys', JSON.stringify(keys));
                return keys;
            } else {
                var sKeys = $(this.objElem).data('d2h-keys');
                return (sKeys ? JSON.parse(sKeys) : '');
            }
        },
        
        save: function(options) {
            var data = d2h_values.validateServer(this),
                _settings = this.settings;
            if (data === false) {
                return this;
            }
            var d2h_oper,
                d2hKeys = this.blockKeys();
            if (d2hKeys) {
                data['[keys]'] = d2hKeys;
                d2h_oper = 'update';
            } else {
                d2h_oper = 'insert';
            }
            this._rows = null;
            this.server({
                ajaxType: 'POST',	
                data: {
                    d2h_oper: d2h_oper,
                    d2h_data: data
                },
                before: [
                    "beforeSave",
                    options && options.beforeSave
                ],
                error: [
                    "errorSave",
                    options && options.errorSave
                ],
                after: [
                    "afterSave",
                    options && options.afterSave
                ],
                complete: function(msg) {
                    this.changedOff();
                }
            });
            return this;
        },
        'delete': function(options) {
            var data = d2h_values.getData(this),
                _settings = this.settings;
            data['[keys]'] = this.blockKeys();
            this.server({
                ajaxType: 'POST',	
                data: {
                    d2h_oper: 'delete',
                    d2h_data: data
                },
                before: [
                    "beforeDelete",
                    options && options.beforeDelete
                ],
                error: [
                    "errorDelete",
                    options && options.errorDelete
                ],
                after: [
                    "afterDelete",
                    options && options.afterDelete
                ],
                complete: function(msg) {
                    this.changedOff();
                }
            });
            return this;
        },
        
        clear: function() {
            this.clearBlock();
        },
        
        clearBlock: function(options) {
            var allElements = true;
            if (options) {
                allElements = !options.onlyWithDefault;
            }
            d2h_values.putData(this, allElements);
            return this;
        },
        
        showFormData: function(row) {
            d2h_values.putData(this, row);
            return this;
        }
    });
    
    return d2h_serverBlock;
})(jQuery);