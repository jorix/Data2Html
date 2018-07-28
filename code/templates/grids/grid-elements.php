<?php
return [
    'heads' => [
        'assign-template' => function($render, $item) {
            $itemDx = new \Data2Html\Data\Lot($item);
            
            $layout = 'base';
            $content = $itemDx->getString('content-template', 'title');
            if ($itemDx->get('sortBy')) {
                $content = 'title-sortable';
            }
            return [$layout, $content, []];
        },
        'layouts' =>    '@@ heads_layouts/',
        'contents' =>   '@@ heads/, ../forms/_inputs/inputs/'
    ], 
    'cells' => [
        'assign-template' => function($render, $item) {
            $itemDx = new \Data2Html\Data\Lot($item);
            
            $layout = 'base';
            $content = $itemDx->getString('content-template', 'base');
            
            // Classes
            $typeToHtmlClass = [
                'integer' => 'text-right',
                'number' => 'text-right',
                'float' => 'text-right'
            ];
            $type = $itemDx->getString('type', '');
            $ngClass = '';
            $class = \Data2Html\Data\Lot::getItem($type, $typeToHtmlClass, '');
            if ($visual = $itemDx->getString('visualClass')) {
                if (strpos($visual, ':') !== false) {
                    $ngClass = '{'.str_replace(':', ':item.{$k}', $visual).'}';
                } else {
                    $class .= ' '.$visual;
                }
            }
            $replaces = [
                'class' => $class,
                'ngClass' => $ngClass
            ];
            
            // Return
            return [$layout, $content, $replaces];
        },
        'layouts' =>  '@@ cells_layouts/',
        'contents' => '@@ cells/, ../forms/_inputs/inputs/'
    ]
];
