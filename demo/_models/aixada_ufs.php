<?php
class aixada_ufs extends Data2Html_Model {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_uf',
            'title' => 'Unitats familiars',
            'base' => array(
                'id'        => array('autoKey', 'required', 'hidden'),
                'name'      => array(
                        'title' => 'Nom UF',
                        'length'=> 255,
                        'required'
                ),
                'uf_name'   => array(
                    'title' => 'Nom UF',
                    'value' => '$${name}#$${id}',
                    'sortBy' => 'name'
                ),
                'active'    => array('boolean', 'required', 'default' => true),
                'created'   => array('date', 'format' => 'dd-MM-yyyy', 'default' => '[now]'),
                'mentor_uf' => array('link' => 'aixada_ufs:list'),
                'mentor_name' =>  array(
                    'title' => 'UF mentora',
                    'base'=>'mentor_uf[uf_name]'
                ),
            ),
            'grids' => array(
                'list' => array(
                    'columns' => array('id', 'uf_name', 'active'),
                    'filter' => array(
                        'fieldLayouts' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                ),
                'account' => array(
                    'sort' => 'name',                    
                    'columns' => array(
                        'value' => array('key', 'db' => '1000 + id'),
                        'uf_name',
                        'active'
                    ),
                    'filter' => array(
                        'fieldLayouts' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                ),
                'main' => array(
                   // 'layout' => 'grid_man',
                    'sort' => 'name', 
                    'filter' => array(
                        'fieldLayouts' => 'inline',
                        'fields' => array(
                            '%name', '=active', '=mentor_uf'
                        )
                    )
                )
            ),
            'forms' => array(
                'main' => array(
                   // 'layout' => 'lines',
                )
            )
        );
    }
}
