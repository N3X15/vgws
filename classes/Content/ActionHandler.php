<?php
namespace VGWS\Content;
// Handles messages sent from AJAX (ajax=1)
class ActionHandler
{
    public $page;
    public $handleAjaxRequests;
    public $path = array();
    /**
     *  Clone of the request ($_REQUEST)
     */
    public $request = array();
    public $response = '';
    public $error = '';
    public $tpl;

    /**
     * Hide all links on the page?
     */
    public $hideLinks = false;

    public function __construct($page, $ajax = false)
    {
        //$this->tpl = Page::GetSavant();
        //var_dump($handler);
        $this->page = $page;
        $this->handleAjaxRequests = $ajax;
    }

    public function OnRequest()
    {
        // Override
    }

    public function CanAccess() {
        return true;
    }

}
