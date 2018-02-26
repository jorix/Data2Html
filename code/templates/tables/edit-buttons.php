<?php
$return = [
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
                    ],
                    [
                        'layout-template' => 'bare',
                        'content-template' => 'html-message'
                    ]
                ]
            ],
            'content-template' => 'empty',
            'items' => [
                [
                    'content-template' => 'button',
                    'icon' => 'edit',
                    'visualClassBody' => 'btn-default',
                    'title' => '$Edit',
                    'description' => null,
                    'action' => 'show-edit'
                ],
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
