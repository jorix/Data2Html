<?php
/*
  id   	     			int				not null auto_increment,
  login			 		varchar(50)	 	not null,
  password   			varchar(255) 	not null,
  email			 		varchar(100) 	not null,
  uf_id                 int,
  member_id             int, 
  provider_id           int,
  language              char(5)        default 'en',
  gui_theme	       		varchar(50)    default null,
  last_login_attempt    timestamp,
  last_successful_login timestamp,
  created_on            timestamp,
  primary key (id),
  foreign key (uf_id) references aixada_uf(id),
  foreign key (member_id) references aixada_member(id),
  foreign key (provider_id) references aixada_provider(id)  
*/
return [
    'table' => 'aixada_user',
    'title' => 'Users',
    'items' => [
        'id' => ['autoKey', 'hidden'],
        'login' => ['string' => 50, 'required'],
        'password' => ['string' => 255, 'required'],
        'email' => ['email' => 100, 'required'],
        'member_id' => ['link' => 'aixada_members:list'],
        'member_name' => ['base' => 'member_id[name]'],
        // 'provider_id' => ['link' => 'aixada_providers:list'],
        'language' => ['string' => 5],
        'gui_theme' => ['string' => 50],
        'last_login_attempt' => ['datetime'],
        'last_successful_login' => ['datetime'],
        'created_on' => [
            'title' => 'Created',
            'datetime'
        ],
    ],
    'grids' => [
        'list' => [
            'items' => ['login']
        ],
        'main' => [
            'sort' => 'login',
            'items' => [
                'login',
                'member_name'
            ],
            'filter' => [
                'items' => [
                    '%login'
                ]
            ]
        ]
    ],    
    'blocks' => [
        'main' => [
            'items' => [
                'login',
                'password',
                'email',
                'member_id'
            ]
        ]
    ]
];
