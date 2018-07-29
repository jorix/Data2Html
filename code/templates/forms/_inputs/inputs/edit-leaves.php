<?php
return function($replaces) {
    $urlParts = explode('?', \Data2Html\Data\Lot::getItem('url', $replaces));
    if (count($urlParts) <= 1) {
        throw new \Data2Html\DebugException(
            'The url arguments is required and must has a query.',
            $replaces
        );
    }
    $urlRequest = null;
    parse_str($urlParts[1], $urlRequest);
    
    $modelNames = \Data2Html\Handler::parseRequest($urlRequest);
    $rx = new \Data2Html\Data\Lot($modelNames, true); // Required
    
    $modelName = $rx->getString('model');
    $model = \Data2Html\Handler::getModel($modelName);
    $gridName = $rx->getString('grid', 'main');
    
    $grid = $model->getLinkedGrid($gridName);
    $blockName = $grid->getAttribute('block-name', 'main');
    $templateGridName = $grid->getAttribute('template', 'edit-grid-paged');
    
    $form = $model->getLinkedBlock($blockName);
    $templateFormName = $form->getAttribute('template', 'forms/edit/edit');
    
    $itemReplaces = [
        'branch' => [
            'model' => $modelName,
            'grid' => $gridName,
            'block' => $blockName
        ]
    ];
    
    $render = \Data2Html\Handler::createRender();
    
    // Grid
    $result = $render->renderGrid($model, $gridName, $templateGridName, $itemReplaces);
    $idGrid = $result->get('id');
        
    // Block of edit form
    $resultBlock = $render->renderBlock($model, $blockName, $templateFormName, $itemReplaces);
    $idForm = $resultBlock->get('id');
    $result->add($resultBlock);
    
    $displayOptions = [
        'auto' => 'loadGrid',
        'items' => [
            'grid' => ['selector' => '#' . $idGrid],
            'element' => ['selector' => '#' . $idForm]
        ]
    ];
    
    // Put display options of branch
    if (array_key_exists('branch', $replaces)) {
        $branch = $replaces['branch'];
        $itemToLink = $model
            ->getLinkedBlock($branch['block'])
            ->searchItemByLink($branch['model']);
        $itemDbToLink = \Data2Html\Data\Lot::getItem('db', $itemToLink);
        
        $branchModel = \Data2Html\Handler::getModel($branch['model']);
        $filterItemNames = $grid->getFilter()->searchItemNameByDb($itemDbToLink);
        $formItemNames = $form->searchItemNameByDb($itemDbToLink);

        $displayOptions['branch'] = '#' . $branchModel->getLinkedGrid($branch['grid'])->getId();
        $displayOptions['items']['grid']['leafKeys'] = $filterItemNames;
        $displayOptions['items']['element']['leafKeys'] = $formItemNames;
    }
    
    // end
    return new \Data2Html\Render\Content(
        [
            'include' => ['d2h_server', 'd2h_display'],
            'html' => '<div class="container">$${body}</div>',
            'js' => "
                (function() {
                    d2h_display(" . 
                    \Data2Html\Data\To::json($displayOptions, true)
                    . ");
                })();"
        ], [
            'body' => $result
        ]
    );
};
