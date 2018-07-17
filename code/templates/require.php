<?php
return [
    'jquery' => ['html' => '
        <script src="$${base}/external/js/jquery-2.1.0/jquery.js" ></script>
        '],
    'bootstrap' => ['html' => '
        $${requires jquery}
        <script src="$${base}/external/js/bootstrap-3.3.6-dist/js/bootstrap.min.js" ></script>
        <link  href="$${base}/external/js/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
        '],
    'moment' => ['html' => '
        <script src="$${base}/external/js-date_time/moment-2.18.1/min/moment-with-locales.js" ></script>
        <script src="$${base}/external/js-date_time/moment-timezone-0.5.13/builds/moment-timezone-with-data.min.js" ></script>
        '],
    'bootstrap-datetimepicker' => ['html' => '
        $${requires bootstrap}
        <link  href="$${base}/external/js-date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
        <script src="$${base}/external/js-date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/js/bootstrap-datetimepicker.min.js"></script>
        '],
    'd2h_server' => ['html' => '
        <link  href="$${base}/code/css/d2h_server.css" rel="stylesheet">
        <script src="$${base}/code/js/d2h_server.js"></script>
        '],
    'd2h_display' => ['html' => '
        <script src="$${base}/code/js/d2h_display.js"></script>
        '],
    'd2h_messages' => ['html' => '
        <link  href="$${base}/code/css/d2h_messages.css" rel="stylesheet">
        <script src="$${base}/code/js/d2h_messages.js"></script>
        '],
    'd2h_sort' => ['html' => '
        <link  href="$${base}/code/css/d2h_sort.css" rel="stylesheet">
        <script src="$${base}/code/js/d2h_sort.js"></script>
        '],
    'd2h_values' => ['html' => '
        <script src="$${base}/code/js/d2h_values.js"></script>
        ']
];
