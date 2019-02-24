<?php
return function($replaces) {
    $urlParts = explode('?', \Data2Html\Data\Lot::getItem('url', $replaces));
    if (count($urlParts) <= 1) {
        throw new \Data2Html\DebugException(
            'The url arguments is required and must has a query.',
            $replaces
        );
    }
    $modelNames = \Data2Html\Model\Models::parseUrl($urlParts[1]);
    $modelName = $modelNames['model'];
    $gridName = $modelNames['grid'];
    
    $grid = \Data2Html\Model\Models::linkGrid($modelName, $gridName);
    $blockName = $grid->getAttribute('block-name', 'main');
    $templateGridName = $grid->getAttribute('template', 'edit-grid-paged');
    
    $block = \Data2Html\Model\Models::linkBlock($modelName, $blockName);
    $templateFormName = $block->getAttribute('template', 'forms/edit/edit');
    
    $itemReplaces = [
        'branch' => [
            'model' => $modelName,
            'grid' => $gridName,
            'block' => $blockName
        ]
    ];
    
    $render = \Data2Html\Handler::createRender();
    
    // Grid
    $result = $render->renderGrid($modelName, $gridName, $templateGridName, $itemReplaces);
    $idGrid = $result->get('id');
        
    // Block of edit
    $resultBlock = $render->renderBlock($modelName, $blockName, $templateFormName, $itemReplaces);
    $idForm = $resultBlock->get('id');
    $result->add($resultBlock);
    
    $displayOptions = [
        'auto' => 'loadGrid',
        'items' => [
            'grid' => ['selector' => '#' . $idGrid],
            'block' => ['selector' => '#' . $idForm]
        ]
    ];
    
    // Put display options of branch
    if (array_key_exists('branch', $replaces)) {
        $branch = $replaces['branch'];
        $itemToLink =  
            \Data2Html\Model\Models::linkBlock($modelName, $branch['block'])
            ->searchLinkOfBranch($branch['model']);
        $itemDbToLink = \Data2Html\Data\Lot::getItem('db', $itemToLink);
        
        $filterItemNames = $grid->getFilter()->searchItemNameByDb($itemDbToLink);
        $formItemNames = $block->searchItemNameByDb($itemDbToLink);

        $displayOptions['branch'] = '#' . 
            \Data2Html\Model\Models::linkGrid($branch['model'], $branch['grid'])
            ->getId();
        $displayOptions['items']['grid']['leafKeys'] = $filterItemNames;
        $displayOptions['items']['block']['leafKeys'] = $formItemNames;
    }
    
    // end
    return new \Data2Html\Render\Content(
        [
            'include' => ['d2h_server', 'd2h_display'],
            'html' => '$${body}',
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
