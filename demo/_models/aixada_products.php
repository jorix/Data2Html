<?php
class aixada_products extends Data2Html_Model {
    
    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_product',
            'base' => array(
                'id'            => array('integer', 'autoKey', 'required'),
                'provider_id'           => array('link' => 'providers'),
                'name'                  => array('length' => 255, 'required'),
                'description'           => array('length' => null),
                'barcode'               => array('length' => 50),
                'custom_product_ref'    => array('length' => 100, 'required'),
                'active'                => array('boolean', 'required', 'default' => true),
                'responsible_uf_id'     => array('link' => 'ufs'),
                'orderable_type_id'     => array('link' => 'orderable_types', 'default' => 2, 'required'),
                'order_min_quantity'    => array(
                    'number' => array(10,4),
                    'format' => 4,
                    'visualClass' => 'red:<0'
                ),
                'ts'                    => array('date', 'format' => 'dd-MM-yy HH:mm')// 'medium'
            ),
            'grids' => array(
                'default' => array(
                )
            ),
            'constraints' => (
                array('uniqueKey' => array('custom_product_ref', 'provider_id'))
            )
        );
/*

 create table aixada_product (
     	              	int				not null auto_increment,
  provider_id        	int 			not null,
  name	     	      	varchar(255) 	not null,
  description	      	text,
  barcode 	 			varchar(50)		default null,
  custom_product_ref	varchar(100)	default null,		
  active     	      	tinyint			default 1,
  responsible_uf_id     int             default null,
  orderable_type_id		tinyint			default 2,
  order_min_quantity	decimal(10,4)	default 0,
  category_id	      	int				default 1,
  rev_tax_type_id		tinyint			default 1,
  iva_percent_id  	    smallint 		default 1,
  unit_price       		decimal(10,2) 	default 0.0,
  unit_measure_order_id	tinyint			default 1,
  unit_measure_shop_id	tinyint			default 1,
  stock_min    	      	decimal(10,4) 	default 0, 
  stock_actual 	      	decimal(10,4) 	default 0, 
  delta_stock           decimal(10,4)   default 0,
  description_url 		varchar(255)	default null,	
  picture 				varchar(255) 	default null,
  ts			  		timestamp 		not null default current_timestamp,
  primary key (id),
  foreign key (provider_id)    			references aixada_provider(id) on delete cascade,
          key (active),
  foreign key (responsible_uf_id) 		references aixada_uf(id),
  foreign key (orderable_type_id)   	references aixada_orderable_type(id),
  foreign key (category_id)    			references aixada_product_category(id),
  foreign key (rev_tax_type_id)    		references aixada_rev_tax_type(id),
  foreign key (iva_percent_id)			references aixada_iva_type(id),
  foreign key (unit_measure_order_id) 	references aixada_unit_measure(id),
  foreign key (unit_measure_shop_id) 	references aixada_unit_measure(id),
  		  key (delta_stock),
  unique  key (custom_product_ref, provider_id)
)
*/
    }
}
