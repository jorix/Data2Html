$('#' + $${id}).data2html({
    url:'$${url}',
    repeat:'table tbody tr',
    visual: $${visual},
    actions: {
        'edit': function(elem) {
            d2h_switchTo.goFormAction(this, 'form-edit', 'edit', elem);
        },
        'delete': function(elem) {
           d2h_switchTo.goFormAction(this, 'form-edit', 'delete', elem);
        },
        'copy': function(elem) {
            d2h_switchTo.goFormAction(this, 'form-edit', 'copy', elem);
        },
        'create': function() {
            d2h_switchTo.goFormAction(this, 'form-edit', 'create');
        }
    },
    filter:['#' +'$${id}_filter', {
        actions: {
            'readPage': function() {this.loadGrid();}
        }
    }],
    page: ['#' + '$${id}_page', {
        actions: {
            'readPage': function() {this.loadGrid();},
            'nextPage': function() {this.loadGrid({add:true});}
        }
    }],
    sort: '#' + '$${id}_sort'
});
