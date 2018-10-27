d2h_server('#$${id}', {
    url: '$${url}',
    type: 'grid',
    repeat: 'table tbody tr',
    selectedClass: 'table-info',
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
    $${filter ? [[
        filter:['#$${id-filter}', {
            actions: {
                'read-page': function() {this.loadGrid();}
            }
        }],
    ]]}
    $${page ? [[
        page: ['#$${id-page}', {
            actions: {
                'read-page': function() {this.loadGrid();},
                'next-page': function() {this.loadGrid({add:true});}
            }
        }],
    ]]}
    sort: '#$${id}_sort'
});
