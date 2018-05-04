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
    
    public function renderGrid($model, $gridName, $templateName, $itemReplaces = null)
    {
        try {
            $this->culprit = "Render for grid: \"{$model->getModelName()}:{$gridName}\"";
            
            $templateBranch = _branches::startTree($templateName);
            $lkGrid = $model->getLinkedGrid($gridName);
            $gridId = $lkGrid->getId();
           
            $replaces = [
                'title' => $lkGrid->getAttributeUp('title'),
                'debug-name' => "{$model->getModelName()}@grid={$gridName}",
                'id' => $gridId,
                'url' => $this->getControllerUrl() .
                    "model={$model->getModelName()}:{$gridName}&",
                'sort' => $lkGrid->getAttributeUp('sort')
            ];
            // Page
            $replaces['id-page'] = $gridId . '_page';
            $replaces['page'] = $this->renderFormSet(
                $replaces['id-page'],
                _branches::getBranch('page', $templateBranch, false),
                null,
                []
            );
            // Filter
            $lkFilter = $lkGrid->getFilter();
            if ($lkFilter) {
                $replaces['id-filter'] = $lkFilter->getId();
                $replaces['filter'] = $this->renderFormSet(
                    $replaces['id-filter'],
                    _branches::getBranch('filter', $templateBranch, false),
                    $lkFilter->getLinkedItems(),
                    ['title' => $lkFilter->getAttributeUp('title')]
                );
            }

            return $this->renderGridSet(
                $gridId,
                _branches::getBranch('grid', $templateBranch),
                $lkGrid->getColumnsSet()->getLinkedItems(),
                $replaces
            );
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
    }
    
    public function renderElement($model, $formName, $templateName, $itemReplaces = null)
    {
        try {
            $this->culprit =
                "Render for element: \"{$model->getModelName()}:{$formName}\"";
            $lkForm = $model->getLinkedElement($formName);
            
            return $this->renderFormSet(
                $lkForm->getId(),
                _branches::startTree($templateName),
                $lkForm->getLinkedItems(),
                [
                    'title' => $lkForm->getAttributeUp('title'),
                    'debug-name' => "{$model->getModelName()}@element={$formName}",
                    'url' => $this->getControllerUrl() .
                         "model={$model->getModelName()}&element={$formName}&"
                ],
                $itemReplaces
            );
        } catch(Exception $e) {
            // Message to user            
            echo Data2Html_Exception::toHtml($e, Data2Html_Config::debug());
            exit();
        }
    }

    private function renderGridSet($gridId, $templateBranch, $columns, $bodyReplaces, $itemReplaces = null)
    {
        if (!$columns) {
            throw new Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return _branches::renderEmpty();
        }

        $itemReplaces = $itemReplaces ? $itemReplaces : [];
        $itemReplaces['from-id'] = $bodyReplaces['id'];
        list($thead, $renderCount) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateBranch, 'head-item'),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch, 'head-item')
            ),
            _branches::getBranch('heads', $templateBranch),
            $itemReplaces
        );
        list($tbody) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateBranch),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch)
            ),
            _branches::getBranch('cells', $templateBranch),
            $itemReplaces
        );
        
        // End
        $bodyReplaces = array_merge($bodyReplaces, array(
            'head' => $thead,
            'body' => $tbody,
            'colCount' => $renderCount,
            'visual' => Data2Html_Model_Set::getVisualItems($columns)
        ));
        
        $result = _templates::apply(
            _branches::getItem('template', $templateBranch),
            $bodyReplaces
        );
        $result['id'] = $gridId; // Required by d2h_display.js
        return $result;
    }

    protected function renderFormSet($formId, $templateBranch, $items, $bodyReplaces, $itemReplaces = null)
    {
        $items = array_merge(
            $this->parseIncludeItems('startItems', $templateBranch),
            $items ? $items : [],
            $this->parseIncludeItems('endItems', $templateBranch)
        );
        $itemReplaces = $itemReplaces ? $itemReplaces : [];
        $itemReplaces['from-id'] = $formId;
        list($body) = $this->renderFlatSet(
            $items,
            $templateBranch,
            $itemReplaces
        );
        
        $bodyReplaces['visual'] = Data2Html_Model_Set::getVisualItems($items);
        $form = _templates::apply(
            _branches::getItem('template', $templateBranch),
            array_merge($bodyReplaces, [
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
                    'id' => 'd2h_item_' . ++self::$idRenderCount,
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
}
