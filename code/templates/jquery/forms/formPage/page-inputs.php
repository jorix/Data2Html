<?php
$return = array(
    'fields' => array(
        array(
            'content-template' => 'button',
            'icon' => 'refresh',
            'title' => '$Refres>hàdata',
            'description' => null,
            'action' => 'readPage'
        ),
        'pageSize' => array(
            'content-template' => 'text',
            'default' => 10,
            'type' => 'integer'
        ),
        array(
            'content-template' => 'button',
            'icon' => 'forward',
            'title' => '$Ne.>Ne.>xtàpage',
            'action' => 'nextPage'
        )
    )
);
