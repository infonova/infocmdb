<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class AdminController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        parent::setTranslatorLocal();
        /* Initialize action controller here */
    }

    public function indexAction()
    {
        // unused
    }

    // AJAX action
    public function enableAction()
    {
        if (parent::getUserInformation()->getRoot()) {
            $session            = Zend_Registry::get('session');
            $session->adminMode = true;
            Zend_Registry::set('session', $session);
        }
        exit;
    }

    // AJAX action
    public function disableAction()
    {
        if (parent::getUserInformation()->getRoot()) {
            $session            = Zend_Registry::get('session');
            $session->adminMode = false;
            Zend_Registry::set('session', $session);
        }
        exit;
    }


    /**
     *
     * show all active sessions
     */
    public function sessionAction()
    {
        if (!parent::getUserInformation()->getRoot()) {
            throw new Exception_AccessDenied();
        }

        $page      = $this->_getParam('page');
        $orderBy   = $this->_getParam('orderBy');
        $direction = $this->_getParam('direction');

        $adminService = new Service_Admin_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator    = $adminService->getSessionList($page, $orderBy, $direction);

        $this->view->paginator = $paginator;
        $this->view->page      = $page;
        $this->view->orderBy   = $orderBy;
        $this->view->direction = $direction;
    }


    /**
     *
     * kills a user session by the given sessionId
     * for admins only. handle with care!
     *
     * @throws Exception_AccessDenied
     */
    public function killsessionAction()
    {
        if (!parent::getUserInformation()->getRoot()) {
            throw new Exception_AccessDenied();
        }

        $sessionId = $this->_getParam('sessionId');

        $ownSession = Zend_Session::getId();
        if ($ownSession && $sessionId == $ownSession) {
            $notification['error'] = $this->translator->translate('killSessionOwnSession');
            $this->_helper->FlashMessenger($notification);
            $this->_redirect('admin/session/');
        }

        try {
            $adminService = new Service_Admin_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $adminService->killSession($sessionId);
            $notification['success'] = $this->translator->translate('killSessionSuccess');
        } catch (Exception_Attribute_KillSessionFailed $e) {
            $notification['error'] = $this->translator->translate('killSessionFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('admin/session/');
    }
}