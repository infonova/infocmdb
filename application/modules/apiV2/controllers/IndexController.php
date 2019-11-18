<?php

require_once 'V2BaseController.php';

class ApiV2_IndexController extends V2BaseController
{
    public function list()
    {
        /** @var Zend_Controller_Action_Helper_ViewRenderer $renderer */
        $renderer = $this->_helper->viewRenderer;
        $renderer->setNoRender(false); // re-enable - disabled in V2BaseController
    }
}