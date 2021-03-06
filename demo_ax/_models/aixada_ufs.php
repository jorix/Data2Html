<?php
return [
    'table' => 'aixada_uf',
    'title' => 'Unitats familiars',
    'sort' => 'uf_name',
    'items' => [
        'id'        => ['key', 'integer', 'default' => null],
        'name'      => [
            'title' => 'Nom UF',
            'string'=> 255,
            'visual-size' => 50,
            'required'
        ],
        'uf_name'   => [
            'title' => 'Nom UF',
            'value' => '$${name}#$${id}',
            'sortBy' => ['name', 'id']
        ],
        'active'    => ['boolean', 'required', 'default' => true],
        'created'   => ['datetime', 'format' => 'dd-MM-yyyy', 'default' => '[now]'],
        'mentor_uf' => [
            'link' => 'aixada_ufs:list', 
            'list' => [2 => 'dewswi', 3 => 'trisdewswi'] // with values not found
        ],
        'members'   => ['leaves' => 'aixada_members:uf_members'],
        'mentor_name' =>  [
            'title' => 'UF mentora',
            'base' => 'mentor_uf[uf_name]',
            'sortBy' => ['mentor_uf[name]', 'mentor_uf']
        ],
    ],
    'beforeInsert' => function ($set, $db, &$values) {
        $values['id'] = $db->getValue('select max(id) + 1 from aixada_uf', 'integer');
        return true;
    },
    'grids' => [
        'list' => [
            'items' => ['uf_name'],
            'filter' => ['items' => ['%name', '=active']]
        ],
        'mentors' => [
            'sort' => 'mentor_name',
            'summary' => true,
            'items' => ['mentor_name', 'mentor_uf' => ['key']],
            'filter' => ['items' => [
                'q' => ['base' => '%mentor_uf[name]'],
                '=active'
            ]],
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
                    '%name',
                    '=active',
                    '=mentor_uf' => ['link' => 'aixada_ufs:mentors', 'default' => 14]
                ]
            ]
        ]
    ],
    'blocks' => [
        'main' => [
            'items' => [
               // 'id', // TODO => ['content-template' => 'html-span'], 
                [
                    'layout-template' => 'bare',
                    'items' => [
                        'name',
                        'active',
                        'created',
                    ]
                ],
                'mentor_uf',
                'members'
            ]
        ]
    ]
];
