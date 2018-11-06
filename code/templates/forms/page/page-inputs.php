<?php
return array(
    'items' => array(
        array(
            'layout-template' => 'bare',
            'content-template' => 'button',
            'icon' => 'refresh',
            'title' => '__{refresh-page}',
            'description' => null,
            'action' => 'read-page'
        ),
        'pageSize' => array(
            'content-template' => 'text-input',
            'default' => 10,
            'type' => 'integer'
        ),
        array(
            'layout-template' => 'bare',
            'content-template' => 'button',
            'icon' => 'forward',
            'title' => '__{next-page}',
            'action' => 'next-page'
        )
    )
);
