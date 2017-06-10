<?php
class aixada_ufs_self extends Data2Html_Model {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_uf',
            'title' => 'Unitats familiars',
            'base' => array(
                'id'        => array('autoKey', 'required'),
                'name'      => array(
                        'title' => 'Nom UF',
                        'length'=> 255, 'required'
                ),
                'uf_name'   => '=$${name}#$${id}',
                'mentor_uf' => array('link' => 'aixada_ufs_self:list'),
                'mentor_name' =>  array(
                    'title' => 'UF mentora',
                    'db'=>'mentor_uf[uf_name]' // Falla: al usar vinculat i plantilla no posa alias al derivats!
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
                    'columns' => array('id', 'name'),
                    'filter' => array()
                ),
                'default' => array(
                    'columns' => array(),
                    'filter' => array()
                )
            )
        );
    }
}
