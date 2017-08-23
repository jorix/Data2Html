<?php
class aixada_payment_methods extends Data2Html_Model {
/*
create table aixada_payment_method (
  id   	     	tinyint   not null auto_increment,
  description   varchar(50) not null,
  details	 	varchar(255) default null,
  primary key (id)
) 
)
*/
    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_payment_method',
            'title' => 'Tipus Moviments',
            'base' => array(
                'id'        => array('autoKey', 'required', 'hidden'),
                'description'      => array(
                    'title' => 'Moviment',
                    'length' => 50,
                    'required'
                ),
                'details'      => array(
                    'title' => 'Descripció',
                    'length' => 255,
                    'required'
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
