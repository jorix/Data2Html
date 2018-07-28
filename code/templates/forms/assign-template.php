<?php
return [
    "assign-template" => function($render, $item) {
        $itemDx = new \Data2Html\Data\Lot($item);
        
        $layout = $itemDx->getString(
            'layout-template',
            'base'
        );
        $content = 'text-input';
        $content = $itemDx->getString('content-template', $content);
        
        $type = $itemDx->getString('type');
        $link = $itemDx->getString('link');
        $leaves = $itemDx->getString('leaves');
        $url = '';
        if ($content === 'hidden-input') {
            $layout = 'bare';
        } elseif ($link) {
            $content = 'select-input';
            $url = $render->getControllerUrl() . "model={$link}&";
        } elseif ($leaves) {
            $layout = 'bare';
            $content = 'edit-leaves';
            $url = $render->getControllerUrl() . "model={$leaves}&";
        } elseif ($type) {
            $typeToInputTemplates = array(
                'boolean' =>    'checkbox',
                'date' =>       'datetimepicker'
            );
            $contentToLayout = array(
                'checkbox' =>    'checkbox'
            );
            $content = \Data2Html\Data\Lot::getItem(
                $type,
                $typeToInputTemplates,
                'text-input'
            );
            $layout = \Data2Html\Data\Lot::getItem(
                $content,
                $contentToLayout,
                $layout
            );
        }
        $replaces = array(
            'url' => $url
        );
        // Return
        return array($layout, $content, $replaces);
    }
];
