<?php

class Data2Html_Render
{
    protected $pathBase;
    protected $tamplates;
    public function __construct($templateIni)
    {
        $this->pathBase = dirname($templateIni).DIRECTORY_SEPARATOR;
        $tamplates = parse_ini_file($templateIni, true);
        $this->tamplates = new Data2Html_Values($tamplates);
    }
    
    public function table($data)
    {
        // templates
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
        $defs = $data->getColDefs();
        $thead = '';
        $tbody = '';
        $renderCount = 0;
        $i = 0;
        $_v = new Data2Html_Values();
        foreach ($defs as $k => $v) {
            ++$i;
            $_v->set($v);
            $name = $_v->getString('name', $k);
            $label = $_v->getString('label', $name);
            $thead .= str_replace(
                array('$${name}', '$${label}'),
                array($name, $label),
                $thSortableTpl
            );
            $type = $_v->getString('type');
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
            if ($visual = $_v->getString('visualClass')) {
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
            if ($type && $format = $_v->getString('format')) {
                $tbody .= "{{item.{$k} | {$type}:'{$format}'}}";
            } elseif ($type === 'currency') {
                $tbody .= "{{item.{$k} | {$type}}}";
            } else {
                $tbody .= "{{item.{$k}}}";
            }
            $tbody .= "</td>\n";
        }
        $tableHtml = str_replace(
            array('$${id}', '$${title}', '$${thead}', '$${tbody}', '$${colCount}'),
            array($data->getId(), $data->getTitle(), $thead, $tbody, $renderCount),
            $tableTpl
        );
        $tableJs = 
            "\n<script>\n".
            str_replace(
                array('$${id}'),
                array($data->getId()),
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
            $this->pathBase.$t->getString('input_select')
        );
        
        $defs = $data->getFilterDefs();
        $body = '';
        $renderCount = 0;
        $i = 0;
        $_v = new Data2Html_Values();
        foreach ($defs as $k => $v) {
            ++$i;
            $_v->set($v);            
            $name = $_v->getString('name');
            $label = $_v->getString('label', $name);
            $list = $_v->getArray('list');
            if ($list) {
                $body .= str_replace(
                    array('$${name}', '$${label}'),
                    array('d2h_filter.'.$name, $label),
                    $inputSelectTpl
                );
            } else {
                $body .= str_replace(
                    array('$${name}', '$${label}'),
                    array('d2h_filter.'.$name, $label),
                    $inputTextTpl
                );
            }
            ++$renderCount;
        }
        $filterId = $data->getId() . '_filter';
        return str_replace(
            array('$${id}', '$${title}', '$${body}', '$${colCount}'),
            array($filterId, $data->getTitle(), $body, $renderCount),
            $formTpl
        );
    }
}