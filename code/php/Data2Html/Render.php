<?php

class Data2Html_Render
{
    public $debug = false;
    protected $modelObj;
    protected $templateObj;
    protected $idRender;
    private static $idRenderCount = 0;
    public function __construct($templateName, $modelObj)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Render for \"{$modelObj->getModelName()}\"";
        
        $this->idRender = $this->createIdRender();
        $this->modelObj = $modelObj;
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
    public function render($payerNames)
    {
        if (isset($payerNames['form'])) {
            $formName = $payerNames['form'];
            $lkForm = $this->modelObj->getForm($formName);
            $lkForm->createLink();
            
            $tplForm = $this->templateObj->getTemplateBranch(
                $lkForm->getAttribute('layout', 'form'),
                $this->templateObj->getTemplateRoot()
            );
            $formId = $this->idRender . '_form_' . $formName;
            return $this->renderForm(
                $formId,
                $this->templateObj->getTemplateBranch('form', $tplForm),
                $lkForm->getLinkedItems(),
                array(
                    'title' => $lkForm->getAttribute('title'),
                    'url' => $this->getControllerUrl() .
                        "model={$this->modelObj->getModelName()}&form={$formName}&"
                )
            );
        } elseif (isset($payerNames['grid'])) {
            return $this->renderGrid($payerNames['grid']);
        } else {
            throw new Exception("no request object.");
        }
    }

    protected function renderGrid($gridName)
    {        
        $lkGrid = $this->modelObj->getGrid($gridName);
        $lkGrid->createLink();
        
        $tplGrid = $this->templateObj->getTemplateBranch(
            $lkGrid->getAttribute('layout', 'grid'),
            $this->templateObj->getTemplateRoot()
        );
        
        $pageId = $this->idRender . '_page';
        $pageForm = $this->renderForm(
            $pageId,
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
            $filterId = $this->idRender . '_filter';
            $filterForm = $this->renderForm(
                $filterId,
                $this->templateObj->getTemplateBranch('filter', $tplGrid),
                $lkFilter->getLinkedItems(),
                array(
                    'title' => $lkGrid->getAttribute('title'),
                )
            );
        }
        
        return $this->renderTable(
            $this->templateObj->getTemplateBranch('table', $tplGrid),
            $lkGrid->getColumnsSet()->getLinkedItems(),
            array(
                $lkGrid->getAttribute('title'),
                'url' => $this->getControllerUrl() .
                    "model={$this->modelObj->getModelName()}:{$gridName}&",
                'filter' => $filterForm,
                'page' => $pageForm,
                'id' => $this->idRender
            )
        );
    }
    
    protected function renderTable($templateTable, $columns, $replaces)
    {
        if (!$columns) {
            throw new Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return $this->templateObj->emptyRender();
        }
        
        $templateHeads =
            $this->templateObj->getTemplateBranch('heads', $templateTable);
        $templateCells =
            $this->templateObj->getTemplateBranch('cells', $templateTable);
        $thead = array();
        $tbody = array();
        $renderCount = 0;
        $dx = new Data2Html_Collection();
        foreach ($columns as $k => $v) {
            $dx->set($v);
            if ($dx->getBoolean('virtual')) {
                continue;
            }
            if ($display = $dx->getArray('display')) {
                if (array_search('none', $display) !== false) {
                    continue;
                }
            }
            
            ++$renderCount;
            // head
            $name = $dx->getString('name', $k);
            $label = $dx->getString('title', $name);
            $this->templateObj->concatContents(
                $thead,
                $this->templateObj->renderTemplateItem(
                    'sortable',
                    $templateHeads,
                    array(
                        'name' => $name,
                        'title' => $label
                    )
                )
            );
            // body
            $type = $dx->getString('type');
            $class = '';
            $ngClass = '';
            switch ($type) {
                case 'integer':
                case 'number':
                case 'currency':
                    $class .= 'text-right';
            }
            if ($visual = $dx->getString('visualClass')) {
                if (strpos($visual, ':') !== false) {
                    $ngClass = '{'.str_replace(':', ":item.{$k}", $visual).'}';
                } else {
                    $class .= ' '.$visual;
                }
            }
            $formatItem = '';
            if ($type && $format = $dx->getString('format')) {
                $formatItem = " | {$type}:'{$format}'";
            } elseif ($type === 'currency') {
                $formatItem = " | {$type}";
            }
            $this->templateObj->concatContents(
                $tbody,
                $this->templateObj->renderTemplateItem(
                    'default',
                    $templateCells,
                    array(
                        'ngClass' => $ngClass, 'prefix' => 'item.', // angular1
                        'class' => $class,
                        'name' => $k,
                        'format' => $formatItem
                    )
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

    protected function renderForm(
        $formId,
        $templateBranch,
        $fieldsDs,
        $replaces
    ){
        if (!$fieldsDs) {
            $fieldsDs = array();
        }
        $fieldsDs = array_merge(
            $this->templateObj->getTemplateItems('startItems', $templateBranch),$fieldsDs,
            $this->templateObj->getTemplateItems('endItems', $templateBranch)
        );
        if (count($fieldsDs) === 0) {
            return $this->templateObj->emptyRender();
        }

        $baseUrl = $this->getControllerUrl();
        //$requestUrl = $baseUrl .
          //      "model={$this->modelObj->getModelName()}&form={$gridName}&";
        $templateInputs =
            $this->templateObj->getTemplateBranch('inputs', $templateBranch);
        $templateLayouts =
            $this->templateObj->getTemplateBranch('layouts', $templateBranch);
        $defaultFieldLayout = Data2Html_Value::getItem($formDs, 'fieldLayouts', 'default');
        $body = array();
        $defaults = array();
        $renderCount = 0;
        
        $fieldPrefix = $this->templateObj->getTemplateItem('prefix', $templateBranch, '');
        foreach ($fieldsDs as $k => $v) {            
            $vDx = new Data2Html_Collection($v);
            $input = $vDx->getString('input', 'text');
            $url = $vDx->getString('url', '');
            $validations = $vDx->getArray('validations', array());
            $link = $vDx->getString('link');
            $inputTplName = $input;
            if ($link) {
                $inputTplName = 'ui-select';
                $url = $baseUrl . 'model='.$link.'&';
            }
            $default = Data2Html_Value::getItem($v, 'default');
            $fReplaces = array(
                'id' => $this->createIdRender(),
                'formId' => $formId,
                'title' => $vDx->getString('title'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action'),
                'description' => $vDx->getString('description'),
                'prefix' => $fieldPrefix,
                'name' => $k,
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
