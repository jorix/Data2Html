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
        $this->idRender = $this->createIdRender();
        $this->modelObj = $modelObj;
        $this->templateObj = new Data2Html_Render_Template($templateName);
    }
    
    protected function createIdRender() {
        self::$idRenderCount++;
        return 'd2h_' . self::$idRenderCount;
    }

    public function render($payerNames)
    {
        if (isset($payerNames['form'])) {
            
        } elseif (isset($payerNames['grid'])) {
            $gridName = $payerNames['grid'];
            $requestUrl = 
                $this->modelObj->requestUrl .
                "model={$payerNames['model']}:{$gridName}&";
            return $this->renderGrid($gridName, $requestUrl);
            
        } else {
            throw new Exception("no request object.");
        }
    }

    protected function renderGrid($gridName, $requestUrl)
    {        
        $linkedGrid = $this->modelObj->getLinkedGrid($gridName);
        $tplGrid = $this->templateObj->getTemplateBranch('grid');
        
        $gridDx = new Data2Html_Collection($linkedGrid);
        $pageDef = array(
            'layout' => 'none',
            'fields' => array(
                array(
                    'input' => 'button',
                    'icon' => 'refresh',
                    'title' => '$$Refres>"hàdata',
                    'description' => null,
                    'action' => 'readPage()'
                ),
                'pageSize' => array(
                    'default' => 10,
                    'type' => 'integer'
                ),
                array(
                    'input' => 'button',
                    'icon' => 'forward',
                    'title' => '$$Ne.>xtàpage',
                    'action' => 'nextPage()',
                )
            )
        );
        $pageId = $this->idRender . '_page';
        $pageForm = $this->renderForm(
            $pageId,
            $this->templateObj->getTemplateBranch('page', $tplGrid),
            $pageDef,
            'd2h_page.',
            $requestUrl,
            $this->modelObj->getTitle()
        );
        $filterId = $this->idRender . '_filter';
        $filterForm = $this->renderForm(
            $filterId,
            $this->templateObj->getTemplateBranch('filter', $tplGrid),
            $gridDx->getArray('filter'),
            'd2h_filter.',
            $requestUrl,
            $this->modelObj->getTitle()
        );
        $gridTable = $this->renderTable(
            $this->templateObj->getTemplateBranch('table', $tplGrid),
            $gridDx->getArray('columns'),
            $gridDx->getArray('columnNames'),
            array(
                'title' => $this->modelObj->getTitle(),
                'url' => $requestUrl,
                'filter' => $filterForm, 
                'filterId' => $filterId,
                'page' => $pageForm,
                'pageId' => $pageId,
                'id' => $this->idRender
            )
        );
        return $gridTable;
    }
    
    protected function renderTable($templateTable, $colDs, $colNames, $replaces)
    {
        if (!$colDs) {
            throw new Exception("`\$colDs` parameter is empty.");
        }
        $templateHeads =
            $this->templateObj->getTemplateBranch('heads', $templateTable);
        $templateCells =
            $this->templateObj->getTemplateBranch('cells', $templateTable);
        $thead = array();
        $tbody = array();
        $renderCount = 0;
        $def = new Data2Html_Collection();
        foreach ($colNames as $k) {
            $v = $colDs[$k];
            $def->set($v);
            $ignore = false;
            if ($display = $def->toArray('display')) {
                if (array_search('hidden', $display) !== false ||
                    array_search('none', $display) !== false) {
                    $ignore = true;
                }
            }
            if (!$ignore) {
                ++$renderCount;
                // head
                $name = $def->getString('name', $k);
                $label = $def->getString('title', $name);
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
                $type = $def->getString('type');
                $class = '';
                $ngClass = '';
                switch ($type) {
                    case 'integer':
                    case 'number':
                    case 'currency':
                        $class .= 'text-right';
                }
                if ($visual = $def->getString('visualClass')) {
                    if (strpos($visual, ':') !== false) {
                        $ngClass = '{'.str_replace(':', ":item.{$k}", $visual).'}';
                    } else {
                        $class .= ' '.$visual;
                    }
                }
                if ($type && $format = $def->getString('format')) {
                    $value = "{{item.{$k} | {$type}:'{$format}'}}";
                } elseif ($type === 'currency') {
                    $value = "{{item.{$k} | {$type}}}";
                } else {
                    $value = "{{item.{$k}}}";
                }
                $this->templateObj->concatContents(
                    $tbody,
                    $this->templateObj->renderTemplateItem(
                        'default',
                        $templateCells,
                        array(
                            'class' => $class,
                            'ngClass' => $ngClass,
                            'value' => $value
                        )
                    )
                );
            }
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
        $formDs,
        $fieldPrefix,
        $formUrl,
        $title
    ){
        if (!$formDs) {
            return $this->templateObj->emptyRender();
        }
        $templateInputs =
            $this->templateObj->getTemplateBranch('inputs', $templateBranch);
        $templateLayouts =
            $this->templateObj->getTemplateBranch('layouts', $templateBranch);
        $fieldsDs = Data2Html_Value::getItem($formDs, 'fields', array());
        $defaultFieldLayout = Data2Html_Value::getItem($formDs, 'layout', 'default');
        $body = array();
        $defaults = array();
        $renderCount = 0;
        foreach ($fieldsDs as $k => $v) {            
            $vDx = new Data2Html_Collection($v);
            $input = $vDx->getString('input', 'text');
            $url = $vDx->getString('url', '');
            $validations = $vDx->getArray('validations', array());
            $link = $vDx->getString('link');
            $inputTplName = $input;
            if ($link) {
                $inputTplName = 'ui-select';
                $baseUrl = explode('?', $formUrl);
                $url = $baseUrl[0].'?model='.$link.'&';
            }
            $replaces = array(
                'id' => $this->createIdRender(),
                'formId' => $formId,
                'title' => $vDx->getString('title'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action'),
                'description' => $vDx->getString('description'),
                'name' => $fieldPrefix . $k,
                'url' => $url,
                'validations' => implode(' ', $validations)
            );
            $replaces['html'] = $this->templateObj->renderTemplateItem(
                $inputTplName, $templateInputs, $replaces
            );
            $this->templateObj->concatContents(
                $body,
                $this->templateObj->renderTemplateItem(
                    $vDx->getString('layout', $defaultFieldLayout),
                    $templateLayouts, 
                    $replaces
                )
            );
            $default = Data2Html_Value::getItem($v, 'default');
            if ($default !== null) {
                $defaults[$k] = $default;
            }
            ++$renderCount;
        }
        $form = $this->templateObj->renderTemplate(
            $templateBranch,
            array(
                'id' => $formId,
                'title' => $title,
                'body' => $body,
                'defaults' => $defaults
            )
        );
        return $form;
    }
}
