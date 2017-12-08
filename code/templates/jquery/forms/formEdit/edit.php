<?php
$return = array(
    "form" => array(
        "folder" => "./",
        "template" => "edit.html.php",
        "startItems" => "edit-buttons.php",
        "endItems" => "edit-buttons.php",
        "assign-template" => function($render, $item) {
            $itemDx = new Data2Html_Collection($item);
            
            $level = $itemDx->getInteger('level', 0);
            $layout = $level ? 'base_1' : 'base';
            
            $type = $itemDx->getString('type', '');
            $url = '';
            if ($type) {
                $link = $vDx->getString('link');
                $url = $vDx->getString('url', '');
                if ($link) {
                    $content = 'select-input';
                    $url = $render->getControllerUrl() . 'model=' . $link . '&';
                } else {
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
            }
            $content = $vDx->getString('content-template', $content);
            $replaces = array(
                'url' => $url
            );
            // Return
            return array($layout, $content, $replaces);
        },
        "layouts" => array(
            "folderTemplates" => "../i_layouts_block/"
        ),
        "contents" => array(
            "folderTemplates" => "../inputs/"
        )
    )
);
