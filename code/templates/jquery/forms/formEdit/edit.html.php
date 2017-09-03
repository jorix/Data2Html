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
<!--
<input class="form-control" placeholder="id" id="get_id" type="text">
<button class="btn btn-default hidden-xs " title="looadP"
    onclick="$('#$${id}').data2html('load', {keys:$('#get_id').val()});">
    <span class="glyphicon glyphicon-step-backward" 
        aria-label="$Previus_PHP"
        aria-hidden="true"></span>
    <span class="hidden-xs hidden-sm"></span>
</button>
-->
    <form class="simple-form"
        
    >
        <div class="form-inline">$${body}</div>
    </form>
</div>
