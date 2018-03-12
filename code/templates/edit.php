<?php
$return = function($replaces) {
    $rx = new Data2Html_Collection($replaces, true); // Required
    
    $model = Data2Html_Handler::createModel($rx->getString('model'));
    $gridName = $rx->getString('grid', 'main');
    
    $grid = $model->getGrid($gridName);
    $templateGridName = $grid->getAttribute('template', 'edit-grid-paged');
    $formName = $grid->getAttribute('form-name', 'main');
    
    $form = $model->getForm($formName);
    $templateFormName = $grid->getAttribute('template', 'edit-form');
    
    $render = Data2Html_Handler::createRender();
    // Grid    
    $result = $render->renderGrid($model, $gridName, $templateGridName);
    $idGrid = $result['id'];
    $jsCode = $result['js'];
    $htmlCode = $result['html'];
        
    // Form edit
    $result = $render->renderForm($model, $formName, $templateFormName);
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
                new d2h_display({
                    items: {
                        grid: '#{$idGrid}',
                        detail: '#{$idForm}'
                    }
                });
                d2h_display.loadGrid('#{$idGrid}'); // TODO !!!!!!!!!!!!!!!!!!!!!!!!!!!!!
            })();"
    ];
};
