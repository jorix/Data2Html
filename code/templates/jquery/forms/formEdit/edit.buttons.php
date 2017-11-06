<?php
$return = array(
    'fields' =>array(
        array(
            'items' => array(    
                array(
                    'layout' => 'bare',
                    'input' => 'button',
                    'icon' => 'step-backward',
                    'title' => '$Previus_PHP',
                    'description' => null,
                    'action' => 'readPage'
                ),
                array(
                    'layout' => 'bare',
                    'input' => 'button',
                    'icon' => 'step-forward',
                    'title' => '$Next',
                    'description' => null,
                    'action' => 'readPage'
                ),
                array(
                    'layout' => 'bare',
                    'input' => 'button',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'description' => null,
                    'action' => 'delete'
                ),
                array(
                    'layout' => 'bare',
                    'input' => 'button',
                    'icon' => 'ok',
                    'title' => '$Save',
                    'description' => null,
                    'action' => 'save'
                ),
                array(
                    'layout' => 'bare',
                    'input' => 'button',
                    'icon' => 'arrow-left',
                    'title' => '$Back',
                    'description' => null,
                    'action' => 'back'
                )
            )
        )
    )
);
