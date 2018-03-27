<?php
$return = function($replaces) {
    $rx = new Data2Html_Collection($replaces, true); // Required
    
    $model = Data2Html_Handler::getModel($rx->getString('model'));
    $gridName = $rx->getString('grid', 'main');
    
    $grid = $model->getLinkedGrid($gridName);
    $templateGridName = $grid->getAttribute('template', 'edit-grid-paged');
    $formName = $grid->getAttribute('element-name', 'main');
    
    $form = $model->getLinkedElement($formName);
    $templateFormName = $grid->getAttribute('template', 'edit-form');
    
    $render = Data2Html_Handler::createRender();
    // Grid    
    $result = $render->renderGrid($model, $gridName, $templateGridName);
    $idGrid = $result['id'];
    $jsCode = $result['js'];
    $htmlCode = $result['html'];
        
    // Form edit
    $result = $render->renderElement($model, $formName, $templateFormName);
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
