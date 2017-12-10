<?php
class aixada_ufs_self extends Data2Html_Model {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_uf',
            'title' => 'Unitats familiars',
            'items' => array(
                'id'        => array('autoKey', 'required'),
                'name'      => array(
                        'title' => 'Nom UF',
                        'string'=> 255,
                        'required'
                ),
                'uf_name'   => '=$${name}#$${id}',
                'mentor_uf' => array('link' => 'aixada_ufs_self:list'),
                'mentor_name' =>  array(
                    'title' => 'UF mentora',
                    'db'=>'mentor_uf[uf_name]' // Falla: al usar vinculat i plantilla no posa alias al derivats!
                ),
            ),
            'grids' => array(
                'list' => array(
                    'items' => array('id', 'name'),
                    'filter' => array(
                        'items' => array('name' => 'EQ')
                    )
                ),
                'main' => array(
                    'filter' => array(
                        'items' => array('name' => 'LK')
                    )
                )
            )
        );
    }
}
