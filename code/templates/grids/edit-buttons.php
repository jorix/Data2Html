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
            'content-template' => 'html-empty',
            'items' => [
                [
                    'content-template' => 'button',
                    'icon' => 'copy',
                    'visualClassBody' => 'btn-outline-secondary',
                    'title' => '$Copy',
                    'description' => null,
                    'action' => 'show-copy'
                ],
                [
                    'content-template' => 'button',
                    'icon' => 'remove',
                    'visualClassBody' => 'btn-outline-danger',
                    'title' => '$Delete',
                    'description' => null,
                    'action' => 'show-delete'
                ]
            ]
        ]
    ]
];
