<?php
return [
    'table' => 'aixada_uf',
    'title' => 'Unitats familiars',
    'sort' => 'name',
    'items' => [
        'id'        => ['key', 'integer', 'default' => null],
        'name'      => [
            'title' => 'Nom UF',
            'string'=> 255,
            'required'
        ],
        'uf_name'   => [
            'title' => 'Nom UF',
            'value' => '$${name}#$${id}',
            'sortBy' => 'name'
        ],
        'active'    => ['boolean', 'required', 'default' => true],
        'created'   => ['datetime', 'format' => 'dd-MM-yyyy', 'default' => '[now]'],
        'mentor_uf' => ['link' => 'aixada_ufs:list'],
        'members'   => ['leaves' => 'aixada_members:uf_members'],
        'mentor_name' =>  [
            'title' => 'UF mentora',
            'base' => 'mentor_uf[uf_name]'
        ],
    ],
    'beforeInsert' => function ($set, $db, &$values) {
        $values['id'] = $db->getValue('select max(id) + 1 from aixada_uf', 'integer');
        return true;
    },
    'grids' => [
        'list' => [
            'items' => ['uf_name'],
            'filter' => ['items' => ['=active']]
        ],
        'account' => [
            'items' => [
                'value' => ['key', 'db' => '1000 + id'],
                'uf_name'
            ],
            'filter' => [
                'items' => ['=active']
            ]
        ],
        'main' => [
            'template' => 'edit-grid-paged',
            'items' => ['active', 'uf_name', 'created', 'mentor_name'],
            'filter' => [
                'items' => [
                    'name' => [
                        'check' => 'LK',
                        'validations' => ['required' => false]
                    ],
                    '=active', '=mentor_uf'
                ]
            ]
        ]
    ],
    'blocks' => [
        'main' => [
            'items' => [
                'name',
                'id', 
                'created',
                'active',
                'mentor_uf',
                'members'
            ]
        ]
    ]
];
