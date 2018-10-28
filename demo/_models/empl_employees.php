<?php
/*
    emp_no      INT             NOT NULL,
    birth_date  DATE            NOT NULL,
    first_name  VARCHAR(14)     NOT NULL,
    last_name   VARCHAR(16)     NOT NULL,
    gender      ENUM ('M','F')  NOT NULL,    
    hire_date   DATE            NOT NULL,
    PRIMARY KEY (emp_no)
*/
$gender = [
    'M' => 'Man',
    'F' => 'Female'
];
return [
    'table' => 'employees',
    'title' => '_{Employees}',
    'items' => [
        'emp_no' => [
            'key', 'integer'
        ],
        'birth_date' => ['date'],
        'first_name' => [
            'string' => 14,
            'required'
        ],
        'last_name' => [
            'string' => 16,
            'required'
        ],
        'gender' => [
            'string' => 1,
            'required',
            'list' => $gender
            
        ],
        'emp_gender' => ['base' => 'gender[]']
    ],
    'grids' => [
        'main' => [
            'filter' => ['items' => ['%first_name', '[]gender']],
            'items' => ['emp_no', 'first_name', 'last_name', 'birth_date', 'emp_gender']
        ]
    ]
];
