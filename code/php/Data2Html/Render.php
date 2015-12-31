<?php

class Data2Html_Render
{
    protected $tableTpl;
    protected $thSortable;
    public function __construct($templatePath)
    {
        if (substr($templatePath, -1, 1) !== '/') {
            $templatePath .= '/';
        }
        $this->tableTpl = file_get_contents($templatePath.'table_div.html');
        $this->thSortable = file_get_contents($templatePath.'th_sortable.html');

    }
    
    public function angularTable($data)
    {
        $defs = $data->getDefs();
        $colArray = $defs['colsDefs'];
        $thead = '';
        $tbody = '';
        $colCount = 0;
        $i = 0;
        $_v = new Data2Html_Values();
        foreach ($colArray as $k => $v) {
            ++$i;
            $_v->set($v);
            $label = $_v->getString('label', $k);
            $thead .= str_replace(
                array('$${name}', '$${label}'),
                array($k, $_v->getString('label', $k)),
                $this->thSortable
            );
            $type = $_v->getString('type');
            ++$colCount;
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

        return str_replace(
            array('$${id}', '$${title}', '$${thead}', '$${tbody}', '$${colCount}'),
            array($data->getId(), $data->getTitle(), $thead, $tbody, $colCount),
            $this->tableTpl
        );
    }
    public function renderHtmlTable($tpl)
    {
        $colArray = $this->colsDefs;
        $tBody = '';
        $thead = '';
        $tbody = '';
        $i = 0;
        foreach ($colArray as $k => $v) {
            ++$i;
            $tbody .= "<td>{{$k}}</td>\n";
            if (isset($v['label'])) {
                $thead .= "<th>{$v['label']}</th>\n";
            } else {
                $thead .= "<th>{$k}</th>\n";
            }
        }

        return str_replace(
            array('$${_id}', '$${_title}', '$${_thead}', '$${_tbody}'),
            array($this->id, $this->title,
                        '<tr>'.$thead.'</tr>',
                        '<tr>'.$tbody.'</tr>', ),
            $tpl
        );
    }
}