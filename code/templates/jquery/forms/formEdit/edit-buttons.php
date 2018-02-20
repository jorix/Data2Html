<?php
$return = array(
    'items' =>array(
        array(
            'layout-template' => 'no_label',
            'content-template' => 'empty',
            'items' => array(    
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'arrow-left',
                    'visualClassBody' => 'btn-warning',
                    'title' => '$Cancel',
                    'description' => null,
                    'action' => 'show-grid'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-backward',
                    'visualClassBody' => 'btn-link',
                    'visualClassLayout' => 'd2h_move',
                    'title' => '$Previus_PHP',
                    'description' => null,
                    'action' => 'read-previous'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-forward',
                    'visualClassBody' => 'btn-link',
                    'visualClassLayout' => 'd2h_move',
                    'title' => '$Next',
                    'description' => null,
                    'action' => 'read-next'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-danger',
                    'visualClassLayout' => 'd2h_delete',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'action' => 'delete'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success',
                    'visualClassLayout' => 'd2h_update',
                    'icon' => 'ok',
                    'title' => '$Save',
                    'action' => 'save'
                )
                ,
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'visualClassBody' => 'btn-success',
                    'visualClassLayout' => 'd2h_insert',
                    'icon' => 'ok',
                    'title' => '$Create',
                    'action' => 'create'
                )
            )
        )
    )
);
