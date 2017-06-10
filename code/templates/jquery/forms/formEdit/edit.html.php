<?php die("It's uncooked!"); ?>
<div class="row"
    xdata-d2h="
        url:'$${url}',
        repeat:'form'
    "
>
    <form id="$${id}" class="simple-form"
        data-d2h-on="change:readPage"
    >
        <div class="form-inline" >$${body}</div>
    </form>
</div>
