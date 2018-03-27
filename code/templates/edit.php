<?php
$return = function($replaces) {
    $rx = new Data2Html_Collection($replaces, true); // Required
    
    $modelName = $rx->getString('model');
    $model = Data2Html_Handler::getModel($modelName);
    $gridName = $rx->getString('grid', 'main');
    
    $grid = $model->getLinkedGrid($gridName);
    $formName = $grid->getAttribute('element-name', 'main');
    $templateGridName = $grid->getAttribute('template', 'edit-grid-paged');
    
    $form = $model->getLinkedElement($formName);
    $templateFormName = $form->getAttribute('template', 'edit-form');
    
    $render = Data2Html_Handler::createRender();
    // Grid
        
    $itemReplaces = [
        'branch' => [
            'model' => $modelName,
            'grid' => $gridName,
            'element' => $formName
        ]
    ];
    
    $result = $render->renderGrid($model, $gridName, $templateGridName, $itemReplaces);
    $idGrid = $result['id'];
    $jsCode = $result['js'];
    $htmlCode = $result['html'];
        
    // Form edit
    $result = $render->renderElement($model, $formName, $templateFormName, $itemReplaces);
    $idForm = $result['id'];
    $jsCode .= $result['js'];
    $htmlCode .= $result['html'];
    
    return [
        'html' => 
            "<div class=\"container\">
                {$htmlCode}
            </div>",
        'js' =>
            "{$jsCode}
            (function() {
                d2h_display.create({
                    auto: 'loadGrid',
                    items: {
                        grid: '#{$idGrid}',
                        element: '#{$idForm}'
                    }
                });
            })();"
    ];
};
