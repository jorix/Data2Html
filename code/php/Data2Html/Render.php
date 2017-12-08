<?php

class Data2Html_Render
{
    public $debug = false;
    private $templateObj;
    private $idRender;
    private $typeToInputTemplates = array(
        '[default]' =>    array('base', 'text-input'),
        'boolean' =>    array('checkbox', 'checkbox'),
        'date' =>       array('base', 'datetimepicker')
    );
    private $visualWords = array(
        'display', 'format', 'size', 'title', 'type', 'validations', 'default'
    );
    private static $idRenderCount = 0;
    public function __construct($templateName)
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Render";
        
        $this->idRender = $this->createIdRender();
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
    
    private function createIdRender() {
        self::$idRenderCount++;
        return 'd2h_' . self::$idRenderCount;
    }

    protected function getControllerUrl()
    {
        return Data2Html_Config::get('controllerUrl') . '?';
    }

    public function renderGrid($model, $gridName)
    {        
        
        $this->culprit = "Render for grid: \"{$model->getModelName()}:{$gridName}\"";
        $lkGrid = $model->getGrid($gridName);
        $lkGrid->createLink();
        
        $tplGrid = $this->templateObj->getTemplateBranch(
            $lkGrid->getAttribute('layout', 'grid'),
            $this->templateObj->getTemplateRoot()
        );
        $gridId = $this->idRender . '_grid_' . $gridName;
        $pageForm = $this->renderFormSet(
            $gridId . '_page',
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
            $filterForm = $this->renderFormSet(
                $gridId . '_filter',
                $this->templateObj->getTemplateBranch('filter', $tplGrid),
                $lkFilter->getLinkedItems(),
                array(
                    'title' => $lkGrid->getAttribute('title'),
                )
            );
        }
        $klColumns = $lkGrid->getColumnsSet();
        
        $result = $this->renderTable(
            $this->templateObj->getTemplateBranch('table', $tplGrid),
            $klColumns->getLinkedItems(),
            array(
                $lkGrid->getAttribute('title'),
                'id' => $gridId,
                'url' => $this->getControllerUrl() .
                    "model={$model->getModelName()}:{$gridName}&",
                'sortBy' => $lkGrid->getAttribute('sort'),
                'filter' => $filterForm,
                'page' => $pageForm
            )
        );
        $result['id'] = $gridId;
        return $result;
    }
    
    public function renderForm($model, $formName)
    {
        $this->culprit =
            "Render for form: \"{$model->getModelName()}:{$formName}\"";
        $lkForm = $model->getForm($formName);
        $lkForm->createLink();
        $items = $lkForm->getLinkedItems();
        
        $tplForm = $this->templateObj->getTemplateBranch(
            $lkForm->getAttribute('layout', 'form'),
            $this->templateObj->getTemplateRoot()
        );
        $formId = $this->idRender . '_form_' . $formName;
        
        list($body) = $this->renderSet(
            array_merge(
                $this->parseIncludeItems('startItems', $tplForm),
                $items,
                $this->parseIncludeItems('endItems', $tplForm)
            ),
            $tplForm,
            array(
                'formId' => $formId
            )
        );
        $form = $this->templateObj->renderTemplate(
            $templateBranch,
            array(
                'id' => $formId,
                'title' => $lkForm->getAttribute('title'),
                'url' => $this->getControllerUrl() .
                     "model={$model->getModelName()}&form={$formName}&",
                'body' => $body,
                'visual' => $this->getVisualItemsJson($items)
            )
        );
        $form['id'] = $formId;
        return $form;
    }
    
    protected function renderTable($templateTable, $columns, $replaces)
    {
        if (!$columns) {
            throw new Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return $this->templateObj->emptyRender();
        }
        list($thead, $renderCount) = $this->renderSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateTable, 'head-item'),
                $columns,
                $this->parseIncludeItems('endItems', $templateTable, 'head-item')
            ),
            $this->templateObj->getTemplateBranch('heads', $templateTable)
        );
        list($tbody) = $this->renderSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateTable),
                $columns,
                $this->parseIncludeItems('endItems', $templateTable)
            ),
            $this->templateObj->getTemplateBranch('cells', $templateTable)
        );
        
        // End
        $replaces = array_merge($replaces, array(
            'thead' => $thead,
            'tbody' => $tbody,
            'colCount' => $renderCount,
            'visual' => $this->getVisualItemsJson($columns)
        ));
        return $this->templateObj->renderTemplate($templateTable, $replaces);
        $previusLevel = $level;
    }

    protected function renderSet($items, $template, $iReplaces = array())
    {
        $assignTemplate = Data2Html_Value::getItem(
            $template[1], 
            "assign-template", 
            function() { return array('base', 'base', array()); }
        );
        $tLayouts =
            $this->templateObj->getTemplateBranch('layouts', $template);
        $tContents =
            $this->templateObj->getTemplateBranch('contents', $template);
        
        $renderSetLevel = function($currentLevel) 
        use(&$renderSetLevel, &$assignTemplate, &$items, $tContents, $tLayouts, $iReplaces)
        {
            $body = $this->templateObj->getEmptyBody();
            $vDx = new Data2Html_Collection();

            // Declare end previous item function
            $endPreviousItem = function($itemBody, $levelBody, $layoutTemplName, $replaces) 
            use(&$body, $tLayouts) {
                if (!$itemBody) {
                    return;
                }
                if ($levelBody) {
                    $this->templateObj->concatContents($itemBody, $levelBody);
                }
                $replaces['html'] = $itemBody;
                $this->templateObj->concatContents(
                    $body,
                    $this->templateObj->renderTemplateItem(
                        $layoutTemplName,
                        $tLayouts,
                        $replaces
                    )
                );
            };
            
            // Current level
            $itemBody = null;
            $levelBody = null;
            $layoutTemplName = null;
            $replaces = null;
            $renderCount = 0;
            $v = current($items); 
            while ($v !== false) {
                $level =  Data2Html_Value::getItem($v, 'level', 0);
                if ($level < $currentLevel) {
                    break;
                }
                // Down level / Finalize previous item
                if ($level > $currentLevel) {
                    list($levelBody) = $renderSetLevel($level);
                    $v = current($items);
                    $level =  Data2Html_Value::getItem($v, 'level', 0);
                }
                if ($level === $currentLevel) {
                    $endPreviousItem(
                        $itemBody,
                        $levelBody,
                        $layoutTemplName,
                        $replaces
                    );
                }
                
                // Start current item
                $vDx->set($v);
                $itemBody = null;
                $levelBody = null;
                $layoutTemplName = null;
                $replaces = null;
                if ($vDx->getBoolean('virtual')) {
                    $v = next($items);
                    continue;
                }
                $display = $vDx->getString('display', 'html');
                if ($display === 'none') {
                    $v = next($items);
                    continue;
                }
                list(
                    $layoutTemplName,
                    $contentTemplName,
                    $replaces
                ) = $assignTemplate($this, $v);
                $replaces += $iReplaces + array(
                    'id' => $this->createIdRender(),
                    'name' => key($items),
                    'title' => $vDx->getString('title') . '#' . $level,
                    'description' => $vDx->getString('description'),
                    'format' => $vDx->getString('format'),
                    'type' => $vDx->getString('type'),
                    'icon' => $vDx->getString('icon'),
                    'action' => $vDx->getString('action'),
                    'validations' => implode(' ', 
                        $vDx->getArray('validations', array())
                    )
                );
                $itemBody = $this->templateObj->renderTemplateItem(
                    $contentTemplName,
                    $tContents,
                    $replaces
                );
                ++$renderCount;

                $v = next($items);
            }
            // Finalize previous item
            $endPreviousItem($itemBody, $levelBody, $layoutTemplName, $replaces);
            return array($body, $renderCount);
        };
        reset($items);
        return $renderSetLevel(0);
    }

    protected function parseIncludeItems($setName, $templateBranch, $alternativeItem = null)
    {
        $items = $this->templateObj->getTemplateItems($setName, $templateBranch);
        if (count($items) === 0) {
            return array();
        } else {
            $tempModel = new Data2Html_Model_Set_Includes(null, $setName, $items, $alternativeItem);
            return $tempModel->getItems();
        }
    }

    protected function renderFormSet(
        $formId,
        $templateBranch,
        $fieldsDs,
        $replaces
    ){
        if (!$fieldsDs) {
            $fieldsDs = array();
        }
        
        $startItems = $this->parseIncludeItems('startItems', $templateBranch);
        $endItems = $this->parseIncludeItems('endItems', $templateBranch);
        $fieldsDs = array_merge($startItems, $fieldsDs, $endItems);
        
        if (count($fieldsDs) === 0) {
            return $this->templateObj->emptyRender();
        }

        $baseUrl = $this->getControllerUrl();
        $templateInputs =
            $this->templateObj->getTemplateBranch('inputs', $templateBranch);
        $templateLayouts =
            $this->templateObj->getTemplateBranch('inputs_layouts', $templateBranch);
            
        $defaultInputTemplates = $this->typeToInputTemplates['[default]'];
        
        $body = array();
        $renderCount = 0;
        
        foreach ($fieldsDs as $k => $v) {            
            $vDx = new Data2Html_Collection($v);
            if ($vDx->getBoolean('virtual')) {
                continue;
            }
            $url = $vDx->getString('url', '');
            $validations = $vDx->getArray('validations', array());
            $link = $vDx->getString('link');
            $type = $vDx->getString('type');

            $inputTplName = $vDx->getString('input');
            if ($inputTplName) {
                $inputTemplates = array($defaultInputTemplates[0], $inputTplName);
            } else {
                if ($link) {
                    $inputTemplates = array($defaultInputTemplates[0], 'select-input');
                    $url = $baseUrl . 'model=' . $link . '&';
                } else {
                    $inputTemplates = Data2Html_Value::getItem(
                        $this->typeToInputTemplates,
                        $type,
                        $defaultInputTemplates
                    );
                }
            }
            $fReplaces = array(
                'formId' => $formId,
                'id' => $this->createIdRender(),
                'name' => $k,
                'title' => $vDx->getString('title'),
                'description' => $vDx->getString('description'),
                'icon' => $vDx->getString('icon'),
                'action' => $vDx->getString('action'),
                'url' => $url,
                'validations' => implode(' ', $validations)
            );
            $fReplaces['html'] = $this->templateObj->renderTemplateItem(
                $inputTemplates[1], $templateInputs, $fReplaces
            );
            $this->templateObj->concatContents(
                $body,
                $this->templateObj->renderTemplateItem(
                    $vDx->getString('layout', $inputTemplates[0]),
                    $templateLayouts, 
                    $fReplaces
                )
            );
            ++$renderCount;
        }
        $replaces = array_merge($replaces, array(
            'id' => $formId,
            'body' => $body,
            'visual' => $this->getVisualItemsJson($fieldsDs)
        ));
        $form = $this->templateObj->renderTemplate(
            $templateBranch,
            $replaces
        );
        return $form;
    }
    
    protected function getVisualItemsJson($lkItems) {
        $visualItems = array();
        foreach ($lkItems as $k => $v) {
            if (!Data2Html_Value::getItem($v, 'virtual')) {
                if (!is_int($k)) {
                    $item = array();
                    $visualItems[$k] = &$item;
                    foreach ($this->visualWords as $w) {
                        $vv = Data2Html_Value::getItem($v, $w);
                        if ($vv) {
                            $item[$w] = $vv;
                        }
                    }
                    unset($item);
                }
            }
        }
        return str_replace('"', "'", Data2Html_Value::toJson($visualItems));
    }
}
