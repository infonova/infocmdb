<?php
require_once 'BaseController.php';

class Api_CitypeController extends BaseController
{


    public function indexAction()
    {
        $typeId = $this->_getParam('id');

        try {
            $userInfo = parent::getUserInformation();
            $themeId  = $userInfo[Db_User::THEME_ID];

            $ciTypeService = new Service_Citype_Get($this->translator, $this->logger, $themeId);
            $ciType        = $ciTypeService->getCiType($typeId);

            $newArray           = array();
            $newArray['citype'] = $ciType;

            $testarray        = array();
            $testarray['uno'] = array($newArray, $newArray);
            $xml              = parent::getXML($testarray);
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


    public function getAction()
    {
        // index IS get action
        $this->_forward('index');

    }

//    public function postAction()
//    {
//        $this->getResponse()
//             ->setHttpResponseCode(201)
//            ->appendBody("created the article\n")
//            ->appendBody("http://zfrest.example.com/article/5");
//    }
//
//    public function putAction()
//    {
//        $this->getResponse()
//            ->setHttpResponseCode(503)
//            ->appendBody("unable to process put requests. Please try later");
//    }

//    public function deleteAction()
//    {
//        $this->getResponse()
//            ->setHttpResponseCode(204);
//    }

}