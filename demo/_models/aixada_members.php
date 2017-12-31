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
$return = array(
    'table' => 'aixada_member',
    'title' => 'Membres',
    'items' => array(
        'id' => array('autoKey', 'hidden'),
        'custom_member_ref' => array(
            'title' => 'Usuari',
            'string' => 100,
            'required'
        ),
        'uf_id' => array('title' => 'UF', 'link' => 'aixada_ufs:list', 'required'),
        'uf_name' =>  array(
            'title' => 'UF',
            'base'=>'uf_id[uf_name]'
        ),
        'name' => array(
            'title' => 'Usuari',
            'string' => 255,
            'required'
        ),
        'address' => array('string' => 255),
        'nif' => array('string' => 255),
        'zip' => array('string' => 10),
        'city' => array('string' => 255),
        'phone1' => array('string' => 50, 'required'),
        'phone2' => array('string' => 50),
        'phones' => array('value' => '$${phone1} / $${phone2}', 'sortBy' => 'phone1'),
        'web' => array('string' => 255),
        'active' => array('boolean', 'required', 'default' => 1),
        'ts' => array(
            'title' => 'Created',
            'date',
            'format' => 'dd-MM-yyyy',
            'default' => '[now]'
        ),
    ),
    'grids' => array(
        'list' => array('sort' => 'name', 'items' => array('name')),
        'main' => array(
            'sort' => 'name',
            'items' => array('name', 'active' => array('sortBy' => null), 'uf_name', 'phones'),
            'filter' => array(
                'items' => array(
                    '%name', '=active', '=uf_id'
                )
            )
        )
    ),
    'forms' => array(
        'main' => array(
            'items' => array(
                'name' => array('items' => array('ts', 'active')),
                'uf_id', 'phone1', 'phone2'
            ),
        )
    )
);
