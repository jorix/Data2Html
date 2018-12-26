<?php die("It's uncooked!"); ?>
$${include d2h_serverSelectivity, d2h_message}
<span data-d2h-message="#$${id}"></span>
<div class="selectivity-input"
    id="$${id}"
    data-d2h-name="$${name}"
    data-d2h-from-id="$${from-id}"
    data-d2h-input="$${visual-attr}"
    $${url ? [[data-d2h="{url:'$${url}'}"]]:[[data-d2h="{}"]]}
></div>
