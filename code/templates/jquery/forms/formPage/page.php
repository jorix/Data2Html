<?php 
$return = array(
    'page' => array(
        'folder' => './',
        'template' => 'page.html.php',
        'startItems' => 'page-inputs.php',
        "assign-template" => function($render, $item) {
            $itemDx = new Data2Html_Collection($item);
            
            $level = $itemDx->getInteger('level', 0);
            $layout = $itemDx->getString(
                'layout-template',
                $level ? 'base_1' : 'base'
            );
            $content = 'text-input';
            
            $type = $itemDx->getString('type', '');
            $url = '';
            if ($type) {
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
            $content = $itemDx->getString('content-template', $content);

            // Return
            return array($layout, $content, array());
        },
        "layouts" => array(
            "folderTemplates" => "../i_layouts_block/"
        ),
        "contents" => array(
            "folderTemplates" => "../inputs/"
        )
    )
);