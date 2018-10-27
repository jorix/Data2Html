<?php
return [
    'items' => [
        [
            'layout-template' => 'no_label',
            'content-template' => 'html-empty',
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
                    'visualClassBody' => 'btn-link d2h_dsp_move',
                    'description' => null,
                    'action' => 'read-previous'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-forward',
                    'visualClassBody' => 'btn-link d2h_dsp_move',
                    'description' => null,
                    'action' => 'read-next'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-danger d2h_dsp_delete',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'action' => 'delete'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success d2h_dsp_update',
                    'icon' => 'check',
                    'title' => '$Save',
                    'action' => 'save'
                ],
                [
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success d2h_dsp_insert',
                    'icon' => 'check',
                    'title' => '$Create',
                    'action' => 'create'
                ]
            ]
        ]
    ]
];
