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
)
*/
return [
    'table' => 'aixada_user',
    'title' => 'Users',
    'items' => array(
        'id' => array('autoKey', 'hidden'),
        'login' => array(
            'string' => 50,
            'required'
        ),
        'password' => array(
            'string' => 255,
            'required'
        ),
        'email' => array(
            'email' => 100,
            'required'
        ),
        'member_id' => array('link' => 'aixada_members:list'),
        'member_name' => array('base' => 'member_id[name]'),
        'created_on' => array(
            'title' => 'Created',
            'date',
            'format' => 'dd-MM-yyyy',
            'default' => '[now]'
        ),
    ),
    'grids' => array(
        //'list' => array('sort' => 'member_name', 'items' => array('member_name')),
        'main' => array(
            'sort' => 'login',
            'items' => array(
                'login',
                // 'member_name'
            ),
            'filter' => array(
                'items' => array(
                    '%login'
                )
            )
        )
    ),
    
    'blocks' => array(
        'main' => array(
            'items' => array(
                'login',
                'password',
                'email',
                'member_id'
            ),
        )
    )
];
