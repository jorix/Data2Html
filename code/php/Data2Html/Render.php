<?php

class Data2Html_Render
{
    public $debug = false;
    protected $templateObjs;
    protected $id;
    private static $idRenderCount = 0;
    public function __construct($templateName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->id = $this->createIdRender();
        $this->templateObjs = new Data2Html_Render_Templates($templateName);
    }
    
    public function createIdRender() {
        self::$idRenderCount++;
        return 'd2h_' . self::$idRenderCount;
    }
    
    public function getId()
    {
        return $this->id;
    }
    
    public function renderGrid($data, $gridName)
    {        
        return;
        // templates
        
        $linkedGrid = $data->getLinkedGrid($gridName);
        $gridDx = new Data2Html_Collection($linkedGrid);
        $gridHtml = $this->renderTable(
            $templGridColl,
            'table', 
            $gridDx->getArray('columns'),
            $data->requestUrl,
            $data->getTitle()
        );
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
            $data->requestUrl,
            $data->getTitle()
        );
        list($filterId, $filterHtml) = $this->renderForm(
            $templGridColl,
            'form_filter',
            $gridDx->getArray('filter'),
            'd2h_filter.',
            $data->requestUrl,
            $data->getTitle()
        );
        return str_replace(
            array('$${pageId}', '$${page}', '$${filterId}', '$${filter}'),
            array($pageId, $pageHtml, $filterId, $filterHtml),
            $gridHtml
        );
    }
    protected function renderTable(
        $templateColl,
        $elemName,
        $colDs,
        $url,
        $title
    ) {
        if (!$colDs) {
            throw new Exception("\$colDs parameter is empty.");
        }
        $tableTpl = $templateColl->getString($elemName, '');
        $tableJsTpl = $templateColl->getString($elemName . '.js', '');
        $columnsTemplates = $templateColl->getCollection('columns');
        //
        $thead = '';
        $tbody = '';
        $renderCount = 0;
        $def = new Data2Html_Collection();
        foreach ($colDs as $k => $v) {
            $def->set($v);
            $ignore = false;
            if ($display = $def->getArray('display')) {
                if (count($display)) { // TODO
                    $ignore = true;
                }
            }
            if (!$ignore) {
                $name = $def->getString('name', $k);
                $label = $def->getString('title', $name);
                $thead .= $this->renderHtml(
                    array(
                        'name' => $name,
                        'title' => $label
                    ),
                    $columnsTemplates->getString('sortable', '')
                );
                $type = $def->getString('type');
                ++$renderCount;
                $tbody .= '<td';
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
                if ($ngClass) {
                    $tbody .= " ng-class=\"{$ngClass}\"";
                }
                if ($class) {
                    $tbody .= " class=\"{$class}\"";
                }
                $tbody .= '>';
                if ($type && $format = $def->getString('format')) {
                    $tbody .= "{{item.{$k} | {$type}:'{$format}'}}";
                } elseif ($type === 'currency') {
                    $tbody .= "{{item.{$k} | {$type}}}";
                } else {
                    $tbody .= "{{item.{$k}}}";
                }
                $tbody .= "</td>\n";
            }
        }
        $result = $this->templateObjs->renderTemplate(
            array(
                'page' => '$${page}', // exclude replace
                'filter' => '$${filter}', // exclude replace 
                'id' => $this->getId(),
                'url' => $url,
                'title' => $title,
                'thead' => $thead,
                'tbody' => $tbody,
                'colCount' => $renderCount
            ),
            array('grid','table')
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
