<?php die("It's uncooked!"); ?>
<div id="$${id}" class="row" style="display:none"
        data-d2h-form="
            url:    '$${url}',
            type:   'GET',
            actions: {
                'none': function() { return false; },
                'readPage': function() {this.load();},
                'save': function() {this.save();}
            },
            visual: $${visual}
        "
        xxxx-data-d2h-on="change:readPage"
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
