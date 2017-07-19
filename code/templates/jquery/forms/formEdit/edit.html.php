<?php die("It's uncooked!"); ?>
<div class="row">
    <form id="$${id}" class="simple-form"
        data-d2h-form="
            url:    '$${url}&d2h_keys=2', // FORÇAR recuperar id=2
            type:   'GET'
        "
        data-d2h-on="change:readPage"
    >
        <div class="form-inline">$${body}</div>
    </form>
</div>
