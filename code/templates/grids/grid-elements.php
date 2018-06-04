<?php
return [
    'heads' => [
        'assign-template' => function($render, $item) {
            $itemDx = new Data2Html_Collection($item);
            
            $level = $itemDx->getInteger('level', 0);
            $layout = $level ? 'base_1' : 'base';
            $content = $itemDx->getString('content-template', 'title');
            if ($itemDx->getItem('sortBy')) {
                $content = 'title-sortable';
            }
            return [$layout, $content, []];
        },
        'layouts' => ['templatesFolder' => 'heads_layouts/'],
        'contents' => ['templatesFolder' => ['heads/', '../forms/_inputs/inputs/']]
    ], 
    'cells' => [
        'assign-template' => function($render, $item) {
            $itemDx = new Data2Html_Collection($item);
            
            $level = $itemDx->getInteger('level', 0);
            $layout = $level ? 'base_1' : 'base';
            $content = $itemDx->getString('content-template', 'base');
            
            // Classes
            $typeToHtmlClass = [
                'integer' => 'text-right',
                'number' => 'text-right',
                'float' => 'text-right'
            ];
            $type = $itemDx->getString('type', '');
            $ngClass = '';
            $class = Data2Html_Value::getItem($typeToHtmlClass, $type, '');
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
        'layouts' =>  ['templatesFolder' => 'cells_layouts/'], 
        'contents' => ['templatesFolder' => ['cells/', '../forms/_inputs/inputs/']]
    ]
];
