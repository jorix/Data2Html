<?php die("It's uncooked!"); ?>
<div id="$${id}" class="row"
    data-d2h-keys="${[keys]}"
    data-d2h="
        url:    '$${url}',
        type:   'form',
        actions: {
            'readPage': function() { return false; },
            'save': function() {this.save({afterSave:function(){d2h_switchTo.go(this, 'grid');}});},
            'delete': function() {this.delete();},
            'back': function() {d2h_switchTo.go(this, 'grid');}
        },
        visual: $${visual}
    "
>
<form class="form-horizontal">$${body}</form>
</div>
