<?php
/*

    CREATE TABLE dept_emp (
    emp_no      INT             NOT NULL,
    dept_no     CHAR(4)         NOT NULL,
    from_date   DATE            NOT NULL,
    to_date     DATE            NOT NULL,
    FOREIGN KEY (emp_no)  REFERENCES employees   (emp_no)  ON DELETE CASCADE,
    FOREIGN KEY (dept_no) REFERENCES departments (dept_no) ON DELETE CASCADE,
    PRIMARY KEY (emp_no,dept_no)
);

*/
return [
    'table' => 'dept_emp',
    'items' => [
        'emp_no' => ['link' => 'empl_employees:list', 'required', 'key'],
        'emp_name' => ['base' => 'emp_no[full_name]'],
        'dept_no' => ['link' => 'empl_departments:list', 'required', 'key'],
        'dept_name' => ['base' => 'dept_no[dept_name]'],
        'from_date' => ['date', 'required'],
        'to_date' => ['date', 'required']
    ],
    'grids' => [
        'main' => [
            'filter' => [
                'items' => ['=dept_no']
            ],
            'items' => ['dept_name', 'emp_name']
        ]
    ],
    'blocks' => [
        'main' => [
            'items' => ['emp_no', 'dept_no', 'from_date',
//            'dept_name',
            'to_date']
        ]
    ]
];
