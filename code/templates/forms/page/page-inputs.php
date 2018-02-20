<?php
$return = array(
    'items' => array(
        array(
            'layout-template' => 'bare',
            'content-template' => 'button',
            'icon' => 'refresh',
            'title' => '_{{refresh-page}}',
            'description' => null,
            'action' => 'read-page'
        ),
        'pageSize' => array(
            'layout-template' => 'bare',
            'title' => '_{{page-size}}',
            'content-template' => 'text-input',
            'default' => 10,
            'type' => 'integer'
        ),
        array(
            'layout-template' => 'bare',
            'content-template' => 'button',
            'icon' => 'forward',
            'title' => '_{{next-page}}',
            'action' => 'next-page'
        )
    )
);
