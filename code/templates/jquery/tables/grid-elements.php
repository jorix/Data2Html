<?php
$return = array(
    "template" => "paged.html.php",
    "heads" => array(
        "assign-template" => function($render, $item) {
            $itemDx = new Data2Html_Collection($item);
            
            $level = $itemDx->getInteger('level', 0);
            $layout = $level ? 'base_1' : 'base';
            $content = $itemDx->getString('content-template', 'title');
            if ($itemDx->getItem('sortBy')) {
                $content = 'title-sortable';
            }
            return array($layout, $content, array());
        },
        "layouts" => array(
            "folderTemplates" => "heads_layouts/"
        ),
        "contents" => array(
            "folderTemplates" => array("heads/", "../forms/inputs/")
        )
    ),
    "cells" => array(
        "assign-template" => function($render, $item) {
            $itemDx = new Data2Html_Collection($item);
            
            $level = $itemDx->getInteger('level', 0);
            $layout = $level ? 'base_1' : 'base';
            $content = $itemDx->getString('content-template', 'base');
            
            // Classes
            $typeToHtmlClass = array(
                'integer' => 'text-right',
                'number' => 'text-right',
                'float' => 'text-right'
            );
            $type = $itemDx->getString('type', '');
            $ngClass = '';
            $class = Data2Html_Value::getItem($typeToHtmlClass, $type, '');
            if ($visual = $itemDx->getString('visualClass')) {
                if (strpos($visual, ':') !== false) {
                    $ngClass = '{'.str_replace(':', ":item.{$k}", $visual).'}';
                } else {
                    $class .= ' '.$visual;
                }
            }
            $replaces = array(
                'class' => $class,
                'ngClass' => $ngClass
            );
            
            // Return
            return array($layout, $content, $replaces);
        },
        "layouts" => array(
            "folderTemplates" => "cells_layouts/"
        ),
        "contents" => array(
            "folderTemplates" => array("cells/", "../forms/inputs/")
        )
    )
);
