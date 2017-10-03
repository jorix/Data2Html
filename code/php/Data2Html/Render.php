<?php

class Data2Html_Render
{
    public $debug = false;
    protected $templateObj;
    protected $idRender;
    protected $typeToInput = array(
        'boolean' =>    'checkbox',
        'date' =>       'datetimepicker'
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
    
    protected function createIdRender() {
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
                'visual' => $klColumns->getVisualItemsJson(),
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
                'visual' => $lkForm->getVisualItemsJson(),
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
        
        $columns = array_merge(
            $this->templateObj->getTemplateItems('startItems', $templateTable),
            $columns,
            $this->templateObj->getTemplateItems('endItems', $templateTable)
        );
        
        $templateHeads =
            $this->templateObj->getTemplateBranch('heads', $templateTable);
        $templateHeadsLayouts =
            $this->templateObj->getTemplateBranch('heads_layouts', $templateTable);
        $templateCells =
            $this->templateObj->getTemplateBranch('cells', $templateTable);
        $templateCellsLayouts =
            $this->templateObj->getTemplateBranch('cells_layouts', $templateTable);
            
        // Use optional input template
        $templateInputs =
            $this->templateObj->getTemplateBranch('inputs', $templateTable, false);
        if ($templateInputs) {
            $templateInputsLayouts =
                $this->templateObj->getTemplateBranch('inputs_layouts', $templateTable);
        }
        
        $thead = array();
        $tbody = array();
        $renderCount = 0;
        $vDx = new Data2Html_Collection();
        $svDx = new Data2Html_Collection();
        foreach ($columns as $k => $v) {
            $vDx->set($v);
            if ($vDx->getBoolean('virtual')) {
                continue;
            }
            if ($display = $vDx->getArray('display')) {
                if (array_search('none', $display) !== false) {
                    continue;
                }
            }
            
            ++$renderCount;
            $inputTplName = $vDx->getString('input');
            $type = $vDx->getString('type');
            $iReplaces = array(
                'id' => null,
                'name' => $k,
                'format' => $vDx->getString('format'),
                'type' => $vDx->getString('type'),
                'title' => $vDx->getString('title'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action'),
                'description' => $vDx->getString('description')
            );
            if ($inputTplName) {
                $layouts = $vDx->getArray('layouts', array('blank', 'base'));
            } else {
                $layouts = $vDx->getArray('layouts', array('base', 'base'));
            }
            $isSorted = !!$vDx->getArray('sortBy');
            if ($isSorted) {
                $layouts[0] = 'sortable';
            }
            
            // head
            $lReplaces = $iReplaces;
            if (is_array($layouts[0]) && $templateInputs) {
                // TODO: Make it generic
                $svDx->set($layouts[0]);
                $sinputTplName = $svDx->getString('input');
                $lReplaces['html'] = $this->templateObj->renderTemplateItem(
                    $sinputTplName,
                    $templateInputs,
                    array(
                        'id' => null,
                        'name' => null,
                        'format' => $svDx->getString('format'),
                        'type' => $svDx->getString('type'),
                        'title' => $svDx->getString('title'),
                        'icon' => $svDx->getString('icon'),
                        'action' => $svDx->getString('action'),
                        'description' => $svDx->getString('description')
                    )
                );
            } else {
                $lReplaces['html'] = $this->templateObj->renderTemplateItem(
                    $layouts[0],
                    $templateHeads,
                    $iReplaces
                );
            }
            $this->templateObj->concatContents(
                $thead,
                $this->templateObj->renderTemplateItem(
                    'base',
                    $templateHeadsLayouts,
                    $lReplaces
                )
            );
            
            // body
            $class = '';
            $ngClass = '';
            switch ($type) {
                case 'integer':
                case 'number':
                case 'currency':
                    $class .= 'text-right';
            }
            if ($visual = $vDx->getString('visualClass')) {
                if (strpos($visual, ':') !== false) {
                    $ngClass = '{'.str_replace(':', ":item.{$k}", $visual).'}';
                } else {
                    $class .= ' '.$visual;
                }
            }
            
            $bReplaces = $iReplaces + array(
                'class' => $class,
                'ngClass' => $ngClass
            );
            if ($inputTplName && $templateInputs) {
                $bReplaces['html'] = $this->templateObj->renderTemplateItem(
                    $inputTplName,
                    $templateInputs,
                    $iReplaces
                );
            } else {
                $bReplaces['html'] = $this->templateObj->renderTemplateItem(
                    $layouts[1],
                    $templateCells,
                    $iReplaces
                );
            }
            $this->templateObj->concatContents(
                $tbody,
                $this->templateObj->renderTemplateItem(
                    'base',
                    $templateCellsLayouts,
                    $bReplaces
                )
            );
        }
        $replaces = array_merge($replaces, array(
            'thead' => $thead,
            'tbody' => $tbody,
            'colCount' => $renderCount
        ));
        return $this->templateObj->renderTemplate($templateTable, $replaces);
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
        $fieldsDs = array_merge(
            $this->templateObj->getTemplateItems('startItems', $templateBranch),
            $fieldsDs,
            $this->templateObj->getTemplateItems('endItems', $templateBranch)
        );
        if (count($fieldsDs) === 0) {
            return $this->templateObj->emptyRender();
        }

        $baseUrl = $this->getControllerUrl();
        $templateInputs =
            $this->templateObj->getTemplateBranch('inputs', $templateBranch);
        $templateLayouts =
            $this->templateObj->getTemplateBranch('inputs_layouts', $templateBranch);
        $defaultFieldLayout = Data2Html_Value::getItem($formDs, 'fieldLayouts', 'base');
        $body = array();
        $defaults = array();
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
            if (!$inputTplName) {
                if ($link) {
                    $inputTplName = 'ui-select';
                    $url = $baseUrl . 'model=' . $link . '&';
                } else {
                    $inputTplName = Data2Html_Value::getItem(
                        $this->typeToInput,
                        $type,
                        'text'
                    );
                }
            }
            $default = Data2Html_Value::getItem($v, 'default');
            $fReplaces = array(
                'formId' => $formId,
                
                'id' => $this->createIdRender(),
                'name' => $k,
                'title' => $vDx->getString('title'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action'),
                'description' => $vDx->getString('description'),
                
                'default' => $default,
                'url' => $url,
                'validations' => implode(' ', $validations)
            );
            $fReplaces['html'] = $this->templateObj->renderTemplateItem(
                $inputTplName, $templateInputs, $fReplaces
            );
            $this->templateObj->concatContents(
                $body,
                $this->templateObj->renderTemplateItem(
                    $vDx->getString('layout', $defaultFieldLayout),
                    $templateLayouts, 
                    $fReplaces
                )
            );
            if ($default !== null) {
                $defaults[$k] = $default;
            }
            ++$renderCount;
        }
        $replaces = array_merge($replaces, array(
            'id' => $formId,
            'body' => $body,
            'defaults' => $defaults
        ));
        $form = $this->templateObj->renderTemplate(
            $templateBranch,
            $replaces
        );
        return $form;
    }
}
