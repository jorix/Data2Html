<?php die("It's uncooked!"); ?>
<div class="row"
    data-d2h="
        url:'$${url}',
        repeat:'.form-inline'
    "
>
    <form id="$${id}" class="simple-form"
        data-d2h-on="change:readPage"
    >
        <div class="form-inline">$${body}</div>
    </form>
</div>
