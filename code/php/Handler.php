<?php
namespace Data2Html;

use Data2Html\DebugException;
use Data2Html\Model\Models;
use Data2Html\Data\Lot;
use Data2Html\Data\To;
use Data2Html\Data\Response;

/**
 * Nomenclature
 *  * Variable name suffixes:
 *      * ..vDx: Definitions as a `Lot` instance.
 *      * ..Ds: Definitions as a array.
 */
 
class Handler
{
    // ========================
    // Server
    // ========================
    /**
     * Controller
     */    
    public static function manage($request)
    {
        try {
            $controller = new Controller();
            Response::json($controller->manage($request));
        } catch(\Exception $e) {
            // Message to user
            // header('HTTP/1.1 409 Conflict');
            header('HTTP/1.1 500 Error');
            Response::json(DebugException::toArray($e));
        }
    }
 
    /**
     * Render
     */    
    public static function render($request, $templateName)
    {
        try {
            $payerNames = Models::parseRequest($request);           
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
        $render = self::createRender();
        if (array_key_exists('block', $payerNames)) {
            $result = $render->renderBlock($payerNames['model'], $payerNames['block'], $templateName);
        } elseif (array_key_exists('grid', $payerNames)) {
            $result = $render->renderGrid($payerNames['model'], $payerNames['grid'], $templateName);
        } else {
            throw new \Exception("No requested object name in parameter.");
        }
        return $result;
    }

    public static function createRender()
    {
        try {
            return new Render();
        } catch(\Exception $e) {
            // Message to user            
            echo DebugException::toHtml($e);
            exit();
        }
    }
}
