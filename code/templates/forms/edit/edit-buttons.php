<?php
$return = [
    'items' => [
        [
            'layout-template' => 'no_label',
            'content-template' => 'empty',
            'items' => [    
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'arrow-left',
                    'visualClassBody' => 'btn-warning',
                    'title' => '__{show-grid}',
                    'description' => null,
                    'action' => 'show-grid'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-backward',
                    'visualClassBody' => 'btn-link',
                    'visualClassLayout' => 'd2h_move',
                    'description' => null,
                    'action' => 'read-previous'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-forward',
                    'visualClassBody' => 'btn-link',
                    'visualClassLayout' => 'd2h_move',
                    'description' => null,
                    'action' => 'read-next'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-danger',
                    'visualClassLayout' => 'd2h_delete',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'action' => 'delete'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success',
                    'visualClassLayout' => 'd2h_update',
                    'icon' => 'ok',
                    'title' => '$Save',
                    'action' => 'save'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success',
                    'visualClassLayout' => 'd2h_insert',
                    'icon' => 'ok',
                    'title' => '$Create',
                    'action' => 'create'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'html-message'
                ]
            ]
        ]
    ]
];
