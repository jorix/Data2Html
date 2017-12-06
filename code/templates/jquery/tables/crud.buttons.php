<?php
$return = array(
    'fields' =>array(
        array(
            'head-content-template' => 'empty',
            'head-items' => array(
                array(
                    'head-content-template' => 'button',
                    'icon' => 'plus',
                    'title' => '$Add',
                    'description' => null,
                    'action' => 'create'
                )
            ),
            'content-template' => 'empty',
            'items' => array(
                array(
                    'content-template' => 'button',
                    'icon' => 'edit',
                    'title' => '$Edit',
                    'description' => null,
                    'action' => 'edit'
                ),
                array(
                    'content-template' => 'button',
                    'icon' => 'duplicate',
                    'title' => '$Copy',
                    'description' => null,
                    'action' => 'copy'
                ),
                array(
                    'content-template' => 'button',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'description' => null,
                    'action' => 'delete'
                )
            )
        )
    )
);
