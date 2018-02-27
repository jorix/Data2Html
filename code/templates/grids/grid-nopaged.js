$('#' + $${id}).d2h_server({
    url:'$${url}',
    repeat:'table tbody tr',
    visual: $${visual},
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
            'readPage': function() {this.load();}
        }
    }],
    sort: '#' + '$${id}_sort'
});
