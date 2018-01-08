$('#' + $${id}).data2html({
    url:'$${url}',
    repeat:'table tbody tr',
    visual: $${visual},
    actions: {
        'edit': function(elem) {
            d2h_switchTo.get(this, 'form-edit').showAction('edit', elem);
        },
        'delete': function(elem) {
           d2h_switchTo.get(this, 'form-edit').showAction('delete', elem);
        },
        'copy': function(elem) {
            d2h_switchTo.get(this, 'form-edit').showAction('copy', elem);
        },
        'create': function() {
            d2h_switchTo.get(this, 'form-edit').showAction('create');
        }
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
