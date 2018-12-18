<?php die("It's uncooked!"); ?>
$${include datetimepicker, d2h_message}
$${require d2h_utils}
<span data-d2h-message="#$${id}"></span>
<div class="input-group date" data-target-input="nearest" id="$${id}_div">
    <input type="text"
        class="form-control datetimepicker-input"
        id="$${id}"
        data-target="#$${id}_div"
        placeholder="$${description}"
        data-d2h-name="$${name}"
        data-d2h-from-id="$${from-id}"
        data-d2h-input="$${visual-attr}"
    >
    <div class="input-group-append"
        data-toggle="datetimepicker" 
        data-target="#$${id}_div"
    >
        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
    </div>
</div>
<script>
$(function () {
    var visual = d2h_utils.getJsData($('#$${id}'), 'd2h-input'),
        format = '';
    switch (visual.type) {
        case 'datetime':
            format = 'L LT';
            break;
        case 'date':
            format = 'L';
            break;
        case 'time':
            format = 'LT';
            break;
    }
    $('#$${id}_div').datetimepicker({format: format});
});
</script>
