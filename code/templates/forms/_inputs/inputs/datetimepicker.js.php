<?php die("It's uncooked!"); ?>
$(function () {
    var $dp = $('#$${id}_div').datetimepicker();
    $('input', $dp).click(function() {
        $dp.data('DateTimePicker').toggle();
    });
});
