<?php
require_once 'BaseController.php';

class Api_CiRecursiveController extends BaseController
{


    /**
     * retrieve list
     */
    public function indexAction()
    {
        $typeId = $this->_getParam('id');

        $page    = $this->_getParam('page');
        $orderBy = $this->_getParam('order');
        if (empty($orderBy)) {
            $orderBy = array();
        } else {
            $orderBy = array($orderBy => 'DESC');
        }

        if (!$page) {
            $page = 1;
        }

        try {
            $userInfo  = parent::getUserInformation();
            $themeId   = $userInfo[Db_User::THEME_ID];
            $userId    = $userInfo[Db_User::ID];
            $projectId = null;

            $ciService = new Service_Ci_Get($this->translator, $this->logger, $themeId);
            $ciList    = $ciService->getCiList($typeId, $userId, $themeId, $projectId, 25, $page, $orderBy);

            $result                 = array();
            $result['item_count']   = $ciList['paginator']->getTotalItemCount();
            $result['current_page'] = $page;
            $result['pages']        = $ciList['paginator']->getPageRange();
            $result['cilist']       = array();

            foreach ($ciList['paginator'] as $res) {
                array_push($result['cilist'], $res);
            }

            $xml = parent::getXML($result);
            $this->getResponse()
                ->appendBody($xml);

        } catch (Exception_AccessDenied $e) {
            parent::forbidden();
        } catch (Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(404)
                ->appendBody("requested ci type " . $typeId . " not found");
        }

    }


    /**
     * retrieve single ci
     */
    public function getAction()
    {
        // index IS get action
        $this->_forward('index');

    }

    /**
     * save ci
     */
    public function postAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(201)
            ->appendBody("created the article\n")
            ->appendBody("http://zfrest.example.com/article/5");
    }

    /**
     * update ci
     */
    public function putAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(503)
            ->appendBody("unable to process put requests. Please try later");
    }

    /**
     * delete ci
     */
    public function deleteAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(204);
    }

}