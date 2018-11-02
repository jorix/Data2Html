<?php die("It's uncooked!"); ?>
$${include datetimepicker, d2h_message}
<span data-d2h-message="#$${id}"></span>
<div class="input-group date" data-target-input="nearest" id="$${id}_div">
    <input type="text"
        class="form-control datetimepicker-input"
        id="$${id}"
        data-target="#$${id}_div"
        placeholder="$${description}"
        name="$${name}"
        data-d2h-from-id="$${from-id}"
    >
    <div class="input-group-append"
        data-toggle="datetimepicker" 
        data-target="#$${id}_div"
    >
        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
    </div>
</div>
