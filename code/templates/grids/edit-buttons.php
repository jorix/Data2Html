<?php
return [
    'head-items' => [
        [
            'content-template' => 'html-empty',
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
        ]
    ],
    'body-items' => [
        [ // One item whit all buttons
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
