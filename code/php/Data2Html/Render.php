<?php

class Data2Html_Render
{
    protected $pathBase;
    protected $tamplates;
    protected $tamplatesFilenames;
    public function __construct($templateIni)
    {
        $this->pathBase = dirname($templateIni).DIRECTORY_SEPARATOR;
        $this->templates = array();
        $this->tamplatesFilenames = array();
        $this->loadTemplates(
            $this->templates,
            $this->tamplatesFilenames,
            $templateIni
        );
        print_r($this->tamplatesFilenames);
    }
    public function render($data)
    {        // templates
        $templColl = new Data2Html_Collection($this->templates);
        $gridTmpl = $templColl->getCollection('grid');
        if (!$gridTmpl) {
            throw new Exception("The template must have a `grid` key");
        }
        $gridHtml = '';
        if ($gridTmpl->getArray('table')) {
            $gridHtml = $this->table($gridTmpl, $data);
        }
        return str_replace(
            array(
                '$${page}',
                '$${filter}'
            ),
            array(
                $this->pageForm($data),
                $this->filterForm($data)
            ),
            $gridHtml
        );
    }
    public function table($templateArray, $data)
    { 
        $tableTpl = Data2Html_Collection::get_string(
            $templateArray, array('table', 'html'), ''
        );
        $tableJsTpl = Data2Html_Collection::get_string(
            $templateArray, array('table.js', 'js')
        );
        $columnsTemplates = Data2Html_Collection::get_collection(
            $templateArray, 'columns', array()
        );
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
            $label = $def->getString('label', $name);
            $thead .= str_replace(
                array('$${name}', '$${label}'),
                array($name, $label),
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
        $tableHtml = str_replace(
            array(
                '$${id}',
                '$${title}',
                '$${thead}',
                '$${tbody}',
                '$${colCount}'
            ),
            array(
                $data->getId(),
                $data->getTitle(),
                $thead,
                $tbody,
                $renderCount
            ),
            $tableTpl
        );
        if ($tableJsTpl) {
            $tableJs = 
                "\n<script>\n".
                str_replace(
                    array('$${id}', '$${serviceUrl}'),
                    array($data->getId(), $data->serviceUrl),
                    $tableJsTpl
                ).
                "\n</script>\n";
        } else {
            $tableJs = '';
        }
        return $tableHtml . $tableJs;
    }
    public function pageForm($data)
    {
        $t = $this->tamplates->getCollection('page');
        $formTpl = file_get_contents(
            $this->pathBase.$t->getString('form')
        );
        return str_replace(
            array('$${id}'),
            array($data->createId()),
            $formTpl
        );
    }
    public function filterForm($data)
    {
        $t = $this->tamplates->getCollection('filter');
        $formTpl = file_get_contents(
            $this->pathBase.$t->getString('form')
        );
        $inputTextTpl = file_get_contents(
            $this->pathBase.$t->getString('input_text')
        );
        $inputSelectTpl = file_get_contents(
            $this->pathBase.$t->getString('input_ui-select')
        );
        
        $defs = $data->getFilterDefs();
        $body = '';
        $renderCount = 0;
        $i = 0;
        $def = new Data2Html_Collection();
        $filterId = $data->createId();
        foreach ($defs as $k => $v) {
            ++$i;
            $def->set($v);            
            $name = $def->getString('name');
            $label = $def->getString('label', $name);
            $placeholder = $def->getString('placeholder', $label);
            $default = $def->getString('default', 'undefined');
            $serviceUrl = $def->getString('serviceUrl', '');
            $foreignKey = $def->getString('foreignKey');
            $validations = $def->getArray('validations', array());
            if ($foreignKey) {
                $template = $inputSelectTpl;
                $aaa = explode('?', $data->serviceUrl);
                $serviceUrl = $aaa[0].'?model='.$foreignKey.'&';
            } else {
                $template = $inputTextTpl;
            }
            $body .= "\n".str_replace(
                array(
                    '$${id}',
                    '$${form-id}',
                    '$${name}', '$${label}', '$${placeholder}',
                    '$${default}',
                    '$${serviceUrl}',
                    '$${validations}'
                ), 
                array(
                    $data->createId(),
                    $filterId,
                    'd2h_filter.'.$name, $label, $placeholder,
                    $default,
                    $serviceUrl,
                    implode(' ', $validations),
                ),
                $template
            );
            ++$renderCount;
        }
        return str_replace(
            array('$${id}', '$${title}', '$${body}'),
            array($filterId, $data->getTitle(), $body),
            $formTpl
        );
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