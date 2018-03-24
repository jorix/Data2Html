<?php
$return = function($replaces) {
    $rx = new Data2Html_Collection($replaces, true); // Required
    
    $urlRequest = null;
    parse_str(explode('?', $rx->getString('url'))[1], $urlRequest);
    $modelNames = Data2Html_Handler::parseRequest($urlRequest);
    $model = Data2Html_Handler::createModel(
        Data2Html_Value::getItem($modelNames, 'model')
    );
    $gridName = Data2Html_Value::getItem($modelNames, 'grid', 'main');
    
    $grid = $model->getGrid($gridName);
    $templateGridName = $grid->getAttribute('template', 'edit-grid-paged');
    $formName = $grid->getAttribute('element-name', 'main');
    
    $form = $model->getElement($formName);
    $templateFormName = $form->getAttribute('template', 'edit-form');
    
    
    
    
    
    
    
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
    
    $displayOptions = [
        'auto' => 'loadGrid',
        'items' => [
            'grid' => '#' . $idGrid,
            'element' => '#' . $idForm
        ]
    ];
    
    return [
        'd2hToken_content' => true,
        'rrrr' => '555',
        'html' => 
            "<div class=\"container\">
                {$htmlCode}
            </div>",
        'js' =>
            "{$jsCode}
            (function() {
                d2h_display.create(" . 
                Data2Html_Value::toJson($displayOptions)
                . ");
            })();"
    ];
};
