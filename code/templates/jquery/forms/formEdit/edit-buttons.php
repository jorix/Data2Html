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
                    'action' => 'back'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-backward',
                    'visualClassBody' => 'btn-link',
                    'title' => '$Previus_PHP',
                    'description' => null,
                    'action' => 'readPage'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-forward',
                    'visualClassBody' => 'btn-link',
                    'title' => '$Next',
                    'description' => null,
                    'action' => 'readPage'
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
