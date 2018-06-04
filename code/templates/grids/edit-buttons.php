<?php
return [
    'items' => [
        [
            'head-item' => [
                'items' => [
                    [
                        'content-template' => 'button',
                        'icon' => 'plus',
                        'visualClassBody' => 'btn-success',
                        'title' => '$Add',
                        'description' => '$Add',
                        'action' => 'show-create'
                    ]
                ]
            ],
            'content-template' => 'empty',
            'items' => [
                [
                    'content-template' => 'button',
                    'icon' => 'duplicate',
                    'visualClassBody' => 'btn-default',
                    'title' => '$Copy',
                    'description' => null,
                    'action' => 'show-copy'
                ],
                [
                    'content-template' => 'button',
                    'icon' => 'remove',
                    'visualClassBody' => 'btn-danger',
                    'title' => '$Delete',
                    'description' => null,
                    'action' => 'show-delete'
                ]
            ]
        ]
    ]
];
