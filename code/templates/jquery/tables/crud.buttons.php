<?php
$return = array(
    'fields' =>array(
        array(
            'headItems' => array(
                array(
                    'layout' => 'blank',
                    'input' => 'button',
                    'icon' => 'plus',
                    'title' => '$Add',
                    'description' => null,
                    'action' => 'create'
                )
            ),
            'items' => array(
                array(
                    'input' => 'button',
                    'icon' => 'edit',
                    'title' => '$Edit',
                    'description' => null,
                    'action' => 'edit'
                ),
                array(
                    'input' => 'button',
                    'icon' => 'duplicate',
                    'title' => '$Copy',
                    'description' => null,
                    'action' => 'copy'
                ),
                array(
                    'input' => 'button',
                    'icon' => 'remove',
                    'title' => '$Delete',
                    'description' => null,
                    'action' => 'delete'
                )
            )
        )
    )
);
