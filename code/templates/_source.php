<?php
return [
    'jquery' => [
        'html' => '
            <script src="$${base}/external/js/jquery-2.1.0/jquery.js" ></script>
            '
    ],
    'bootstrap' => [
        'require' => 'jquery',
        'html' => '
            <script src="$${base}/external/js/bootstrap-3.3.6-dist/js/bootstrap.min.js" ></script>
            <link  href="$${base}/external/js/bootstrap-3.3.6-dist/css/bootstrap.min.css" rel="stylesheet">
            '
        ],
    'moment' => [
        'html' => '
            <script src="$${base}/external/js-date_time/moment-2.18.1/min/moment-with-locales.js" ></script>
            <!-- script src="$${base}/external/js-date_time/moment-timezone-0.5.13/builds/moment-timezone-with-data.min.js" ></script -->
            <script>moment.locale("$${lang}");</script>'
    ],
    'datetimepicker' => [
        'require' => ['moment', 'bootstrap'],
        'html' => '
            <link  href="$${base}/external/js-date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/css/bootstrap-datetimepicker.min.css" rel="stylesheet">
            <script src="$${base}/external/js-date_time/eonasdan/bootstrap-datetimepicker-4.17.47/build/js/bootstrap-datetimepicker.min.js"></script>'
    ],
    'd2h_server' => [
        'require' => ['jquery'],
        'include' => ['d2h_values', 'd2h_utils'],
        'html' => '
            <link  href="$${base}/code/css/d2h_server.css" rel="stylesheet">
            <script src="$${base}/code/js/d2h_server.js"></script>'
        ],
    'd2h_display' => [
        'require' => ['jquery'],
        'include' => ['d2h_values', 'd2h_utils'],
        'html' => '
            <script src="$${base}/code/js/d2h_display.js"></script>'
        ],
    'd2h_messages' => [
        'require' => ['jquery', 'popper'],
        'include' => ['d2h_values', 'd2h_utils'],
        'html' => '
            <link  href="$${base}/code/css/d2h_messages.css" rel="stylesheet">
            <script src="$${base}/code/js/d2h_messages.js"></script>'
    ],
    'popper' => [
        'require' => 'bootstrap'    ,
        'html' => '
            <link  href="$${base}/external/js/popper.js-1.14.3/docs/css/popper.css" rel="stylesheet">
            <script src="$${base}/external/js/popper.js-1.14.3/dist/umd/popper.js"></script>'
    ],
    'd2h_sort' => [
        'require' => 'jquery',
        'html' => '
            <link  href="$${base}/code/css/d2h_sort.css" rel="stylesheet">
            <script src="$${base}/code/js/d2h_sort.js"></script>'
    ],
    'd2h_values' => [
        'require' => 'jquery',
        'html' => '
            <script src="$${base}/code/js/d2h_values.js"></script>
            '
    ],
    'd2h_utils' => [
        'require' => 'jquery',
        'html' => '
            <script src="$${base}/code/js/d2h_utils.js"></script>
            '
    ]
];
