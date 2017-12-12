$('#' + $${id}).data2html({
    url:'$${url}',
    repeat:'table tbody tr',
    visual: $${visual},
    actions: {
        'edit': function(elem) {
                var keys = this.getKeys(elem, 'info'); 
                var a = d2h_switchTo.go(this, 'form-edit')
                a.load({keys:keys});
        },
        'copy': function() {},
        'delete': function(elem) {
                var keys = this.getKeys(elem, 'info'); 
                d2h_switchTo.go(this, 'form-edit').load({keys:keys});
        },
        'create': function() {d2h_switchTo.go(this, 'form-edit').clear();}
    },
    filter:['#' +'$${id}_filter', {
        actions: {
            'readPage': function() {this.load();}
        }
    }],
    page: ['#' + '$${id}_page', {
        actions: {
            'readPage': function() {this.load();},
            'nextPage': function() {this.load({add:true});}
        }
    }],
    sort: '#' + '$${id}_sort'
});
