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
                'uf_name'   => array('value' => '$${name}#$${id}', 'sortBy' => 'name'),
                'mentor_name_val'   => '=$${mentor_uf[uf_name]}',                  
                'active'    => array('boolean', 'required', 'default' => true),
                'mentor_uf' => array('link' => 'aixada_ufs:list'),
                'created'   => array('date', 'format' => 'dd-MM-yyyy'),
                'mentor_name' =>  array(
                    'title' => 'UF mentora',
                    'base'=>'mentor_uf[uf_name]'
                ),
            ),
            'grids' => array(
                'list' => array(
                    'columns' => array('value' =>'id', 'text' => '=$${name}#$${id}', 'active'),
                    'filter' => array(
                        'fieldLayouts' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                ),
                'only_name' => array(
                    'columns' => array('id', 'name'),
                    'filter' => array(
                        'fieldLayouts' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                ),
                'account' => array(
                    'sort' => 'name',                    
                    'columns' => array(
                        'value' => array('key', 'db' => '1000 + id'),
                        'text' => '=$${name}#$${value}',
                        'active',
                        'mentor_uf'
                    ),
                    'filter' => array(
                        'fieldLayouts' => 'inline',
                        'fields' => array('active' => 'EQ')
                    )
                ),
                'default' => array(
                   // 'layout' => 'grid_man',
                    'sort' => 'name', 
                    'filter' => array(
                        'fieldLayouts' => 'inline',
                        'fields' => array(
                            //'active' => 'EQ',
                            //'mentor_uf[name]' => 'LK',
                            'name' => 'LK'
                            // 'aaaa' => array('db'=>'mentor_uf[name]' ,'check'=> 'LK'),
                            // '_niste' => array('default'=>1005),
                        )
                    )
                )
            ),
            'forms' => array(
                'default' => array(
                   // 'layout' => 'lines',
                )
            )
        );
    }
}
