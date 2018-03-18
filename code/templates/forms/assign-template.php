<?php
$return = array(
    "assign-template" => function($render, $item) {
        $itemDx = new Data2Html_Collection($item);
        
        $level = $itemDx->getInteger('level', 0);
        $layout = $itemDx->getString(
            'layout-template',
            $level ? 'base_1' : 'base'
        );
        $content = 'text-input';
        $content = $itemDx->getString('content-template', $content);
        
        $type = $itemDx->getString('type');
        $link = $itemDx->getString('link');
        $leafs = $itemDx->getString('leafs');
        $url = '';
        if ($content === 'hidden-input') {
            $layout = 'bare';
        } elseif ($link) {
            $content = 'select-input';
            $url = $render->getControllerUrl() . "model={$link}&";
        } elseif ($leafs) {
            $layout = 'bare';
            $content = 'edit-leafs';
            $url = $render->getControllerUrl() . "model={$leafs}&";
        } elseif ($type) {
            $typeToInputTemplates = array(
                'boolean' =>    'checkbox',
                'date' =>       'datetimepicker'
            );
            $contentToLayout = array(
                'checkbox' =>    'checkbox'
            );
            $content = Data2Html_Value::getItem(
                $typeToInputTemplates,
                $type,
                'text-input'
            );
            $layout = Data2Html_Value::getItem(
                $contentToLayout,
                $content,
                $layout
            );
        }
        $replaces = array(
            'url' => $url
        );
        // Return
        return array($layout, $content, $replaces);
    }
);
