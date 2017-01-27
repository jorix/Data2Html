<?php
class aixada_ufs extends Data2Html_Model {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_uf',
            'title' => 'Unitats familiars',
            'fields' => array(
                'id'        => array('autoKey', 'required', 'hidden'),
                'name'      => array(
                        'title' => 'Nom UF',
                        'length'=> 255, 'required'
                ),
                'uf_name'   => '=$${name}#$${id}',
                'active'    => array('boolean', 'required', 'default' => true),
                'created'   => array('date', 'format' => 'dd-MM-yyyy'),
                'mentor_uf' => array('link' => 'aixada_ufs:list'),
                'mentor_name' =>  array(
                    'title' => 'UF mentora',
                    'db'=>'mentor_uf[uf_name]'
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
                ),
                'default' => array(
                    'columns' => array(),
                    'filter' => array(
                        'layout' => 'inline',
                        'fields' => array(
                            //'active' => 'EQ',
                            'mentor_uf[name]' => 'LK',
                            // 'aaaa' => array('db'=>'mentor_uf[name]' ,'check'=> 'LK'),
                            // '_niste' => array('default'=>1005),
                        )
                    )
                )
            ),
            'forms' => array(
                'default' => array(
                    'columns' => array()
                )
            )
        );
    }
}
