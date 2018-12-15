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
            var blockElem = this.objElem,
                _blockElemId = blockElem.id,
                $blockElem = $(blockElem);
            if (blockElem.tagName === 'FORM')  {
                $blockElem.on('submit', function() {
                    return false;
                });
            }
            var _this = this;
            this.$('[data-d2h-from-id]').change(function() {
                console.log(
                    'execute-> #' + _blockElemId + ': change->changedOn(#' + this.id + ')'
                );
                _this.changedOn();
            });
                   
            // clear
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
        
        clear: function(options) {
            d2h_inputs.clear(this.objElem, options ? options.onlyWithDefault : false);
            return this;
        },
        
        load: function(options) {
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
                            this.clear();
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

        save: function(options) {
            var data = d2h_inputs.get(this.objElem, 'save'),
                _settings = this.settings;
            if (data === false) {
                return this;
            }
            var d2h_oper;
            if (data['[keys]']) {
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
            var data = d2h_inputs.get(this.objElem, 'delete'),
                _settings = this.settings;
            if (data === false) {
                return this;
            }
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
        
        hideBlock: function(options) {
            $(this.objElem).hide();
        },
        
        showFormData: function(row) {
            d2h_inputs.put(this.objElem, row);
            return this;
        }
    });
    
    return d2h_serverBlock;
})(jQuery);