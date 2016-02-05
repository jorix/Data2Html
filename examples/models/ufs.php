<?php
class aixada_ufs extends Data2Html {

    protected function init()
    {
        $this->parse();
    }

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_uf',
            'title' => 'Unitats familiars',
            'fields' => array(
                'id'        => array('autoKey', 'required'),
                'account_id'=> array('db' => '1000+id', 'integer'),
                'name'      => array('label' => 'Nom',
                    'maxLength' => 255, 'required'
                ),
                'active'    => array('boolean', 'required', 'default' => true),
                'created'   => array('date', 'format' => 'dd-MM-yyyy'),
                'mentor_uf' => array('label' => 'UF mentora',
                    'foreignKey' => 'ufs'
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
            'list' => array('id', '$${name} #$${id}', 'active'),
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
