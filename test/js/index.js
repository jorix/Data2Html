(function(w) {
    w.testDiv_create = function(options) {
        var div = document.createElement('div');
        div.id = 'testDiv_container';
        document.body.appendChild(div);
        var html = ['<div id="testDiv"></div>'],
            js = (options ? options.js : null);
        if (js) {
            for (var i = 0, l = js.length; i < l; i++) {
                html.push('<script src="../../' + js[i] + '"></script>');
            }
        }
        $(div).html(html.join(''));
        return div;
    };
    w.testDiv_remove = function() {
        var div = document.getElementById('testDiv_container');
        if (div) {
            document.body.removeChild(div);
        }
    };
})(window);
