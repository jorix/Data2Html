<?php

class Data2Html_Render
{
    public $debug = false;
    private $templateObj;
    private $idRender;
    private $typeToInputTemplates = array(
        '[default]' =>    array('base', 'text'),
        'boolean' =>    array('checkbox', 'checkbox'),
        'date' =>       array('base', 'datetimepicker')
    );
    private $visualWords = array(
        'display', 'format', 'size', 'title', 'type', 'validations', 'default'
    );
    private static $idRenderCount = 0;
    public function __construct($templateName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Render";
        
        $this->idRender = $this->createIdRender();
        $this->templateObj = new Data2Html_Render_Template($templateName);
    }
    
    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = array(
            );
        }
        Data2Html_Utils::dump($this->culprit, $subject);
    }
    
    private function createIdRender() {
        self::$idRenderCount++;
        return 'd2h_' . self::$idRenderCount;
    }

    protected function getControllerUrl()
    {
        return Data2Html_Config::get('controllerUrl') . '?';
    }

    public function renderGrid($model, $gridName)
    {        
        
        $this->culprit = "Render for grid: \"{$model->getModelName()}:{$gridName}\"";
        $lkGrid = $model->getGrid($gridName);
        $lkGrid->createLink();
        
        $tplGrid = $this->templateObj->getTemplateBranch(
            $lkGrid->getAttribute('layout', 'grid'),
            $this->templateObj->getTemplateRoot()
        );
        $gridId = $this->idRender . '_grid_' . $gridName;
        $pageForm = $this->renderFormSet(
            $gridId . '_page',
            $this->templateObj->getTemplateBranch('page', $tplGrid),
            null,
            array(
                'title' => $lkGrid->getAttribute('title'),
            )
        );
        
        $lkFilter = $lkGrid->getFilter();
        if (!$lkFilter) {
            $filterForm = $this->templateObj->emptyRender();
        } else {
            $filterForm = $this->renderFormSet(
                $gridId . '_filter',
                $this->templateObj->getTemplateBranch('filter', $tplGrid),
                $lkFilter->getLinkedItems(),
                array(
                    'title' => $lkGrid->getAttribute('title'),
                )
            );
        }
        $klColumns = $lkGrid->getColumnsSet();
        
        $result = $this->renderTable(
            $this->templateObj->getTemplateBranch('table', $tplGrid),
            $klColumns->getLinkedItems(),
            array(
                $lkGrid->getAttribute('title'),
                'id' => $gridId,
                'url' => $this->getControllerUrl() .
                    "model={$model->getModelName()}:{$gridName}&",
                'sortBy' => $lkGrid->getAttribute('sort'),
                'filter' => $filterForm,
                'page' => $pageForm
            )
        );
        $result['id'] = $gridId;
        return $result;
    }
    
    public function renderForm($model, $formName)
    {
        $this->culprit = "Render for form: \"{$model->getModelName()}:{$formName}\"";
        $lkForm = $model->getForm($formName);
        $lkForm->createLink();
        
        $tplForm = $this->templateObj->getTemplateBranch(
            $lkForm->getAttribute('layout', 'form'),
            $this->templateObj->getTemplateRoot()
        );
        $formId = $this->idRender . '_form_' . $formName;
        
        $result = $this->renderFormSet(
            $formId,
            $this->templateObj->getTemplateBranch('form', $tplForm),
            $lkForm->getLinkedItems(),
            array(
                'title' => $lkForm->getAttribute('title'),
                'url' => $this->getControllerUrl() .
                    "model={$model->getModelName()}&form={$formName}&"
            )
        );        
        $result['id'] = $formId;
        return $result;
    }
    protected function renderTable($templateTable, $columns, $replaces)
    {
        if (!$columns) {
            throw new Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return $this->templateObj->emptyRender();
        }
        
        $heads = array_merge(
            $this->parseFormSet('startItems', $templateTable, null, 'heads'),
            $columns,
            $this->parseFormSet('endItems', $templateTable, null, 'heads')
        );        
        $tHeads = $this->templateObj->getTemplateBranch('heads', $templateTable);
        
        $assingHeads = Data2Html_Value::getItem(
            $tHeads[1], 
            "assignTemplate", 
            function() { return array('base', 'base'); }
        );
        $templateHeads =
            $this->templateObj->getTemplateBranch('contents', $tHeads);
        $templateHeadsLayouts =
            $this->templateObj->getTemplateBranch('layouts', $tHeads);
            
        
        $thead = array();
        $renderCount = 0;
        $vDx = new Data2Html_Collection();
        $previusLevel = -1;
        foreach ($heads as $k => $v) {
            $vDx->set($v);
            if ($vDx->getBoolean('virtual')) {
                continue;
            }
            $display = $vDx->getString('display', 'html');
            if ($display === 'none') {
                continue;
            }
            
            
            $type = $vDx->getString('type');
            $level = $vDx->getInteger('level');
            if (!$level) {
                ++$renderCount;
            }
            $iReplaces = array(
                'id' => $this->createIdRender(),
                'name' => $k,
                'title' => $vDx->getString('title'),
                'description' => $vDx->getString('description'),
                'format' => $vDx->getString('format'),
                'type' => $vDx->getString('type'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action')
            );
            
            // Heads
            $hReplaces = $iReplaces;
            
            $itemTemplates = $assingHeads($this, $v);
            $hReplaces['html'] = $this->templateObj->renderTemplateItem(
                $itemTemplates[1],
                $templateHeads,
                $iReplaces
            );
            $this->templateObj->concatContents(
                $thead,
                $this->templateObj->renderTemplateItem(
                    $itemTemplates[0],
                    $templateHeadsLayouts,
                    $hReplaces
                )
            );
        }
            
        // Cells
        $tbody = $this->renderSet($columns, $templateTable, 'cells', 'items');
        // $cells = array_merge(
            // $this->parseFormSet('startItems', $templateTable),
            // $columns,
            // $this->parseFormSet('endItems', $templateTable)
        // );    
        // $tCells = $this->templateObj->getTemplateBranch('cells', $templateTable);
        // $assingCells = Data2Html_Value::getItem(
            // $tCells, 
            // "assignTemplate", 
            // function() { return array('base', 'base', array()); }
        // );
        // $templateCells =
            // $this->templateObj->getTemplateBranch('contents', $tCells);
        // $templateCellsLayouts =
            // $this->templateObj->getTemplateBranch('layouts', $tCells);
        
        // $tbody = array();
        // $previusLevel = -1;
        // foreach ($cells as $k => $v) {
            // list($tLayout, $tContent, $cReplaces) = $assingCells($this, $v);
            // $cReplaces += array(
                // 'id' => $this->createIdRender(),
                // 'name' => $k,
                // 'title' => $vDx->getString('title'),
                // 'description' => $vDx->getString('description'),
                // 'format' => $vDx->getString('format'),
                // 'type' => $vDx->getString('type'),
                // 'icon' => $vDx->getString('icon'),
                // 'action' => $vDx->getString('action')
            // );
            // $cReplaces['html'] = $this->templateObj->renderTemplateItem(
                // $tLayout,
                // $templateCells,
                // $cReplaces
            // );
            // $this->templateObj->concatContents(
                // $tbody,
                // $this->templateObj->renderTemplateItem(
                    // $tContent,
                    // $templateCellsLayouts,
                    // $cReplaces
                // )
            // );
        // }
        
        // End
        $replaces = array_merge($replaces, array(
            'thead' => $thead,
            'tbody' => $tbody,
            'colCount' => $renderCount,
            'visual' => $this->getVisualItemsJson($columns)
        ));
        return $this->templateObj->renderTemplate($templateTable, $replaces);
        $previusLevel = $level;
    }

    protected function renderSet($setItems, $templateBranch, $templateName, $subItemsKey) {
        $finalItems = array_merge(
            $this->parseFormSet('startItems', $templateBranch, $subItemsKey),
            $setItems,
            $this->parseFormSet('endItems', $templateBranch, $subItemsKey)
        );    
        $template = $this->templateObj->getTemplateBranch($templateName, $templateBranch);
        $assingCells = Data2Html_Value::getItem(
            $template, 
            "assignTemplate", 
            function() { return array('base', 'base', array()); }
        );
        $templContents =
            $this->templateObj->getTemplateBranch('contents', $template);
        $templLayouts =
            $this->templateObj->getTemplateBranch('layouts', $template);
        
        $body = array();
        $previusLevel = -1;
        $vDx = new Data2Html_Collection();
        foreach ($finalItems as $k => $v) {
            $vDx->set($v);
            if ($vDx->getBoolean('virtual')) {
                continue;
            }
            $display = $vDx->getString('display', 'html');
            if ($display === 'none') {
                continue;
            }
            list(
                $layoutTemplName,
                $contentTemplName,
                $replaces
            ) = $assingCells($this, $v);
            $replaces += array(
                'id' => $this->createIdRender(),
                'name' => $k,
                'title' => $vDx->getString('title'),
                'description' => $vDx->getString('description'),
                'format' => $vDx->getString('format'),
                'type' => $vDx->getString('type'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action')
            );
            $replaces['html'] = $this->templateObj->renderTemplateItem(
                $layoutTemplName,
                $templContents,
                $replaces
            );
            $this->templateObj->concatContents(
                $body,
                $this->templateObj->renderTemplateItem(
                    $contentTemplName,
                    $templLayouts,
                    $replaces
                )
            );
        }
        return $body;
    }

    protected function parseFormSet($setName, $templateBranch){
        $items = $this->templateObj->getTemplateItems($setName, $templateBranch);
        if (count($items) === 0) {
            return array();
        } else {
            $tempModel = new Data2Html_Model_Set_Form(null, $setName, $items);
            return $tempModel->getItems();
        }
    }

    protected function renderFormSet(
        $formId,
        $templateBranch,
        $fieldsDs,
        $replaces
    ){
        if (!$fieldsDs) {
            $fieldsDs = array();
        }
        
        $startItems = $this->parseFormSet('startItems', $templateBranch);
        $endItems = $this->parseFormSet('endItems', $templateBranch);
        $fieldsDs = array_merge($startItems, $fieldsDs, $endItems);
        
        if (count($fieldsDs) === 0) {
            return $this->templateObj->emptyRender();
        }

        $baseUrl = $this->getControllerUrl();
        $templateInputs =
            $this->templateObj->getTemplateBranch('inputs', $templateBranch);
        $templateLayouts =
            $this->templateObj->getTemplateBranch('inputs_layouts', $templateBranch);
            
        $defaultInputTemplates = $this->typeToInputTemplates['[default]'];
        
        $body = array();
        $renderCount = 0;
        
        foreach ($fieldsDs as $k => $v) {            
            $vDx = new Data2Html_Collection($v);
            if ($vDx->getBoolean('virtual')) {
                continue;
            }
            $url = $vDx->getString('url', '');
            $validations = $vDx->getArray('validations', array());
            $link = $vDx->getString('link');
            $type = $vDx->getString('type');

            $inputTplName = $vDx->getString('input');
            if ($inputTplName) {
                $inputTemplates = array($defaultInputTemplates[0], $inputTplName);
            } else {
                if ($link) {
                    $inputTemplates = array($defaultInputTemplates[0], 'select');
                    $url = $baseUrl . 'model=' . $link . '&';
                } else {
                    $inputTemplates = Data2Html_Value::getItem(
                        $this->typeToInputTemplates,
                        $type,
                        $defaultInputTemplates
                    );
                }
            }
            $fReplaces = array(
                'formId' => $formId,
                'id' => $this->createIdRender(),
                'name' => $k,
                'title' => $vDx->getString('title'),
                'description' => $vDx->getString('description'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action'),
                'url' => $url,
                'validations' => implode(' ', $validations)
            );
            $fReplaces['html'] = $this->templateObj->renderTemplateItem(
                $inputTemplates[1], $templateInputs, $fReplaces
            );
            $this->templateObj->concatContents(
                $body,
                $this->templateObj->renderTemplateItem(
                    $vDx->getString('layout', $inputTemplates[0]),
                    $templateLayouts, 
                    $fReplaces
                )
            );
            ++$renderCount;
        }
        $replaces = array_merge($replaces, array(
            'id' => $formId,
            'body' => $body,
            'visual' => $this->getVisualItemsJson($fieldsDs)
        ));
        $form = $this->templateObj->renderTemplate(
            $templateBranch,
            $replaces
        );
        return $form;
    }
    
    protected function getVisualItemsJson($lkItems) {
        $visualItems = array();
        foreach ($lkItems as $k => $v) {
            if (!Data2Html_Value::getItem($v, 'virtual')) {
                if (!is_int($k)) {
                    $item = array();
                    $visualItems[$k] = &$item;
                    foreach ($this->visualWords as $w) {
                        $vv = Data2Html_Value::getItem($v, $w);
                        if ($vv) {
                            $item[$w] = $vv;
                        }
                    }
                    unset($item);
                }
            }
        }
        return str_replace('"', "'", Data2Html_Value::toJson($visualItems));
    }
}
