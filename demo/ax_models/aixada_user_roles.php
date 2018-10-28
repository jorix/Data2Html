<?php
/*
  create table aixada_user_role (
  user_id    		int	not null,
  role  			varchar(100)	not null,
  primary key (user_id, role),
  foreign key (user_id)	references aixada_user(id)

*/

$list = [
    'Econo-Legal Commission' => 'Econo-Legal Commission#',
    'Consumer Commission' => 'Consumer Commission#',
    'Logistic Commission' => 'Logistic Commission#',
    'Hacker Commission' => 'Hacker Commission#',
    'Fifth Column Commission' => 'Fifth Column Commission#',
    'Producer' => 'Producer#',
    'Checkout' => 'Checkout#',
    'Consumer' => 'Consumer#'
];
return [
    'table' => 'aixada_user_role',
    'title' => 'User roles',
    'items' => [
        'user_id' => [
            'key', 
            'link' => 'aixada_users:list',
            'required'
        ],
        'user_login' => ['base' => 'user_id[login]'],
        'role' => [
            'key',
            'string' => 100,
            'required',
            'list' => $list
        ],
        'role_desc' => ['base' => 'role[]']
    ],
    'grids' => [
        'main' => [
            'filter' => ['items' => ['[]role']],
            'items' => ['user_login', 'role_desc']
        ]
    ]
];
