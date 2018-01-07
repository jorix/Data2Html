<?php
$return = array(
    'table' => 'aixada_uf',
    'title' => 'Unitats familiars',
    'sort' => 'uf_name',
    'items' => array(
        'id'        => array('key', 'integer'),
        'name'      => array(
            'title' => 'Nom UF',
            'string'=> 255,
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
       // 'members' => array('leafs' => 'aixada_members:main'),
        'mentor_name' =>  array(
            'title' => 'UF mentora',
            'base' => 'mentor_uf[uf_name]'
        ),
    ),
    'beforeInsert' => function ($set, $db, &$values) {
        $values['id'] = $db->getValue('select max(id) + 1 from aixada_uf', 'integer');
        return true;
    },
    'grids' => array(
        'list' => array(
            'items' => array('uf_name'),
            'filter' => array('items' => array('=active'))
        ),
        'account' => array(
            'items' => array(
                'value' => array('key', 'db' => '1000 + id'),
                'uf_name'
            ),
            'filter' => array(
                'items' => array('=active')
            )
        ),
        'main' => array(
            'items' => array('active', 'uf_name', 'created', 'mentor_name'),
            'filter' => array(
                'items' => array(
                    '%name', '=active', '=mentor_uf'
                )
            )
        )
    ),
    'forms' => array(
        'main' => array(
            'items' => array(
                'name' => array('items' => array('id', 'created', 'active')),
                'mentor_uf'
            )
        )
    )
);
