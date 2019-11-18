<?php
require_once 'AbstractAppAction.php';

/**
 *
 *
 *
 */
class IndexController extends AbstractAppAction
{

    public function init()
    {
        parent::init();
        parent::setTranslatorLocal();
        /* Initialize action controller here */
    }

    public function indexAction()
    {

    }

    public function maintenanceAction()
    {
        $this->_helper->layout->setLayout('login');
        $this->view->headTitle('Maintenance Mode');
        $lockFile               = APPLICATION_PATH . '/../cmdb.lock';
        $maintenanceModeEnabled = file_exists($lockFile);

        if ($maintenanceModeEnabled === true) {
            $this->view->message      = file_get_contents($lockFile);
            $disableForCurrentSession = $this->getParam('disableForCurrentSession');
            $session                  = Zend_Registry::get('session');
            if ($disableForCurrentSession == 1) {
                $session->disableMaintenanceForUser = true;
                $this->redirect('index/index');
            }
        } else {
            $this->redirect('index/index');
        }

    }
}