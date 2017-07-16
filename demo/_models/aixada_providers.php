<?php
class aixada_providers extends Data2Html_Model {
    
    protected function definitions()
    {
        return array(
            'table' => 'aixada_provider',
            'base' => array(
                'id' => array('integer', 'autoKey'),
                'name' => array('title' => '$$Provider', 'string' => 255),
                'contact' => array('title' => '$$Provider_contact', 'string' => 255),
                'address' => array('title' => '$$Provider_address', 'string' => 255),
                'nif' => array('title' => '$$Nif', 'string' => 15),
                'zip' => array('title' => '$$Zip', 'string' => 10),
                
                'email' => array('title' => '$$Provider_email', 'email' => 100),
                'text' => array(
                    'sortBy' => null,
                    'value' => '$${name} #$${id}'
                ),
                'active' => array(
                    'type' => 'boolean'
                ),

                'order_send_format' => array('title' => '$$Order_format', 'string' => 25, 'default' => 'default'),

                
  // name	     	  	varchar(255) 	not null,
  // contact           varchar(255)    default null,
  // address			varchar(255)    default null,
  // nif               varchar(15)     default null,
  // zip				varchar(10)		default null,
  // city				varchar(255) 	default null,
  // phone1    	  	varchar(50) 	default null,
  // phone2			varchar(50) 	default null,
  // fax	     	  	varchar(100) 	default null,	
  // email				varchar(100) 	default null,
  // web				varchar(255) 	default null,
  // bank_name 		varchar(255) 	default null, 
  // bank_account 		varchar(40) 	default null,
  // picture 			varchar(255) 	default null,
  // notes  			text 			default null,
  // active     	  	tinyint 		default 1,
  // responsible_uf_id	int     		default null,
  // offset_order_close int			default 4, 			/* default offset closing of order in days*/
  // order_send_format varchar(25)     default 'default',
  // order_send_prices varchar(15)     default 'default',
  // ts			  	timestamp 		not null default current_timestamp,
                
                
                
                
                
                
            ),
            'grids' => array(
                'default' => array(
                    'sort' => 'name',
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array(
                            'name' => 'LK',
                            'active' => 'EQ'
                        )
                    )
                )
            ),
            'forms' => array(
                'default' => array()
            )
        );
    }
}
