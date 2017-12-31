<?php
/*
create table aixada_payment_method (
  id   	     	tinyint   not null auto_increment,
  description   varchar(50) not null,
  details	 	varchar(255) default null,
  primary key (id)
) 
)
*/
$return = array(
    'table' => 'aixada_payment_method',
    'title' => 'Tipus Moviments',
    'items' => array(
        'id'        => array('autoKey', 'required', 'hidden'),
        'description'      => array(
            'title' => 'Moviment',
            'string' => 50,
            'required'
        ),
        'details'      => array(
            'title' => 'Descripció',
            'string' => 255,
            'required'
        )
    )
);
