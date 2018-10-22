<?php
return [
    'jquery' => [
        'html' => '
            <script src="$${base}/vendor/js/jquery/dist/jquery.min.js" ></script>
            '
    ],
    'bootstrap' => [
        'require' => 'jquery',
        'html' => '
            <script src="$${base}/vendor/js/bootstrap/dist/js/bootstrap.min.js" ></script>
            <link  href="$${base}/vendor/js/bootstrap/dist/css/bootstrap.min.css" rel="stylesheet">
            '
        ],
    'moment' => [
        'html' => '
            <script src="$${base}/vendor/js/moment/min/moment-with-locales.js" ></script>
            <script>moment.locale("$${lang}");</script>'
    ],
    'datetimepicker' => [
        'require' => ['moment', 'bootstrap'],
        'html' => '
            <link  href="$${base}/vendor/js/tempusdominus-bootstrap-4/build/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet">
            <script src="$${base}/vendor/js/tempusdominus-bootstrap-4/build/js/bootstrap-datetimepicker.min.js"></script>'
    ],
    'font-awesome' => [
        'html' => '
            <link href="$${base}/vendor/js/font-awesome/css/font-awesome.min.css" rel="stylesheet">
        '
    ],
    'selectivity' => [
        'require' => ['jquery', 'bootstrap', 'font-awesome', 'd2h_server'],
        'html' => '
            <link  href="$${base}/vendor/js/selectivity/selectivity-3.1.0/selectivity-jquery.min.css" rel="stylesheet">
            <script src="$${base}/vendor/js/selectivity/selectivity-3.1.0/selectivity-jquery.min.js" ></script>
            <link  href="$${base}/code/css/selectivityWrapper.css" rel="stylesheet">
            <script src="$${base}/code/js/selectivityWrapper.js">
            <script>
            var Selectivity_Locale = {
                loading: "Loading...",
                loadMore: "Load more...",
                noResults: "No results found",
                ajaxError: function(term) {
                    if (term) {
                        return "Failed to fetch results for <b>" + escape(term) + "</b>";
                    } else {
                        return "Failed to fetch results";
                    }
                },

                needMoreCharacters: function(numCharacters) {
                    return "Enter " + numCharacters + " more characters to search";
                },

                noResultsForTerm: function(term) {
                    return "No results forzzzz <b>" + escape(term) + "</b>";
                }
            };
            $.extend($.Selectivity.Locale, Selectivity_Locale);
            </script>'
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
            <link  href="$${base}/vendor/js/popper.js-1.14.3/docs/css/popper.css" rel="stylesheet">
            <script src="$${base}/vendor/js/popper.js-1.14.3/dist/umd/popper.js"></script>'
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
