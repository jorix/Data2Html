$('#' + $${id}).data2html({
    url:'$${url}',
    repeat:'table tbody tr',
    visual: $${visual},
    actions: {
        'edit': function(elem) {
            var keys = this.getKeys(elem, 'info'); 
            var form = d2h_switchTo.go(this, 'form-edit')
                .load({keys:keys})
                .getElem();
            $('.d2h_insert', form).hide();
            $('.d2h_update,.d2h_delete', form).show();
        },
        'delete': function(elem) {
            var keys = this.getKeys(elem, 'info'); 
            var form = d2h_switchTo.go(this, 'form-edit')
                .load({keys:keys})
                .getElem();
            $('.d2h_update,.d2h_insert', form).hide();
            $('.d2h_delete', form).show();
        },
        'copy': function(elem) {
            var keys = this.getKeys(elem, 'info'); 
            var form = d2h_switchTo.go(this, 'form-edit')
                .load({keys:keys})
                .getElem();
            $('.d2h_update,.d2h_delete', form).hide();
            $('.d2h_insert', form).show();
        },
        'create': function() {
            var form = d2h_switchTo.go(this, 'form-edit')
                .clear()
                .getElem();
            $('.d2h_update,.d2h_delete', form).hide();
            $('.d2h_insert', form).show();
        }
    },
    filter:['#' +'$${id}_filter', {
        actions: {
            'readPage': function() {this.load();}
        }
    }],
    sort: '#' + '$${id}_sort'
});
