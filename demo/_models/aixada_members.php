<?php
/*
aixada_member (
  id 	       		int				not null auto_increment,
  custom_member_ref	varchar(100)	default null,
  uf_id      		int,
  name	     		varchar(255) 	not null,
  address			varchar(255) 	not null,
  nif 				varchar(15) 	default null,
  zip				varchar(10)		default null,
  city				varchar(255) 	not null,
  phone1    		varchar(50) 	default null,
  phone2			varchar(50) 	default null,
  web				varchar(255) 	default null,
  bank_name 		varchar(255) 	default null, 
  bank_account 		varchar(40) 	default null,
  picture           varchar(255)    default null,
  notes  	 		text 			default null,
  active     	  	tinyint			default 1, 
  participant		bool 			default true,
  adult		        bool			default true, 
  ts			  	timestamp not null default current_timestamp,
  primary key (id),
  foreign key (uf_id)  references aixada_uf(id)
)
*/
$return = [
    'table' => 'aixada_member',
    'title' => 'Membres',
    'sort' => 'name',
    'items' => [
        'id' => ['autoKey', 'hidden'],
        'custom_member_ref' => [
            'title' => 'Usuari',
            'string' => 100,
            'required'
        ],
        'uf_id' => ['title' => 'UF', 'link' => 'aixada_ufs:list', 'required'],
        'uf_name' =>  [
            'title' => 'UF',
            'base'=>'uf_id[uf_name]'
        ],
        'name' => [
            'title' => 'Usuari',
            'string' => 255,
            'required'
        ],
        'address' => ['string' => 255],
        'nif' => ['string' => 255],
        'zip' => ['string' => 10],
        'city' => ['string' => 255],
        'phone1' => ['string' => 50, 'required'],
        'phone2' => ['string' => 50],
        'phones' => ['value' => '$${phone1} / $${phone2}', 'sortBy' => 'phone1'],
        'web' => ['string' => 255],
        'active' => ['boolean', 'required', 'default' => 1],
        'ts' => [
            'title' => 'Created',
            'date',
            'format' => 'dd-MM-yyyy',
            'default' => '[now]'
        ],
    ],
    'grids' => [
        'list' => ['sort' => 'name', 'items' => ['name']],
        'main' => [
            'items' => ['name', 'active' => ['sortBy' => null], 'uf_name', 'phones'],
            'filter' => [
                'items' => [
                    '%name', '=active', '=uf_id'
                ]
            ]
        ],
        'uf_members' => [
            'items' => ['name', 'active' => ['sortBy' => null], 'phones'],
            'filter' => [
                'items' => [
                    ['base' => '=uf_id', 'content-template' => 'hidden-input', 'default' => 7]
                ]
            ]
        ]
    ],
    'forms' => [
        'main' => [
            'items' => [
                'name' => ['items' => ['ts', 'active']],
                'uf_id', 'phone1', 'phone2'
            ],
        ]
    ]
];
