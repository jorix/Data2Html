<?php
return [
    'items' => [
        [
            'layout-template' => 'no_label',
            'content-template' => 'empty',
            'items' => [    
                [
                    'layout-template' => 'no_label',
                    'content-template' => 'button',
                    'icon' => 'arrow-left',
                    'visualClassBody' => 'btn-warning',
                    'title' => '__{show-grid}',
                    'description' => null,
                    'action' => 'show-grid'
                ],
                [
                    'layout-template' => 'no_label',
                    'content-template' => 'button',
                    'icon' => 'step-backward',
                    'visualClassBody' => 'btn-link',
                    'visualClassLayout' => 'd2h_dsp_move',
                    'description' => null,
                    'action' => 'read-previous'
                ],
                [
                    'layout-template' => 'no_label',
                    'content-template' => 'button',
                    'icon' => 'step-forward',
                    'visualClassBody' => 'btn-link',
                    'visualClassLayout' => 'd2h_dsp_move',
                    'description' => null,
                    'action' => 'read-next'
                ],
                [
                    'layout-template' => 'no_label',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-danger',
                    'visualClassLayout' => 'd2h_dsp_delete',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'action' => 'delete'
                ],
                [
                    'layout-template' => 'no_label',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success',
                    'visualClassLayout' => 'd2h_dsp_update',
                    'icon' => 'ok',
                    'title' => '$Save',
                    'action' => 'save'
                ],
                [
                    'layout-template' => 'no_label',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success',
                    'visualClassLayout' => 'd2h_dsp_insert',
                    'icon' => 'ok',
                    'title' => '$Create',
                    'action' => 'create'
                ]
            ]
        ]
    ]
];
