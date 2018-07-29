<?php die("It's uncooked!"); ?>
$${include datetimepicker, d2h_server, d2h_messages}
<span data-d2h-message="#$${id}"></span>
<div class="input-group date" id="$${id}_div">
    <input type="text" class="form-control"
        id="$${id}"
        placeholder="$${description}"
        name="$${name}"
        data-d2h-from-id="$${from-id}"
    >
    <span class="input-group-addon">
        <span class="glyphicon glyphicon-calendar"></span>
    </span>
</div>
