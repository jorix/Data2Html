<?php
$return = array(
    'items' =>array(
        array(
            'head-item' => array(
                'content-template' => 'button',
                'icon' => 'plus',
                'visualClassBody' => 'btn-success',
                'title' => '$Add',
                'description' => '$Add',
                'action' => 'create'
            ),
            'content-template' => 'empty',
            'items' => array(
                array(
                    'content-template' => 'button',
                    'icon' => 'edit',
                    'visualClassBody' => 'btn-info',
                    'title' => '$Edit',
                    'description' => null,
                    'action' => 'edit'
                ),
                array(
                    'content-template' => 'button',
                    'icon' => 'duplicate',
                    'visualClassBody' => 'btn-default',
                    'title' => '$Copy',
                    'description' => null,
                    'action' => 'copy'
                ),
                array(
                    'content-template' => 'button',
                    'icon' => 'remove',
                    'visualClassBody' => 'btn-danger',
                    'title' => '$Delete',
                    'description' => null,
                    'action' => 'delete'
                )
            )
        )
    )
);
