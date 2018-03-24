<?php
use Data2Html_Render_Branches as _branches;
use Data2Html_Render_Templates as _templates;

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
        'display', 'format', 'size', 'title', 'type', 'validations', 'default', 'db'
    );
    private static $idRenderCount = 0;
    
    public function __construct()
    {
        $this->debug = Data2Html_Config::debug();
        $this->culprit = "Render";
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
    
    public function render($replaces, $templateName)
    {
        try {
            return _templates::apply(
                Data2Html_Render_FileContents::get($templateName),
                $replaces
            );           
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
    }
    
    public function renderGrid($model, $gridName, $templateName, $options = null)
    {
        try {
            $this->culprit = "Render for grid: \"{$model->getModelName()}:{$gridName}\"";
            
            $gridId = $this->createIdRender() . '_grid_' . $gridName;
            $templateBranch = _branches::startTree($templateName);
            $lkGrid = $model->getGrid($gridName);
            $lkGrid->createLink();

            // Page
            $pageForm = $this->renderFormSet(
                $gridId . '_page',
                _branches::getBranch('page', $templateBranch, false),
                null,
                []
            );
            // Filter
            $lkFilter = $lkGrid->getFilter();
            if (!$lkFilter) {
                $filterForm = _templates::renderEmpty();
            } else {
                $filterForm = $this->renderFormSet(
                    $gridId . '_filter',
                    _branches::getBranch('filter', $templateBranch, false),
                    $lkFilter->getLinkedItems(),
                    ['title' => $lkFilter->getAttributeUp('title')]
                );
            }

            return $this->renderGridSet(
                $gridId,
                _branches::getBranch('grid', $templateBranch),
                $lkGrid->getColumnsSet()->getLinkedItems(),
                array(
                    'title' => $lkGrid->getAttributeUp('title'),
                    'debug-name' => "{$model->getModelName()}@grid={$gridName}",
                    'id' => $gridId,
                    'url' => $this->getControllerUrl() .
                        "model={$model->getModelName()}:{$gridName}&",
                    'sort' => $lkGrid->getAttributeUp('sort'),
                    'filter' => $filterForm,
                    'page' => $pageForm
                )
            );
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
    }
    
    public function renderElement($model, $formName, $templateName, $options = null)
    {
        try {
            $this->culprit =
                "Render for element: \"{$model->getModelName()}:{$formName}\"";
            $lkForm = $model->getElement($formName);
            $lkForm->createLink();
            
            $options = [
                'branch' => [
                    'model' => 'aixada_ufs',
                    'grid' => 'uf_members',
                    'from' => 'main'
                ]
            ];
            
            
            
            
            return $this->renderFormSet(
                $this->createIdRender() . '_elem_' . $formName,
                _branches::startTree($templateName),
                $lkForm->getLinkedItems(),
                array(
                    'title' => $lkForm->getAttributeUp('title'),
                    'debug-name' => "{$model->getModelName()}@element={$formName}",
                    'url' => $this->getControllerUrl() .
                         "model={$model->getModelName()}&element={$formName}&",
                    'branch' => [
                        'model' => 'aixada_ufs',
                        'grid' => 'uf_members',
                        'from' => 'main'
                    ]
                )
            );
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

    private function renderGridSet($gridId, $templateBranch, $columns, $replaces)
    {
        if (!$columns) {
            throw new Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return _branches::renderEmpty();
        }

        list($thead, $renderCount) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateBranch, 'head-item'),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch, 'head-item')
            ),
            _branches::getBranch('heads', $templateBranch),
            ['from-id' => $replaces['id']]
        );
        list($tbody) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateBranch),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch)
            ),
            _branches::getBranch('cells', $templateBranch),
            ['from-id' => $replaces['id']]
        );
        
        // End
        $replaces = array_merge($replaces, array(
            'head' => $thead,
            'body' => $tbody,
            'colCount' => $renderCount,
            'visual' => $this->getVisualItems($columns)
        ));
        
        $result = _templates::apply(
            _branches::getItem('template', $templateBranch),
            $replaces
        );
        $result['id'] = $gridId; // Required by d2h_display.js
        return $result;
    }

    protected function renderFormSet($formId, $templateBranch, $items, $replaces)
    {
        $items = array_merge(
            $this->parseIncludeItems('startItems', $templateBranch),
            $items ? $items : [],
            $this->parseIncludeItems('endItems', $templateBranch)
        );
        list($body) = $this->renderFlatSet(
            $items,
            $templateBranch,
            ['from-id' => $formId]
        );
        
        $replaces['visual'] = $this->getVisualItems($items);
        $form = _templates::apply(
            _branches::getItem('template', $templateBranch),
            array_merge($replaces, [
                'id' => $formId,
                'body' => $body
            ])
        );
        $form['id'] = $formId; // Required by d2h_display.js
        return $form;
    }
    
    protected function renderFlatSet($items, $template, $iReplaces = array())
    {
        $assignTemplate = Data2Html_Value::getItem(
            $template[1], 
            "assign-template", 
            function() { return array('base', 'base', array()); }
        );
        $tLayouts = _branches::getBranch('layouts', $template);
        $tContents = _branches::getBranch('contents', $template);
        
        $renderSetLevel = function($currentLevel) 
        use(&$renderSetLevel, &$assignTemplate, &$items, $tContents, $tLayouts, $iReplaces)
        {
            $body = null;
            $vDx = new Data2Html_Collection();

            // Declare end previous item function
            $endPreviousItem = function($itemBody, $levelBody, $layoutTemplName, $replaces) 
            use(&$body, $tLayouts) {
                if (!$itemBody) {
                    return;
                }
                if ($levelBody) {
                    _templates::concat($itemBody, $levelBody);
                }
                $replaces['body'] = $itemBody;
                _templates::concat(
                    $body,
                    _templates::apply(
                        _branches::getItem(
                            ['templates', $layoutTemplName],
                            $tLayouts
                        ),
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
                    'debug-name' => key($items),
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
                ++$renderCount;
                $itemBody = _templates::apply(
                    _branches::getItem(
                        ['templates', $contentTemplName],
                       $tContents
                    ),
                    $replaces
                );
                //$this->dump([$contentTemplName, $tContents, $replaces, $itemBody]);

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
        $items = _branches::getItem($setName, $templateBranch);
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
