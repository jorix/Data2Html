$('#' + $${id}).d2h_server({
    url: '$${url}',
    repeat: 'table tbody tr',
    selectedClass: 'info',
    visual: $${visual},
    auto: 'clearGrid',
    actions: {
        'edit': function(elem) {
            d2h_display.goFormAction(this, 'edit', elem);
        },
        'delete': function(elem) {
           d2h_display.goFormAction(this, 'delete', elem);
        },
        'copy': function(elem) {
            d2h_display.goFormAction(this, 'copy', elem);
        },
        'create': function() {
            d2h_display.goFormAction(this, 'create');
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
