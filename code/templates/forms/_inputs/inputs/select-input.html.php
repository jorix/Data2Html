<?php exit("It's uncooked!"); ?>
$${include d2h_message}
<span data-d2h-message="#$${id}"></span>
<select class="form-control" style="padding:0;"
    id="$${id}"
    data-d2h-name="$${name}"
    data-d2h-from-id="$${from-id}"
    data-d2h-input="$${visual-attr}"
    $${url ? [[data-d2h="{url:'$${url}'}"]]}
>
<option value="">( . . . )</option>
$${repeat [[<option value="${[keys]}">${0}</option>]]}
$${url ?  [[<option class="d2h_repeat" value="${[keys] | }">${0}</option>]]}
</select>
