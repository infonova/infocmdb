<?php
require_once 'AbstractAppAction.php';

/**
 *
 * TODO: check if still in use
 *
 *
 */
class ConsoleController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        parent::setTranslatorLocal();
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        $this->logger->log('Index action has been invoked', Zend_Log::DEBUG);
        // action body
    }

    public function executionscriptAction()
    {
        $ciId          = $this->_getParam('ciId');
        $attributeId   = $this->_getParam('attributeId');
        $ciAttributeId = $this->_getParam('ciAttributeId');

        $utilExecutable = new Util_Executable($this->logger);
        $user           = parent::getUserInformation();

        $result = $utilExecutable->startExecutable($ciId, $attributeId, $ciAttributeId, $user);

        $this->_helper->FlashMessenger($result['notification']);
        if ($result['redirect_url'] !== '') {
            $this->_redirect($result['redirect_url']);
        } else {
            $this->_redirect('ci/detail/ciid/' . $ciId);
        }

    }
}