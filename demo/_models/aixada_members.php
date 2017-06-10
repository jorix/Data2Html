<?php
class aixada_members extends Data2Html_Model {
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
    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_member',
            'title' => 'Membres',
            'base' => array(
                'id'        => array('autoKey', 'required'),
                'name'      => array(
                    'title' => 'Usuari',
                    'length' => 255, 'required'
                ),
                'active'    => array('integer', 'required', 'default' => 1)
            ),
            'constraints' => (
                array('uniqueKey' => 'name')
            ),
            'grids' => array(
                'default' => array(
                    'columns' => array('id', 'name', 'active'),
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                )
            )
        );
/*

 create table aixada_uf (
  id   	     		int				not null,
  name				varchar(255)    not null,
  active     		tinyint 		default 1,   	
  created			timestamp 		default current_timestamp,
  mentor_uf         int             default null,
  primary key (id)
)
*/
    }
}
