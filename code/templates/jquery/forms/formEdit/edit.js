$('#' + '$${id}').d2h_server({
    url:    '$${url}',
    type:   'form',
    actions: {
        'readPage': function() { return false; },
        'save': function() {
            this.save({
                afterSave: function(){
                    var gridServer = d2h_display.getServer(this, 'grid');
                    gridServer.loadGrid();
                    d2h_display.show(this, 'grid');
                }
            });
        },
        'create': function() {
            this.save({
                afterSave: function(jsonData) {
                    var gridServer = d2h_display.getServer(this, 'grid'),
                        keys = jsonData.keys;
                    gridServer.selectedKeys(keys);
                    if (this.isEventUsed('applyLeafKeys')) {
                        d2h_display.goFormAction(this, 'edit', keys);
                    } else {
                        gridServer.loadGrid();
                        d2h_display.show(this, 'grid');
                    }
                }
            });
        },
        'delete': function() {
            this.delete({
                afterDelete: function(){
                    var gridServer = d2h_display.getServer(this, 'grid');
                    gridServer.loadGrid();
                    d2h_display.show(this, 'grid');
                }
            });
        },
        'back': function() {d2h_display.show(this, 'grid');}
    },
    visual: $${visual}
});
