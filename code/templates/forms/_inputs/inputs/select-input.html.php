<?php die("It's uncooked!"); ?>
$${include d2h_server, d2h_messages}
<span data-d2h-message="#$${id}"></span>
<select class="form-control" style="padding:0;"
    id="$${id}"
    name="$${name}"
    data-d2h-from-id="$${from-id}"
    data-d2h="{url:'$${url}'}"
>
<option value="">( . . . )</option>
<option class="d2h_repeat" value="${[keys] | }">${0}</option>
</select>
