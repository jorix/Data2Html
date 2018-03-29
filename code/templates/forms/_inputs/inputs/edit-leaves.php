<?php
$return = function($replaces) {
    $rx = new Data2Html_Collection($replaces, true); // Required
    $urlRequest = null;
    parse_str(explode('?', $rx->getString('url'))[1], $urlRequest);
    $modelNames = Data2Html_Handler::parseRequest($urlRequest);
    $rx = new Data2Html_Collection($modelNames, true); // Required
    
    $modelName = $rx->getString('model');
    $model = Data2Html_Handler::getModel($modelName);
    $gridName = $rx->getString('grid', 'main');
    
    $grid = $model->getLinkedGrid($gridName);
    $formName = $grid->getAttribute('element-name', 'main');
    $templateGridName = $grid->getAttribute('template', 'edit-grid-paged');
    
    $form = $model->getLinkedElement($formName);
    $templateFormName = $form->getAttribute('template', 'edit-form');
    
    $itemReplaces = [
        'branch' => [
            'model' => $modelName,
            'grid' => $gridName,
            'element' => $formName
        ]
    ];
    
    $render = Data2Html_Handler::createRender();
    
    // Grid
    $result = $render->renderGrid($model, $gridName, $templateGridName, $itemReplaces);
    $idGrid = $result['id'];
    $jsCode = $result['js'];
    $htmlCode = $result['html'];
        
    // Form edit
    $result = $render->renderElement($model, $formName, $templateFormName, $itemReplaces);
    $idForm = $result['id'];
    $jsCode .= $result['js'];
    $htmlCode .= $result['html'];
    
    $displayOptions = [
        'auto' => 'loadGrid',
        'items' => [
            'grid' => ['selector' => '#' . $idGrid],
            'element' => ['selector' => '#' . $idForm]
        ]
    ];
    
    // branch
    if (array_key_exists('branch', $replaces)) {
        $branch = $replaces['branch'];
        $itemToLink = $model
            ->getLinkedElement($branch['element'])
            ->searchItemByLink($branch['model']);
        $itemDbToLink = Data2Html_Value::getItem($itemToLink, 'db');
        
        $branchModel = Data2Html_Handler::getModel($branch['model']);
        $filterItemNames = $grid->getFilter()->searchItemNameByDb($itemDbToLink);
        $formItemNames = $form->searchItemNameByDb($itemDbToLink);

        $displayOptions['branch'] = '#' . $branchModel->getLinkedGrid($branch['grid'])->getId();
        $displayOptions['items']['grid']['leafKeys'] = $filterItemNames;
        $displayOptions['items']['element']['leafKeys'] = $formItemNames;
    }
    
    // end
    return [
        'd2hToken_content' => true,
        'html' => "<div class=\"container\">{$htmlCode}</div>",
        'js' => $jsCode . "
            (function() {
                d2h_display.create(" . 
                Data2Html_Value::toJson($displayOptions, true)
                . ");
            })();"
    ];
};
