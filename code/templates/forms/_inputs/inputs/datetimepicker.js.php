<?php die("It's uncooked!"); ?>
$(function () {
    var $dp = $('#$${id}_div').datetimepicker({locale: 'ca'});
    $('input', $dp).click(function() {
        $dp.data('DateTimePicker').toggle();
    });
});
