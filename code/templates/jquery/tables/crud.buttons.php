<?php
$return = array(
    array(
        'layouts' => array(
            array(
                'layouts' => array('blank', 'base'),
                'input' => 'button',
                'icon' => 'plus',
                'title' => '$Add',
                'description' => null,
                'action' => 'create'
            ), 
            'base'
        ),
        'input' => 'button',
        'icon' => 'edit',
        'title' => '$Edit',
        'description' => null,
        'action' => 'edit'
    ),
    array(
        'layouts' => array('blank', 'base'),
        'input' => 'button',
        'icon' => 'duplicate',
        'title' => '$Copy',
        'description' => null,
        'action' => 'copy'
    ),
    array(
        'layouts' => array('blank', 'base'),
        'input' => 'button',
        'icon' => 'remove',
        'title' => '$Delete',
        'description' => null,
        'action' => 'delete'
    )
);
