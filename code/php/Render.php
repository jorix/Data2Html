<?php
namespace Data2Html;

use Data2Html\Data\Lot;
use Data2Html\Render\Branch;
use Data2Html\Render\Content;
use Data2Html\Model\Set;
use Data2Html\Model\Set\Includes as SetIncludes;

class Render
{
    use Debug;
    
    private $matchTranslate = '/__\{([a-z][\w\-\/]*)\}/i';
        // Text are as: __{tow-word} or __{house/word}';

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
            $templateBranch = new Branch($templateNam);
            return new Content(
                $templateBranch->gtTemplate('template'),
                $replaces
            );        
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
    }
    
    public function renderGrid($model, $gridName, $templateName, $itemReplaces = null)
    {
        try {
            $templateBranch = new Branch($templateName);
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
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
    }
    
    public function renderElement($model, $formName, $templateName, $itemReplaces = null)
    {
        try {
            $lkForm = $model->getLinkedBlock($formName);
            
            return $this->renderFormSet(
                $lkForm->getId(),
                new Branch($templateName),
                $lkForm->getLinkedItems(),
                [
                    'title' => $lkForm->getAttributeUp('title'),
                    'debug-name' => "{$model->getModelName()}@element={$formName}",
                    'url' => $this->getControllerUrl() .
                         "model={$model->getModelName()}&element={$formName}&"
                ],
                $itemReplaces
            );
        } catch(\Exception $e) {
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
            throw new \Exception("`\$columns` parameter is empty.");
        }
        if (count($columns) === 0) {
            return new Content();
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
            'id' => $gridId,
            'head' => $thead,
            'body' => $tbody,
            'colCount' => $renderCount,
            'visual' => Set::getVisualItems($columns)
        ));
        
        return new Content(
            $templateBranch->getTemplate('template'),
            $bodyReplaces
        );
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
        
        $bodyReplaces['visual'] = Set::getVisualItems($items);
        $form = new Content(
            $templateBranch->getTemplate('template'),
            array_merge($bodyReplaces, [
                'id' => $formId,
                'body' => $body
            ])
        );
        return $form;
    }
    
    protected function renderFlatSet($items, Branch $template, $iReplaces = array())
    {
        $assignTemplate = $template->getItem(
            "assign-template"
            function() { return array('base', 'base', array()); }
        );
        $tLayouts = $template->getBranch(['layouts', 'templates']);
        $tContents = $template->getBranch(['contents', 'templates']);
        
        return $this->renderFlatLevelSet(0, &$assignTemplate, $tContents, $tLayouts, &$items, $iReplaces)
    }

    protected function renderFlatLevelSet($currentLevel, &$items, $tContents, $tLayouts, &$assignTemplate, $iReplaces)
    {
        $body = new Content();
        
        // Current level
        $levelBody = new Content();
        $itemLayoutName = null;
        $itemReplaces = null;
        $vDx = new Lot();
        $renderCount = 0;
        
        $v = current($items); 
        while ($v !== false) {
            $level = Lot::getItem('level', $v, 0);
            
            // Get down level and add to previous item
            if ($level > $currentLevel) { 
                // a level always is started with a item, therefore previous item exist and $itemReplaces is not null
                list($nextLevelBody) = $this->renderFlatLevelSet($level, $items, $tContents, $tLayouts, $assignTemplate, $itemReplaces)
                $itemBody->add($nextLevelBody);
                // read next item and force current level
                $v = current($items); // v$ is pending to verify if items are ended
                $level = $currentLevel; // to force finish previous item ()
            }
            
            // Apply the layout to finish previous item only if is started 
            if ($level === $currentLevel && $itemReplaces) {
                $itemReplaces['body'] = $itemBody;
                $levelBody->add(
                    $tLayouts->getTemplate($itemLayoutName),
                    $itemReplaces
                );
                // verify if items are ended by a previous down level
                if ($v === false) {
                    break;
                }
                // set next item level
                $level = Lot::getItem('level', $v, 0);
            }

            // this level is ended
            if ($level < $currentLevel) {
                break;
            }
            
            // Start current item
            $vDx->set($v);
            $itemLayoutName = null;
            $itemReplaces = null;
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
                $itemLayoutName,
                $contentTemplName,
                $itemReplaces
            ) = $assignTemplate($this, $v);
            $itemReplaces += $iReplaces + array(
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
            $itemBody = new Content(
                $tContents->getTemplate($contentTemplName),
                $itemReplaces
            );
            $v = next($items);
        }
        // Finalize previous item
        if ($itemReplaces) {
            $itemReplaces['body'] = $itemBody;
            $levelBody->add(
                $tLayouts->getTemplate($itemLayoutName),
                $itemReplaces
            );
        }
        return [$levelBody, $renderCount]
    };
    
    protected function parseIncludeItems(
        $setName,
        Branch $templateBranch,
        $alternativeItem = null
    ) {
        $items = $templateBranch->getItem($setName);
        if (count($items) === 0) {
            return [];
        } else {
            $tempModel = new SetIncludes(null, $setName, $items, $alternativeItem);
            return $tempModel->getItems();
        }
    }
}
