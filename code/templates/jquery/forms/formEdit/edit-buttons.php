<?php
$return = array(
    'items' =>array(
        array(
            'layout-template' => 'bare',
            'content-template' => 'empty',
            'items' => array(    
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-backward',
                    'title' => '$Previus_PHP',
                    'description' => null,
                    'action' => 'readPage'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'step-forward',
                    'title' => '$Next',
                    'description' => null,
                    'action' => 'readPage'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'description' => null,
                    'action' => 'delete'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'ok',
                    'title' => '$Save',
                    'description' => null,
                    'action' => 'save'
                ),
                array(
                    'layout-template' => 'bare',
                    'content-template' => 'button',
                    'icon' => 'arrow-left',
                    'title' => '$Back',
                    'description' => null,
                    'action' => 'back'
                )
            )
        )
    )
);
