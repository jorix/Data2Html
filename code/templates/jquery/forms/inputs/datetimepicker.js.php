<?php die("It's uncooked!"); ?>
$(function () {
    var $dp = $('#' + '$${id}').datetimepicker({locale: 'ca'});
    $('input', $dp).click(function() {
        $dp.data('DateTimePicker').toggle();
    });
});
