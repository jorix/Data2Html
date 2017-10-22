<?php
class aixada_ufs extends Data2Html_Model {

    protected function definitions()
    {
        #Set database table
        return array(
            'table' => 'aixada_uf',
            'title' => 'Unitats familiars',
            'base' => array(
                'id'        => array('key', 'integer', 'required', 'hidden'),
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
                array('db' => null,'items' => array(
                'active'    => array('boolean', 'required', 'default' => true),
                'created'   => array('date', 'format' => 'dd-MM-yyyy', 'default' => '[now]'),
                )),
                'mentor_uf' => array('link' => 'aixada_ufs:list'),
                'mentor_name' =>  array(
                    'title' => 'UF mentora',
                    'base'=>'mentor_uf[uf_name]'
                ),
            ),
            'grids' => array(
                'list' => array(
                    'sort' => 'name',
                    'columns' => array('uf_name'),
                    'filter' => array(
                        'fields' => array('=active')
                    )
                ),
                'account' => array(
                    'sort' => 'name',
                    'columns' => array(
                        'value' => array('key', 'db' => '1000 + id'),
                        'uf_name'
                    ),
                    'filter' => array(
                        'fields' => array('=active')
                    )
                ),
                'main' => array(
                    'sort' => 'name', 
                    'filter' => array(
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
    
    public function beforeInsert($db, &$values)
    {
        $values['id'] =
            $db->getValue('select max(id) + 1 from  aixada_uf', 'integer');
        return true;
    }
}
