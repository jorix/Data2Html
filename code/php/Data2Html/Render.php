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
        return 'd2h_'.self::$idRenderCount;
    }
    public function getId()
    {
        return $this->id;
    }
    public function render($data)
    {        // templates
        $this->debug = $data->debug;
    echo '<pre>'.$data->toJson($this->tamplatesFilenames).'</pre>';
        $templColl = new Data2Html_Collection($this->templates);
        $gridTemplate = $templColl->getArray('grid');
        if (!$gridTemplate) {
            throw new Exception("The template must have a `grid` key");
        }
        $gridColl = new Data2Html_Collection($gridTemplate);
        $gridHtml = $this->renderTable($gridTemplate, $data);
        $pageDef = array(
            array(
                'input' => 'button',
                'icon' => 'refresh',
                'title' => '$$Refres>"hàdata',
                'description' => null,
                'action' => 'readPage()'
            ),
            array(
                'name' => 'pageSize',
                'default' => 10,
                'type' => 'integer'
            ),
            array(
                'input' => 'button',
                'icon' => 'forward',
                'title' => '$$Ne.>xtàpage',
                'action' => 'nextPage()',
            )
        );
        return str_replace(
            array(
                '$${page}',
                '$${filter}'
            ),
            array(
                $this->renderForm('form_page',
                    'd2h_page.',
                    $gridColl->getArray('form_page'),
                    $pageDef,
                    $data->serviceUrl,
                    $data->getTitle()
                ),
                $this->renderForm('form_filter',
                    'd2h_filter.',
                    $gridColl->getArray('form_filter'),
                    $data->getFilterDefs(),
                    $data->serviceUrl,
                    $data->getTitle()
                )
            ),
            $gridHtml
        );
    }
    protected function renderTable($template, $data)
    { 
        $tableColl = new Data2Html_Collection($template);
        $tableTpl = $tableColl->getString('table', '');
        $tableJsTpl = $tableColl->getString('table.js', '');
        $columnsTemplates = $tableColl->getCollection('columns');
        //
        $colDefs = $data->getColDefs();
        $thead = '';
        $tbody = '';
        $renderCount = 0;
        $i = 0;
        $def = new Data2Html_Collection();
        foreach ($colDefs as $k => $v) {
            ++$i;
            $def->set($v);
            $name = $def->getString('name', $k);
            $label = $def->getString('title', $name);
            $thead .= $this->renderHtmlDefs(
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
        $tableHtml = $this->renderHtmlDefs(
            array(
                'page' => '$${page}', // exclude replace
                'filter' => '$${filter}', // exclude replace 
                'id' => $this->getId(),
                'title' => $data->getTitle(),
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
                    array('$${id}', '$${serviceUrl}'),
                    array($this->getId(), $data->serviceUrl),
                    $tableJsTpl
                ).
                "\n</script>\n";
        } else {
            $tableJs = '';
        }
        return $tableHtml . $tableJs;
    }
    protected function renderForm(
        $templateName,
        $fieldPrefix,
        $template,
        $defs,
        $formServiceUrl,
        $title
    ){
        $formId = $this->createIdRender();
        if (!$template) {
            $html = '';
        } else {
            $templateColl = new Data2Html_Collection($template);
            $formTpl = $templateColl->getString('form');
            $inputsColl = $templateColl->getCollection('inputs');
            // Apply template
            $body = '';
            $renderCount = 0;
            $def = new Data2Html_Collection();
            foreach ($defs as $k => $v) {
                $body .= $this->renderInput(
                    $inputsColl,
                    $formId,
                    $fieldPrefix,
                    $formServiceUrl,
                    $v
                );
                ++$renderCount;
            }
            $html = $this->renderHtmlDefs(
                array(
                    'id' => $formId,
                    'title' => $title,
                    'body' => $body
                ),
                $formTpl
            );
        }
        if ($this->debug) {
            $html = 
                "\n<!-- START renderForm({\"{$templateName}\") formId=\"{$formId}\" -->" .
                // $data->toJson($defs) .
                "\n<!-- ======================================== -->\n" .
                $html .
                "\n<!-- END renderForm({\"{$templateName}\") formId=\"{$formId}\" -->\n";
        }
        return $html;
    }
    
    protected function renderInput(
        $inputsColl,
        $formId,
        $fieldPrefix,
        $formServiceUrl,
        $defs
    ) {
        $def = new Data2Html_Collection($defs);
            
        $input = $def->getString('input');
        
        $name = $def->getString('name', '');
        $default = $def->getString('default', 'undefined');
        $serviceUrl = $def->getString('serviceUrl', '');
        $validations = $def->getArray('validations', array());
        
        $foreignKey = $def->getString('foreignKey');
        if ($foreignKey) {
            $template = $inputsColl->getString('ui-select');
            $baseUrl = explode('?', $formServiceUrl);
            $serviceUrl = $baseUrl[0].'?model='.$foreignKey.'&';
        } elseif ($input) {
            $template = $inputsColl->getString($input);
        } else {
            $template = $inputsColl->getString('text');
        }
        $body = "\n".str_replace(
            array(
                '$${id}',
                '$${form-id}',
                '$${name}',
                '$${default}',
                '$${serviceUrl}',
                '$${validations}'
            ), 
            array(
                $this->createIdRender(),
                $formId,
                $fieldPrefix.$name,
                $default,
                $serviceUrl,
                implode(' ', $validations),
            ),
            $template
        );
        // Other matches
        return $this->renderHtmlDefs($defs, $body);
    }
    protected function renderHtmlDefs($defs, $template)
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
}