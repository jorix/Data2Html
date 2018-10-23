<?php die("It's uncooked!"); ?>
$(function () {
    $('#$${id}_div').datetimepicker();
    $('#$${id}_div input').click(function() {
        $(this).datetimepicker('toggle');
        return false;
    });
});
