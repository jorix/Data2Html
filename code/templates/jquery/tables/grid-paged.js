$('#' + $${id}).d2h_server({
    url: '$${url}',
    repeat: 'table tbody tr',
    selectedClass: 'info',
    visual: $${visual},
    auto: 'clearGrid',
    actions: {
        'show-edit': function(elem) {
            d2h_display.goFormAction(this, 'show-edit', this.getSelectedKeys(elem));
        },
        'show-delete': function(elem) {
           d2h_display.goFormAction(this, 'show-delete', this.getSelectedKeys(elem));
        },
        'show-copy': function(elem) {
            d2h_display.goFormAction(this, 'show-copy', this.getSelectedKeys(elem));
        },
        'show-create': function() {
            d2h_display.goFormAction(this, 'show-create');
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
