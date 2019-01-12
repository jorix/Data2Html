<?php
return [
    'items' => [
        [
            'layout-template' => 'no_label',
            'content-template' => 'button',
            'visual-size' => 0,
            'icon' => 'refresh',
            'title' => '__{refresh-page}',
            'description' => null,
            'action' => 'read-page'
        ],
        'pageSize' => [
            'layout-template' => 'no_label',
            'content-template' => 'text-input',
            'integer' => 2,
            'default' => 10,
            'type' => 'integer'
        ],
        [
            'layout-template' => 'no_label',
            'content-template' => 'button',
            'visual-size' => 0,
            'icon' => 'forward',
            'title' => '__{next-page}',
            'action' => 'next-page'
        ]
    ]
];
