$('#' + '$${id}').data2html({
    url:    '$${url}',
    type:   'form',
    actions: {
        'readPage': function() { return false; },
        'save': function() {
            this.save({
                afterSave: function(){
                    d2h_switchTo.go(this, 'grid');
                }
            });
        },
        'delete': function() {
            this.delete({
                afterDelete: function(){
                    d2h_switchTo.go(this, 'grid');
                }
            });
        },
        'back': function() {d2h_switchTo.go(this, 'grid');}
    },
    visual: $${visual}
});
