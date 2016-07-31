<?php

class Data2Html_Render
{
    public $debug = false;
    protected $pathBase;
    protected $tamplates;
    protected $tamplatesFilenames;
    protected $id;
    private static $idRenderCount = 0;
    public function __construct($templateIni)
    {
        $this->id = $this->createIdRender();
        $this->pathBase = dirname($templateIni).DIRECTORY_SEPARATOR;
        $this->templates = array();
        $this->tamplatesFilenames = array();
        $this->loadTemplates(
            $this->templates,
            $this->tamplatesFilenames,
            $templateIni
        );
        $this->tamplatesColl = new Data2Html_Collection($this->tamplates);
    }
    public function createIdRender() {
        self::$idRenderCount++;
        return 'd2h_' . self::$idRenderCount;
    }
    public function getId()
    {
        return $this->id;
    }
    public function render($data, $gridName = 'default')
    {        // templates
        $this->debug = $data->debug;
        $templColl = new Data2Html_Collection($this->templates);
        $templGridColl = $templColl->getCollection('grid');
        if (!$templGridColl) {
            throw new Exception("The template must have a `grid` key");
        }
        $linkedGrid = $data->getLinkedGrid($gridName);
        $gridDx = new Data2Html_Collection($linkedGrid);
        $gridHtml = $this->renderTable(
            $templGridColl,
            'table', 
            $gridDx->getArray('columns'),
            $data->url,
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
            $data->url,
            $data->getTitle()
        );
        list($filterId, $filterHtml) = $this->renderForm(
            $templGridColl,
            'form_filter',
            $gridDx->getArray('filter'),
            'd2h_filter.',
            $data->url,
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
            //print_r($display);
            //print_r(array_search('hidden', $display));
                if (count($display)) { // TODO
                    $ignore = true;
                }
            }
            if (!$ignore) {
                $name = $def->getString('name', $k);
                $label = $def->getString('title', $name);
                $thead .= $this->renderHtmlDs(
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
        $tableHtml = $this->renderHtmlDs(
            array(
                'page' => '$${page}', // exclude replace
                'filter' => '$${filter}', // exclude replace 
                'id' => $this->getId(),
                'title' => $title,
                'thead' => $thead,
                'tbody' => $tbody,
                'colCount' => $renderCount
            ),
            $tableTpl
        );
        if ($tableJsTpl) {
            $tableJs = 
                "\n<script>\n".
                str_replace(
                    array('$${id}', '$${url}'),
                    array($this->getId(), $url),
                    $tableJsTpl
                ).
                "\n</script>\n";
        } else {
            $tableJs = '';
        }
        return $tableHtml . $tableJs;
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
        if (!$formDs) {
            throw new Exception("\$formDs parameter is empty.");
        }
        $formId = $this->createIdRender();
        if (!$template) {
            $html = '';
        } else {
            $templateColl = new Data2Html_Collection($template);
            $formTpl = $templateColl->getString('form');
            $inputsColl = $templateColl->getCollection('inputs');
            $layoutsColl = $templateColl->getCollection('layouts');
            // Apply template
            $body = '';
            $renderCount = 0;
            $formDx = new Data2Html_Collection($formDs, true);
            $fieldsDs = $formDx->getArray('fields');
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
            $html = $this->renderHtmlDs(
                array(
                    'id' => $formId,
                    'title' => $title,
                    'body' => $body,
                    'defaults' => Data2Html_Value::toJson($defaults)
                ),
                $formTpl
            );
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
        // $default = $def->getString('default', 'undefined');
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
                //'$${default}',
                '$${url}',
                '$${validations}'
            ), 
            array(
                $this->createIdRender(),
                $formId,
                $fieldPrefix . $key,
               // $default,
                $url,
                implode(' ', $validations),
            ),
            $template
        );
        // Other matches
        return $this->renderHtmlDs($defs, $body);
    }
    protected function renderHtmlDs($defs, $template)
    {
        $body = $template;
        $def = new Data2Html_Collection($defs);
        $matches = null;
        preg_match_all('/\=\"\$\$\{([\w.:]+)\}\"/', $body, $matches);
        //htmlentities($str, ENT_SUBSTITUTE, "UTF-8")
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $body = str_replace(
                $matches[0][$i],
                '="' . htmlspecialchars(
                    $def->getString($matches[1][$i], ''),
                    ENT_COMPAT | ENT_SUBSTITUTE,
                    'UTF-8'
                ) . '"',
                $body
            );
        }
        $matches = null;
        preg_match_all('/\$\$\{([\w.:]+)\}/', $body, $matches);
        for($i = 0, $count = count($matches[0]); $i < $count; $i++) {
            $body = str_replace(
                $matches[0][$i],
                $def->getString($matches[1][$i], ''),
                $body
            );
        }
        return $body;
    }
    protected function loadTemplates(
        &$templatesArray,
        &$templatesFnArray,
        $template
    ) {
        if (!file_exists($template)) {
            throw new Exception(
                "Template ini file `{$template}` does not exist."
            );
        }
        $tamplates = parse_ini_file($template, true);
        $tmpls = new Data2Html_Collection($tamplates);
        $this->loadTemplatesElem(
            $templatesArray,
            $templatesFnArray,
            dirname($template) . DIRECTORY_SEPARATOR,
            $tmpls,
            'elements'
        );
    }    
    protected function loadTemplatesElem(
        &$templatesArray,
        &$templatesFnArray,
        $folderBase,
        $tmpls,
        $key
    ) {
        $elements = $tmpls->getArray($key, array());
        $ds = DIRECTORY_SEPARATOR;
        foreach($elements as $k => $v) {
            $path = Data2Html_Collection::create(pathinfo($v));
            $dirname = $path->getString('dirname','');
            if ($dirname) {
                $dirname = $folderBase . $dirname . $ds;
            } else {
                $dirname = $folderBase;
            }
            $basename = $path->getString('basename','');
            switch ($path->getString('extension')) {
                case 'ini':
                    $subElems = array();
                    $subFnElems = array();
                    $this->loadTemplates(
                        $subElems, $subFnElems,
                        $dirname . $basename
                    );
                    $templatesArray[$k] = $subElems;
                    $templatesFnArray[$k] = $subFnElems;
                    break;
                case 'html':
                    if (!file_exists($dirname . $basename)) {
                        throw new Exception(
                            "Parsing on folder `{$folderBase}` on element \"{$k}\"=>\"{$v}\" does not exist `{$basename}` on forder `{$dirname}`."
                        );
                    }
                    $templatesArray[$k] = file_get_contents($dirname . $basename);
                    $templatesFnArray[$k] = $dirname . $basename;
                    $fileJs = $dirname . $path->getString('filename', '') . '.js';
                    if (file_exists($fileJs)) {
                        $templatesArray[$k . '.js'] = file_get_contents($fileJs);
                        $templatesFnArray[$k . '.js'] = $fileJs;
                    }
                    break;
                default:
                    $subElems = array();
                    $subFnElems = array();
                    $this->loadTemplatesElem(
                        $subElems, $subFnElems,
                        $folderBase, $tmpls, $k
                    );
                    $templatesArray[$k] = $subElems;
                    $templatesFnArray[$k] = $subFnElems;
                    break;
            }
        }
    }
    public function toJson($obj)
    {
        return Data2Html_Value::toJson($obj, $this->debug);
    }
}