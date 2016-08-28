<?php

class Data2Html_Render
{
    public $debug = false;
    protected $templateObj;
    protected $id;
    private static $idRenderCount = 0;
    public function __construct($templateName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->id = $this->createIdRender();
        $this->templateObj = new Data2Html_Render_Template($templateName);
    }
    
    public function createIdRender() {
        self::$idRenderCount++;
        return 'd2h_' . self::$idRenderCount;
    }
    
    public function getId()
    {
        return $this->id;
    }

    protected function concatContents(&$final, $item) {
        foreach($item as $k => $v) {
            if (array_key_exists($k, $final)) {
                $final[$k] .= $item[$k];
            } else {
                $final[$k] = $item[$k];
            }
        }
    }
    
    public function renderGrid($model, $gridName)
    {        
        $linkedGrid = $model->getLinkedGrid($gridName);
        $tplGrid = $this->templateObj->getTemplateBranch('grid');
        
        $gridDx = new Data2Html_Collection($linkedGrid);
        $gridHtml = $this->renderTable(
            $this->templateObj->getTemplateBranch('table', $tplGrid),
            $gridDx->getArray('columns'),
            $model->requestUrl,
            $model->getTitle()
        );
        print_r($gridHtml);
        $pageDef = array(
            'layout' => 'inline',
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
        list($pageId, $pageHtml) = $this->renderFormByDs('form_page',
            $templGridColl->getArray('form_page'),
            $pageDef,
            'd2h_page.',
            $model->requestUrl,
            $model->getTitle()
        );
        list($filterId, $filterHtml) = $this->renderForm(
            $templGridColl,
            'form_filter',
            $gridDx->getArray('filter'),
            'd2h_filter.',
            $model->requestUrl,
            $model->getTitle()
        );
        return str_replace(
            array('$${pageId}', '$${page}', '$${filterId}', '$${filter}'),
            array($pageId, $pageHtml, $filterId, $filterHtml),
            $gridHtml
        );
    }
        
    public function render($model, $request)
    {
        if (isset($request['grid'])) {
            return $this->renderGrid($model, $request['grid']);
        } else {
            throw new Exception("no request object.");
        }
    }
    
    protected function renderTable(
        $templateTable,
        $colDs,
        $url,
        $title
    ) {
        if (!$colDs) {
            throw new Exception("`\$colDs` parameter is empty.");
        }
        $thead = array();
        $tbody = array();
        $renderCount = 0;
        $def = new Data2Html_Collection();
        $templateHeads =
            $this->templateObj->getTemplateBranch('heads', $templateTable);
        $templateCells =
            $this->templateObj->getTemplateBranch('cells', $templateTable);
        foreach ($colDs as $k => $v) {
            $def->set($v);
            $ignore = false;
            if ($display = $def->getArray('display')) {
                if (count($display)) { // TODO
                    $ignore = true;
                }
            }
            if (!$ignore) {
                ++$renderCount;
                // head
                $name = $def->getString('name', $k);
                $label = $def->getString('title', $name);
                $this->concatContents(
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
                $this->concatContents(
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
        $result = $this->templateObj->renderTemplate(
            $templateTable,
            array(
                'page' => '$${page}', // exclude replace
                'filter' => '$${filter}', // exclude replace 
                'id' => $this->getId(),
                'url' => $url,
                'title' => $title,
                'thead' => $thead,
                'tbody' => $tbody,
                'colCount' => $renderCount
            )
        );
        return $result;
    }

    protected function renderForm(
        $templateColl,
        $formName,
        $defs,
        $fieldPrefix,
        $formUrl,
        $title
    ){  
        list($formId, $html) = $this->renderFormByDs(
            $formName,
            $templateColl->getArray($formName),
            $defs,
            $fieldPrefix,
            $formUrl,
            $title
        );
        return array($formId, $html);
    }
    protected function renderFormByDs(
        $templateName,
        $template,
        $formDs,
        $fieldPrefix,
        $formUrl,
        $title
    ){
        $formId = $this->createIdRender();
        if (!$formDs) {
            $formDs = array();
            // throw new Exception("\$formDs parameter is empty.");
        }
        if (!$template) {
            throw new Exception("Template \"{$templateName}\" is empty.");
        } else {
            $templateColl = new Data2Html_Collection($template);
            $formTpl = $templateColl->getString('form');
            $inputsColl = $templateColl->getCollection('inputs');
            $layoutsColl = $templateColl->getCollection('layouts');
            // Apply template
            $body = '';
            $renderCount = 0;
            $formDx = new Data2Html_Collection($formDs, true);
            $fieldsDs = $formDx->getArray('fields', array());
            $defLayoutTpl = '';
            if ($layoutsColl) {
                $defLayoutTpl = $layoutsColl->getString(
                    $formDx->getString('layout', '')
                );
            }
            $defaults = array();
            foreach ($fieldsDs as $k => $v) {
                $body .= $this->renderInput(
                    $inputsColl,
                    $defLayoutTpl,
                    $formId,
                    $fieldPrefix,
                    $formUrl,
                    $k,
                    $v
                );
                $default = Data2Html_Array::get($v, 'default');
                if ($default !== null) {
                    $defaults[$k] = $default;
                }
                ++$renderCount;
            }
            $html = $this->renderHtml(
                array(
                    'id' => $formId,
                    'title' => $title,
                    'body' => $body,
                    'defaults' => Data2Html_Value::toJson($defaults)
                ),
                $formTpl
            );
        }
        if ($html === '') {
            $html = "<div id=\"{$formId}\"></div>";
        }
        if ($this->debug) {
            $html = 
                "\n<!-- START renderForm({\"{$templateName}\") formId=\"{$formId}\" -->" .
                "\n<!-- ======================================== -->\n" .
                $html .
                "\n<!-- END renderForm({\"{$templateName}\") formId=\"{$formId}\" -->\n";
        }
        return array($formId, $html);
    }
    
    protected function renderInput(
        $inputsColl,
        $layoutTpl,
        $formId,
        $fieldPrefix,
        $formUrl,
        $key,
        $defs
    ) {
        $def = new Data2Html_Collection($defs);
        $input = $def->getString('input', 'text');
        $url = $def->getString('url', '');
        $validations = $def->getArray('validations', array());
        $link = $def->getString('link');
        if ($link) {
            $template = $inputsColl->getString('ui-select');
            $baseUrl = explode('?', $formUrl);
            $url = $baseUrl[0].'?model='.$link.'&';
        } elseif ($input) {
            $template = $inputsColl->getString($input);
        }
        if ($layoutTpl) {
            $template = str_replace('$${html}', $template, $layoutTpl);
        }
        $body = "\n".str_replace(
            array(
                '$${id}',
                '$${form-id}',
                '$${name}',
                '$${url}',
                '$${validations}'
            ), 
            array(
                $this->createIdRender(),
                $formId,
                $fieldPrefix . $key,
                $url,
                implode(' ', $validations),
            ),
            $template
        );
        // Other matches
        return $this->renderHtml($defs, $body);
    }
}
