<?php
namespace Data2Html;

use Data2Html\DebugException;
use Data2Html\Data\Lot;
use Data2Html\Render\Branch;
use Data2Html\Render\Content;
use Data2Html\Model\Set;
use Data2Html\Model\Models;
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
            $templateBranch = new Branch($templateName);
            return new Content(
                $templateBranch->getTemplate('template'),
                $replaces
            );        
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit;
        }
    }
    
    public function renderGrid($modelName, $gridName, $templateName, $itemReplaces = null)
    {
        try {
            $templateBranch = new Branch($templateName);
            if (!$gridName) {
                $gridName = 'main';
            }
            $lkGrid = Models::linkGrid($modelName, $gridName);
            $gridId = $lkGrid->getId();
           
            $replaces = [
                'debug-name' => "{$modelName}@grid={$gridName}",
                '_level' => -1,
                'title' => $lkGrid->getAttributeUp('title'),
                'id' => $gridId,
                'url' => $this->getControllerUrl() .
                    "grid={$modelName}:{$gridName}",
                'sort' => $lkGrid->getSort()
            ];
            // Page
            
            if ($lkGrid->getAttribute(['options', 'page'], true)) {
                $replaces['id-page'] = $gridId . '_page';
                $replaces['page'] = $this->renderBlockSet(
                    $replaces['id-page'],
                    $templateBranch->getBranch('page', false),
                    null,
                    [
                        'debug-name' => "{$modelName}@grid-page={$gridName}",
                        '_level' => -1
                    ]
                );
            }
            
            // Filter
            $lkFilter = $lkGrid->getFilter();
            if ($lkFilter) {
                $replaces['id-filter'] = $lkFilter->getId();
                $replaces['filter'] = $this->renderBlockSet(
                    $replaces['id-filter'],
                    $templateBranch->getBranch('filter', false),
                    $lkFilter->getLinkedItems(),
                    [
                        'debug-name' => "{$modelName}@grid-filter={$gridName}",
                        '_level' => -1,
                        'title' => $lkFilter->getAttributeUp('title')
                    ]
                );
            }

            return $this->renderGridSet(
                $gridId,
                $templateBranch->getBranch('grid'),
                $lkGrid->getLinkedColumns()->getLinkedItems(),
                $replaces
            );
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit;
        }
    }
    
    public function renderBlock($modelName, $blockName, $templateName, $itemReplaces = null)
    {
        try {
            $lkForm = Models::linkBlock($modelName, $blockName);
            
            return $this->renderBlockSet(
                $lkForm->getId(),
                new Branch($templateName),
                $lkForm->getLinkedItems(),
                [
                    'debug-name' => "{$modelName}@block={$blockName}",
                    '_level' => -1,
                    'title' => $lkForm->getAttributeUp('title'),
                    'url' => $this->getControllerUrl() .
                         "block={$modelName}:{$blockName}&"
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
                $this->parseIncludeItems('startItems', $templateBranch, 'head-items'),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch, 'head-items')
            ),
            $templateBranch->getBranch('heads'),
            $itemReplaces
        );
        list($tbody) = $this->renderFlatSet(
            array_merge(
                $this->parseIncludeItems('startItems', $templateBranch, 'body-items'),
                $columns,
                $this->parseIncludeItems('endItems', $templateBranch, 'body-items')
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

    protected function renderBlockSet(
        $blockId,
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
        $itemReplaces['from-id'] = $blockId;
        list($body) = $this->renderFlatSet(
            $items,
            $templateBranch,
            $itemReplaces
        );
        
        $bodyReplaces['visual'] = Set::getVisualItems($items);
        return new Content(
            $templateBranch->getTemplate('template'),
            array_merge($bodyReplaces, [
                'id' => $blockId,
                'body' => $body
            ])
        );
    }
    
    protected function renderFlatSet($items, Branch $template, $iReplaces = array())
    {
        $assignTemplate = $template->getItem(
            "assign-template",
            function() { return ['base', 'base', []]; }
        );
        $tLayouts = $template->getBranch(['layouts', 'templates']);
        $tContents = $template->getBranch(['contents', 'templates']);
        
        return $this->renderFlatLevelSet(
            0,
            $items,
            $tContents,
            $tLayouts,
            $assignTemplate,
            $iReplaces
        );
    }

    protected function renderFlatLevelSet(
        $currentLevel,
        &$items,
        $tContents,
        $tLayouts,
        &$assignTemplate,
        $iReplaces
    ) {
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
                list($nextLevelBody) = $this->renderFlatLevelSet(
                    $level,
                    $items,
                    $tContents,
                    $tLayouts,
                    $assignTemplate,
                    $itemReplaces
                );
                $itemBody->add($nextLevelBody);
                
                // read actual item after end level and force his level
                // v$ is pending to verify if items are ended
                $v = current($items); 
                $level = Lot::getItem('level', $v, 0);
            }
            
            // Apply the layout to finish previous item only if is started 
            if ($level <= $currentLevel) {
                if ($itemReplaces) {
                    $itemReplaces['body'] = $itemBody;
                    $levelBody->add(
                        $tLayouts->getTemplate($itemLayoutName),
                        $itemReplaces
                    );
                    $itemReplaces = null;
                }
            }

            // verify if items are ended by a previous down level
            if ($v === false || $level < $currentLevel) {
                break;
            }
            
            // Start current item
            $vDx->set($v);
            $itemLayoutName = null;
            $itemReplaces = null;
            if ($vDx->getBoolean('_instrumental')) {
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
            $itemReplaces = array_merge($iReplaces, $itemReplaces , [
                'debug-name' => key($items),
                '_level' => $level,
                '_level-0' => ($level === 0),
                'id' => 'd2h_item_' . ++self::$idRenderCount,
                'name' => key($items),
                'title' => $vDx->getString('title'),
                'description' => $vDx->getString('description'),
                'format' => $vDx->getString('format'),
                'visual-attr' => Set::getVisualAttr($v),
                'icon' => $vDx->getString('icon'),
                'visualClassLayout' => $vDx->getString('visualClassLayout'),
                'visualClassBody' => $vDx->getString('visualClassBody'),
                'action' => $vDx->getString('action')
                ]
            );
            ++$renderCount;
            $itemBody = new Content(
                $tContents->getTemplate($contentTemplName),
                $itemReplaces
            );
            $itemBody->repeat($vDx->get('list'));
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
        return [$levelBody, $renderCount];
    }
    
    protected function parseIncludeItems(
        $setName,
        Branch $templateBranch,
        $alternativeItem = 'items'
    ) {
        $items = $templateBranch->getTemplate($setName, false);
        if (!$items) {
            return [];
        } else {
            $tempModel = new SetIncludes($setName, $items, $alternativeItem);
            return $tempModel->getItems();
        }
    }
}
