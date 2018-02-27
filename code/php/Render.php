<?php

class Data2Html_Render
{
    public $debug = false;
    private $templateObj;
    private $idRender = null;
    
    private $matchTranslate = '/__\{([a-z][\w\-\/]*)\}/i';
        // Text are as: __{tow-word} or __{house/word}';
    private $typeToInputTemplates = array(
        '[default]' =>    array('base', 'text-input'),
        'boolean' =>    array('checkbox', 'checkbox'),
        'date' =>       array('base', 'datetimepicker')
    );
    private $visualWords = array(
        'display', 'format', 'size', 'title', 'type', 'validations', 'default'
    );
    private static $idRenderCount = 0;
    
    public function __construct()
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Render";
        
        $this->templateObj = new Data2Html_Render_Template();
    }

    public function dump($subject = null)
    {
        if (!$subject) {
            $subject = array(
            );
        }
        Data2Html_Utils::dump($this->culprit, $subject);
    }

    public function getControllerUrl()
    {
        return Data2Html_Config::getPath('controllerUrl') . '?';
    }
    
    public function renderGrid($model, $templateName, $gridName)
    {
        try {
            $this->idRender = $this->createIdRender();
            $this->templateObj->setTemplate($templateName);
            return $this->renderGridObj($model, $gridName);            
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
    }
    
    public function renderForm($model, $templateName, $formName)
    {
        try {
            $this->idRender = $this->createIdRender();
            $this->templateObj->setTemplate($templateName);
            return $this->renderFormObj($model, $formName);            
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
    }
    
    private function createIdRender() {
        self::$idRenderCount++;
        return 'd2h_' . self::$idRenderCount;
    }

    private function renderGridObj($model, $gridName)
    {        
        
        $this->culprit = "Render for grid: \"{$model->getModelName()}:{$gridName}\"";
        $lkGrid = $model->getGrid($gridName);
        $lkGrid->createLink();
        
        $tplGrid = $this->templateObj->getTemplateBranch(
            $lkGrid->getAttribute('layout', 'grid'),
            $this->templateObj->getTemplateRoot()
        );
        $gridId = $this->idRender . '_grid_' . $gridName;
        
        $tmplPage = $this->templateObj->getTemplateBranch('page', $tplGrid, false);
        if ($tmplPage) {
            $pageForm = $this->renderFormSet(
                $gridId . '_page',
                $tmplPage,
                null,
                array()
            );
        } else {
            $pageForm = null;
        }
        
        $lkFilter = $lkGrid->getFilter();
        if (!$lkFilter) {
            $filterForm = $this->templateObj->emptyRender();
        } else {
            $filterForm = $this->renderFormSet(
                $gridId . '_filter',
                $this->templateObj->getTemplateBranch('filter', $tplGrid),
                $lkFilter->getLinkedItems(),
                array(
                    'title' => $lkFilter->getAttributeUp('title'),
                )
            );
        }
        $klColumns = $lkGrid->getColumnsSet();
        
        $result = $this->renderGridSet(
            $this->templateObj->getTemplateBranch('table', $tplGrid),
            $klColumns->getLinkedItems(),
            array(
                'title' => $lkGrid->getAttributeUp('title'),
                'id' => $gridId,
                'url' => $this->getControllerUrl() .
                    "model={$model->getModelName()}:{$gridName}&",
                'sort' => $lkGrid->getAttributeUp('sort'),
                'filter' => $filterForm,
                'page' => $pageForm
            )
        );
        $result['id'] = $gridId; // Required to use d2h_display.js
        return $result;
    }
    
    private function renderFormObj($model, $formName)
    {
        $this->culprit =
            "Render for form: \"{$model->getModelName()}:{$formName}\"";
        $lkForm = $model->getForm($formName);
        $lkForm->createLink();
        
        return $this->renderFormSet(
            $this->idRender . '_form_' . $formName,
            $this->templateObj->getTemplateBranch(
                $lkForm->getAttribute('layout', 'form'),
                $this->templateObj->getTemplateRoot()
            ),
            $lkForm->getLinkedItems(),
            array(
                'title' => $lkForm->getAttributeUp('title'),
                'url' => $this->getControllerUrl() .
                     "model={$model->getModelName()}&form={$formName}&"
            )
        );
    }
    
    private function renderGridSet($templateTable, $columns, $replaces)
    {
        if (!$columns) {
            throw new Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return $this->templateObj->emptyRender();
        }
        list($thead, $renderCount) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateTable, 'head-item'),
                $columns,
                $this->parseIncludeItems('endItems', $templateTable, 'head-item')
            ),
            $this->templateObj->getTemplateBranch('heads', $templateTable)
        );
        list($tbody) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateTable),
                $columns,
                $this->parseIncludeItems('endItems', $templateTable)
            ),
            $this->templateObj->getTemplateBranch('cells', $templateTable)
        );
        
        // End
        $replaces = array_merge($replaces, array(
            'head' => $thead,
            'body' => $tbody,
            'colCount' => $renderCount,
            'visual' => $this->getVisualItems($columns)
        ));
        return $this->templateObj->renderTemplate($templateTable, $replaces);
        $previusLevel = $level;
    }

    protected function renderFormSet(
        $formId,
        $templateBranch,
        $items,
        $replaces
    ){
        $items = array_merge(
            $this->parseIncludeItems('startItems', $templateBranch),
            $items ? $items : array(),
            $this->parseIncludeItems('endItems', $templateBranch)
        );
        list($body) = $this->renderFlatSet($items, $templateBranch);
        
        $replaces['visual'] = $this->getVisualItems($items);
        $form = $this->templateObj->renderTemplate(
            $templateBranch,
            array_merge($replaces, array(
                'id' => $formId,
                'body' => $body
            ))
        );
        $form['id'] = $formId; // Required to use d2h_display.js
        return $form;
    }
    
    protected function renderFlatSet($items, $template, $iReplaces = array())
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
                $replaces['body'] = $itemBody;
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
                    if ($v === false) {
                        break;
                    }
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
                    'title' => $vDx->getString('title'),
                    'description' => $vDx->getString('description'),
                    'format' => $vDx->getString('format'),
                    'type' => $vDx->getString('type'),
                    'icon' => $vDx->getString('icon'),
                    'visualClassLayout' => $vDx->getString('visualClassLayout'),
                    'visualClassBody' => $vDx->getString('visualClassBody'),
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

    protected function getVisualItems($lkItems) {
        $visualItems = array();
        foreach ($lkItems as $k => $v) {
            if (!Data2Html_Value::getItem($v, 'virtual')) {
                if (!is_int($k)) {
                    $item = array();
                    $visualItems[$k] = &$item;
                    foreach ($this->visualWords as $w) {
                        if (array_key_exists($w, $v)) {
                            $item[$w] = $v[$w];
                        }
                    }
                    unset($item);
                }
            }
        }
        return $visualItems;
    }
}
