<?php
$return = array(
    'items' => array(
        array(
            'layout-template' => 'bare',
            'content-template' => 'button',
            'icon' => 'refresh',
            'title' => '$Refres>hàdata',
            'description' => null,
            'action' => 'readPage'
        ),
        'pageSize' => array(
            'layout-template' => 'bare',
            'content-template' => 'text-input',
            'default' => 10,
            'type' => 'integer'
        ),
        array(
            'layout-template' => 'bare',
            'content-template' => 'button',
            'icon' => 'forward',
            'title' => '$Ne.>Ne.>xtàpage',
            'action' => 'nextPage'
        )
    )
);
