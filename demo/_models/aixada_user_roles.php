<?php
/*
  create table aixada_user_role (
  user_id    		int	not null,
  role  			varchar(100)	not null,
  primary key (user_id, role),
  foreign key (user_id)	references aixada_user(id)

*/
return [
    'table' => 'aixada_user_role',
    'title' => 'User roles',
    'items' => [
        'user_id' => [
            'key', 
            'link' => 'aixada_users:list',
            'required'
        ],
        'role' => [
            'string' => 100,
            'required'
        ]
    ]
];
