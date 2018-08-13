<?php
return [
    "assign-template" => function($render, $item) {
        $itemDx = new \Data2Html\Data\Lot($item);
        
        $layout = $itemDx->getString('layout-template', 'base');
        $content = $itemDx->getString('content-template');
        
        $type = $itemDx->getString('type');
        $link = $itemDx->getString('link');
        
        $visualWidth = $itemDx->get('size', [999])[0];
        $visualWidth = $itemDx->get('visual-size', $visualWidth);
        
        $leaves = $itemDx->getString('leaves');
        $url = '';
        if ($content === 'hidden-input') {
            $layout = 'bare';
        } elseif ($link) {
            $visualWidth = 40;
            $content = 'select-input';
            $url = $render->getControllerUrl() . "model={$link}&";
        } elseif ($leaves) {
            $visualWidth = 999;
            $layout = 'no_label';
            $content = 'edit-leaves';
            $url = $render->getControllerUrl() . "model={$leaves}&";
        } elseif ($type) {
            switch ($type) {
                case 'boolean':
                    $content = 'checkbox';
                    break;
                case 'date':
                    $visualWidth = 10;
                    $content = 'datetimepicker';
                    break;
                case 'datetime':
                    $visualWidth = 16 + 10;
                    $content = 'datetimepicker';
                    break;
                case 'float':
                case 'integer':
                case 'number':
                    $visualWidth = $visualWidth ? $visualWidth : 10;
                    $visualWidth = $visualWidth * 15 / 12;
                    break;
                default:
                    $visualWidth = $visualWidth ? $visualWidth : 60;
            }
            $content = $content ? $content : 'text-input';
        }
        if ($content === 'checkbox') {
            $layout = 'checkbox';
        }
        $content = $content ? $content : 'html-span';
        
        $replaces = [];
        $replaces['url'] = $url;
        if ($visualWidth > 0) {
            $visualWidth = ($visualWidth + 10) / 15; // to bootstrap width.
            if ($visualWidth < 2) {
                $visualWidth = ceil($visualWidth);
            } elseif ($visualWidth <= 10) {
                $visualWidth = floor($visualWidth);
            } else {
                $visualWidth = 10;
            }
            $replaces['visual-width'] = $visualWidth;
        }
        
        // Return
        return array($layout, $content, $replaces);
    }
];
