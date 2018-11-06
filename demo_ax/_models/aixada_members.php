<?php
return [
    'table' => 'aixada_member',
    'title' => 'Membres',
    'sort' => 'name',
    'items' => [
        'id' => ['autoKey', 'hidden'],
        'custom_member_ref' => [
            'title' => 'Usuari',
            'string' => 100,
            'required'
        ],
        'uf_id' => [
            'title' => 'UF',
            'link' => 'aixada_ufs:list',
            'required'
        ],
        'uf_name' =>  [
            'title' => 'UF',
            'base' => 'uf_id[uf_name]'
        ],
        'name' => [
            'title' => 'Membre',
            'string' => 255,
            'visual-size' => 50,
            'required'
        ],
        'address' => ['email' => 255, 'required'],
        'nif' => ['string' => 15],
        'zip' => ['string' => 10],
        'city' => ['string' => 255, 'required'],
        'phone1' => ['string' => 50, 'required'],
        'phone2' => ['string' => 50],
        'phones' => ['value' => '$${phone1} / $${phone2}', 'sortBy' => 'phone1'],
        'web' => ['string' => 255],
        'active' => ['integer' => 1, 'default' => 1, 'content-template' => 'checkbox'],
        'participant' => ['boolean' => 1, 'default' => true],
        'ts' => [
            'title' => 'Created',
            'date',
            'format' => 'dd-MM-yyyy',
            'default' => '[now]'
        ],
    ],
    'grids' => [
        'list' => ['sort' => 'name', 'items' => ['name']],
        'main' => [
            'items' => [
                'id', 'name', 
                'active' => ['sortBy' => null],
                'uf_name', 'phones'
            ],
            'filter' => [
                'items' => [
                    '%name' => ['required'], '=active', '=uf_id'
                ]
            ]
        ],
        'uf_members' => [
            'block-name' => 'main',
            'options' => ['page' => false],
            'items' => ['name', 'active' => ['sortBy' => null], 'phones'],
            'filter' => [
                'items' => [
                    ['base' => '=uf_id', 'content-template' => 'hidden-input', 'default' => 7]
                ]
            ]
        ]
    ],
    'blocks' => [
        'main' => [
            'items' => [
                [
                    'layout-template' => 'bare',
                    'items' => ['name', 'active', 'ts']
                ],
                'uf_id' => ['content-template' => 'hidden-input'],
                'nif',
                'address',
                'zip', 'city', 
                'phone1', 'phone2'
            ],
        ]
    ]
];
