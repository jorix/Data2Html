<?php
/*

    dept_no     CHAR(4)         NOT NULL,
    dept_name   VARCHAR(40)     NOT NULL,
    PRIMARY KEY (dept_no),
    UNIQUE  KEY (dept_name)

*/
return [
    'table' => 'departments',
    'title' => '_{Departments}',
    'items' => [
        'dept_no' => [
            'key',
            'title' => '_{departament}',
            'string' => 4
        ],
        'dept_name' => [
            'string' => 40,
            'required'
        ]
    ],
    'grids' => [
        'main' => [
            'filter' => ['items' => ['%dept_name']],
            'items' => ['dept_no', 'dept_name']
        ],
        'list' => [
            'filter' => ['items' => ['%dept_name']],
            'items' => ['dept_name']
        ]
    ]
];
