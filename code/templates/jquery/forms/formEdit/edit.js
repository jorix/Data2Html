$('#' + '$${id}').d2h_server({
    url:    '$${url}',
    type:   'form',
    actions: {
        'read-previous': function() {
            d2h_display.goGridAction(this, 'read-previous');
        },
        'read-next': function() {
            d2h_display.goGridAction(this, 'read-next');
        },
        'save': function() {
            d2h_display.goGridAction(this, 'save');
        },
        'create': function() {
            d2h_display.goGridAction(this, 'create');
        },
        'delete': function() {
            d2h_display.goGridAction(this, 'delete');
        },
        'show-grid': function() {
            d2h_display.goGridAction(this, 'show-grid');
        }
    },
    visual: $${visual}
});
