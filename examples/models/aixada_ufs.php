<?php
class aixada_ufs extends Data2Html {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_uf',
            'title' => 'Unitats familiars',
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
            'fields' => array(
                'id'        => array('autoKey', 'required'),
                'name'      => array(
                        'title' => 'Nom UF',
                        'length'=> 255, 'required'
                ),
                'uf_name'   => '=$${name}#$${id}',
                'active'    => array('boolean', 'required', 'default' => true),
                'created'   => array('date', 'format' => 'dd-MM-yyyy'),
                'mentor_uf' => array(
                    'title' => 'UF mentora',
                    'link' => 'aixada_ufs:list'
                ),
            ),
            'constraints' => (
                array('uniqueKey' => 'name')
            ),
            'filter' => array(
                'name' => 'EQ',
                'active' => 'EQ',
                'mentor_uf' => 'EQ',
            ),
            'grids' => array(
                'list' => array(
                    'columns' => array('id', '=$${name}#$${id}', 'active'),
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                ),
                'account' => array(
                    'sort' => 'name',                    
                    'columns' => array(
                        'value' => array('key', 'db' => '1000+id'),
                        'text' => '=$${name}#$${value}',
                        'active',
                        'mentor_uf'
                    ),
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                )
            )
        );
    }
}
