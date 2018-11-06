// Static
var d2h_template = (function ($) {
    
    var _toHtml = function(val, dataType) {
        if (val === null) {
            return '&amp;{null}';
        } else {
            return ('' + _toVal(val, dataType))
                .split('&').join('&amp;')
                .split('"').join('&quot;')
                .split("'").join('&#39;'); //(val === null ? '{null}' : val);
        }
    };

    var _toVal = function(val, dataType) {
        switch (dataType) {
            case 'datetime':
                if (val === null || val === '[now]') {
                    return '';
                }
                return moment(val).format('L LT');
            case 'date':
                if (val === null || val === '[now]') {
                    return '';
                }
                return moment(val).format('L');
            default:
                return val;
        }
    };

    // Public static methods
    var d2h_template = function(html, visualData) {
        this.html = html;
        this.visualData = visualData;
        
        var patt = /\$\{(\[keys\]\s*\|?\s*|[\w\d]+|[\w\d]+\s*\|[\s\w\d,;:\(\)\.\|\-+'"]+)\}/g,
            repl,
            __replaces = [],
            __cols = [];
        while (repl = patt.exec(this.html)) {
            var formatIndex = repl[1].indexOf('|'),
                name,
                format = '';
            if (formatIndex >= 0) {
                name = repl[1].substr(0, formatIndex -1).trim();
                format = repl[1].substr(formatIndex +1).trim();
            } else {
                name = repl[1];
            }
            __replaces.push({
                repl: repl[0],
                name: name,
                format: format
            });
        }
        
        this.apply = function(row) {
            var xName,
                rowKeys = null;
            if (__cols.length === 0) {
                // initialize __cols of object
                for (xName in row) {
                    __cols.push(xName);
                }
            }
            var html = this.html,
                visualData = this.visualData ;
            for (var i = 0, l = __replaces.length; i < l; i++) {
                var replItem = __replaces[i],
                    iName = replItem.name.replace(' ', ''),
                    val;
                if (iName === '[keys]') {
                    val = row['[keys]'];
                    if (replItem.repl.indexOf('|') > 0 && val.length === 1) {
                        // When pattern ${[keys] | } force scalar if is possible
                        rowKeys = val[0]; // (val[0] === null ? '{null}' : val[0]);
                    } else {
                        rowKeys = JSON.stringify(val);
                    }
                    html = html.replace(replItem.repl, _toHtml(rowKeys, 'string'));
                } else {
                    var visualAttr = visualData ? visualData[iName] : null,
                        dataType =  visualAttr ? visualAttr.type : null;
                    if ($.isNumeric(iName)) {
                        val = row[__cols[iName]];
                    } else {
                        val = row[iName];
                    }
                    html = html.replace(replItem.repl, _toHtml(val, dataType));
                }
            }
            return html;
        };
    };
    
    return d2h_template;
})(jQuery);
