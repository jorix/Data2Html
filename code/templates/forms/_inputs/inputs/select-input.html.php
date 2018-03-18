<?php die("It's uncooked!"); ?>
<select class="form-control" style="padding:0;"
    id="$${id}"
    name="$${name}"
    data-d2h-from="$${from-id}"
    data-d2h="url:'$${url}'"
>
<option value="">( . . . )</option>
<option class="d2h_repeat" value="${[keys] | }">${0}</option>
</select>
