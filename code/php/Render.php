<?php
namespace Data2Html;

use Data2Html\Render\Branches;
use Data2Html\Render\Templates;
use Data2Html\Render\FileContents;

class Render
{
    use Debug;

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
    }

    public function getControllerUrl()
    {
        return Config::getPath('controllerUrl') . '?';
    }
    
    public function render($replaces, $templateName)
    {
        try {
            return Templates::apply(
                FileContents::get($templateName),
                $replaces
            );           
        } catch(Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
    }
    
    public function renderGrid($model, $gridName, $templateName, $itemReplaces = null)
    {
        try {
            $templateBranch = new Branch(FileContents::load($templateName));
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
                $templateBranch->getBranch('page', false),
                null,
                []
            );
            // Filter
            $lkFilter = $lkGrid->getFilter();
            if ($lkFilter) {
                $replaces['id-filter'] = $lkFilter->getId();
                $replaces['filter'] = $this->renderFormSet(
                    $replaces['id-filter'],
                    $templateBranch->getBranch('filter', false),
                    $lkFilter->getLinkedItems(),
                    ['title' => $lkFilter->getAttributeUp('title')]
                );
            }

            return $this->renderGridSet(
                $gridId,
                $templateBranch->getBranch('grid'),
                $lkGrid->getColumnsSet()->getLinkedItems(),
                $replaces
            );
        } catch(Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
    }
    
    public function renderElement($model, $formName, $templateName, $itemReplaces = null)
    {
        try {
            $lkForm = $model->getLinkedElement($formName);
            
            return $this->renderFormSet(
                $lkForm->getId(),
                new Branch(FileContents::load($templateName)),
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
            echo DebugException::toHtml($e);
            exit();
        }
    }

    private function renderGridSet(
        $gridId,
        Branch $templateBranch,
        $columns,
        $bodyReplaces,
        $itemReplaces = null
    ) {
        if (!$columns) {
            throw new Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return Branches::renderEmpty();
        }

        $itemReplaces = $itemReplaces ? $itemReplaces : [];
        $itemReplaces['from-id'] = $bodyReplaces['id'];
        list($thead, $renderCount) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateBranch, 'head-item'),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch, 'head-item')
            ),
            $templateBranch->getBranch('heads'),
            $itemReplaces
        );
        list($tbody) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateBranch),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch)
            ),
            $templateBranch->getBranch('cells'),
            $itemReplaces
        );
        
        // End
        $bodyReplaces = array_merge($bodyReplaces, array(
            'head' => $thead,
            'body' => $tbody,
            'colCount' => $renderCount,
            'visual' => Data2Html_Model_Set::getVisualItems($columns)
        ));
        
        $result = Templates::apply(
            $templateBranch->getItem('template'),
            $bodyReplaces
        );
        $result['id'] = $gridId; // Required by d2h_display.js
        return $result;
    }

    protected function renderFormSet(
        $formId,
        Branch $templateBranch,
        $items,
        $bodyReplaces,
        $itemReplaces = null
    ) {
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
        $form = Templates::apply(
            $templateBranch->getItem('template'),
            array_merge($bodyReplaces, [
                'id' => $formId,
                'body' => $body
            ])
        );
        $form['id'] = $formId; // Required by d2h_display.js
        return $form;
    }
    
    protected function renderFlatSet($items, Branch $template, $iReplaces = array())
    {
        $assignTemplate = $template->getItem(
            "assign-template"
            function() { return array('base', 'base', array()); }
        );
        $tLayouts = $template->getBranch('layouts');
        $tContents = $template->getBranch('contents');
        
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
                    Templates::concat($itemBody, $levelBody);
                }
                $replaces['body'] = $itemBody;
                Templates::concat(
                    $body,
                    Templates::apply(
                        $tLayouts->getItem(['templates', $layoutTemplName]),
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
                $level =  Lot::getItem('level', $v, 0);
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
                    $level =  Lot::getItem($v, 'level', $v, 0);
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
                $itemBody = Templates::apply(
                    $tContents->getItem(['templates', $contentTemplName]),
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

    protected function parseIncludeItems(
        $setName,
        Branch $templateBranch,
        $alternativeItem = null
    ) {
        $items = $templateBranch->getItem($setName);
        if (count($items) === 0) {
            return array();
        } else {
            $tempModel = new Data2Html_Model_Set_Includes(null, $setName, $items, $alternativeItem);
            return $tempModel->getItems();
        }
    }
}
