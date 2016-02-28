<?php

class Data2Html_Render
{
    protected $pathBase;
    protected $tamplates;
    public function __construct($templateIni)
    {
        $this->pathBase = dirname($templateIni).DIRECTORY_SEPARATOR;
        $tamplates = parse_ini_file($templateIni, true);
        $this->tamplates = new Data2Html_Collection($tamplates);
    }
    public function render($data)
    {        // templates
        $t = $this->tamplates->getArrayValues('table');
        if (!$t) {
            return '';
        }
        $tableTpl = file_get_contents(
            $this->pathBase.$t->getString('table')
        );
        $tableJsTpl = file_get_contents(
            $this->pathBase.$t->getString('table_js')
        );
        $thSortableTpl = file_get_contents(
            $this->pathBase.$t->getString('col_sortable')
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
                $thSortableTpl
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
                '$${filter}',
                '$${thead}',
                '$${tbody}',
                '$${colCount}'
            ),
            array(
                $data->getId(),
                $data->getTitle(),
                $this->filterForm($data),
                $thead,
                $tbody,
                $renderCount
            ),
            $tableTpl
        );
        $tableJs = 
            "\n<script>\n".
            str_replace(
                array('$${id}', '$${serviceUrl}'),
                array($data->getId(), $data->serviceUrl),
                $tableJsTpl
            ).
            "\n</script>\n";
        return $tableHtml . $tableJs;
    }
    public function filterForm($data)
    {
        $t = $this->tamplates->getArrayValues('filter');
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
}