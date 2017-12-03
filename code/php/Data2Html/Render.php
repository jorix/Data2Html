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
        
        list($thead, $renderCount) = $this->renderSet(
            array_merge(
                $this->parseFormSet('startItems', $templateTable, 'headItems'),
                $columns,
                $this->parseFormSet('endItems', $templateTable, 'headItems')
            ),
            $this->templateObj->getTemplateBranch('heads', $templateTable)
        );
        list($tbody) = $this->renderSet(
            array_merge(
                $this->parseFormSet('startItems', $templateTable, 'items'),
                $columns,
                $this->parseFormSet('endItems', $templateTable, 'items')
            ),
            $this->templateObj->getTemplateBranch('cells', $templateTable)
        );
        
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

    protected function renderSet($items, $template)
    {
        $assingCells = Data2Html_Value::getItem(
            $template, 
            "assignTemplate", 
            function() { return array('base', 'base', array()); }
        );
        $templContents =
            $this->templateObj->getTemplateBranch('contents', $template);
        $templLayouts =
            $this->templateObj->getTemplateBranch('layouts', $template);
        
        $vDx = new Data2Html_Collection();
        
        $renderSetLevel = function($currentLevel) 
        use(&$renderSetLevel, &$items, &$assingCells, &$vDx, $templContents, $templLayouts)
        {
            $body = array();

            // Declare end previous item function
            $endPreviousItem = function($itemBody, $levelBody, $layoutTemplName, $replaces) 
            use(&$body, $templLayouts) {
                if (!$itemBody) {
                    return;
                }
                if ($levelBody) {
                    $this->templateObj->concatContents($itemBody, $levelBody);
                }
                $replaces['html'] = $itemBody;
                $this->templateObj->concatContents(
                    $body,
                    $this->templateObj->renderTemplateItem(
                        $layoutTemplName,
                        $templLayouts,
                        $replaces
                    )
                );
            };
            
            // Current level
            $itemBody = null;
            $levelBody = null;
            $layoutTemplName = null;
            $replaces = null;
            $renderCount = 0;
            $v = current($items); 
            while ($v !== false) {
                $vDx->set($v);
                $level = $vDx->getInteger('level');
                if ($level < $currentLevel) {
                    break;
                }
                // Finalize previous item
                if ($level === $currentLevel) {
                    $endPreviousItem($itemBody, $levelBody, $layoutTemplName, $replaces);
                }
                
                // Start current item
                $itemBody = null;
                $levelBody = null;
                $layoutTemplName = null;
                $replaces = null;
                if ($level > $currentLevel) {
                    list($levelBody) = $renderSetLevel($level);
                } else {
                    if ($vDx->getBoolean('virtual')) {
                        $v = next($items);
                        continue;
                    }
                    $display = $vDx->getString('display', 'html');
                    if ($display === 'none') {
                        $v = next($items);
                        continue;
                    }
                    list(
                        $layoutTemplName,
                        $contentTemplName,
                        $replaces
                    ) = $assingCells($this, $v);
                    $replaces += array(
                        'id' => $this->createIdRender(),
                        'name' => key($items),
                        'title' => $vDx->getString('title'),
                        'description' => $vDx->getString('description'),
                        'format' => $vDx->getString('format'),
                        'type' => $vDx->getString('type'),
                        'icon' => $vDx->getString('icon'),
                        'action' => $vDx->getString('action')
                    );
                    $itemBody = $this->templateObj->renderTemplateItem(
                        $contentTemplName,
                        $templContents,
                        $replaces
                    );
                    ++$renderCount;
                }
                $v = next($items);
            }
            // Finalize previous item
            $endPreviousItem($itemBody, $levelBody, $layoutTemplName, $replaces);
            return array($body, $renderCount);
        };
        reset($items);
        return $renderSetLevel(0);
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
