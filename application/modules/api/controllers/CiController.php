<?php
require_once 'BaseController.php';

class Api_CiController extends BaseController
{

    /**
     * retrieve list of cis
     */
    public function indexAction()
    {
        if ($this->_getParam('id', false)) {
            $this->forward('get');
            return;
        }

        $typeId    = $this->_getParam('typeId');
        $recursive = $this->_getParam('recursive');
        $limitFrom = $this->_getParam('offset');
        $limitTo   = $this->_getParam('limit');

        if (!$page) {
            $page = 1;
        }
        try {
            $userInfo  = parent::getUserInformation();
            $themeId   = $userInfo[Db_User::THEME_ID];
            $userId    = $userInfo[Db_User::ID];
            $projectId = null;

            $ciService = new Service_Ci_Get($this->translator, $this->logger, $themeId);
            $ciList    = $ciService->getCiListIds($typeId, $userId, $themeId, $projectId, $limitFrom, $limitTo, $recursive);

            $result               = array();
            $result['ci_type']    = $typeId;
            $result['item_count'] = count($ciList);
            $result['offset']     = $limitFrom;
            $result['limit']      = $limitTo;

            $result['cilist'] = $ciList;

            foreach ($ciList as $res) {
                array_push($result['cilist'], $res);
            }

            $responseCode = 200;
            if (count($ciList) < 1) {
                // EMPTY
                $ciList['message'] = 'no items found';
                $responseCode      = 204;
            }

            $xml = parent::getReturnValue($ciList);
            $this->getResponse()
                ->setHttpResponseCode($responseCode)
                ->appendBody($xml);

        } catch (Exception_AccessDenied $e) {
            parent::forbidden();
        } catch (Exception_Ci_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknownen error while updating Ci "' . $ciId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('ciUpdateFailed');

            $this->getResponse()
                ->setHttpResponseCode(404)
                ->appendBody("requested cis for ci type " . $typeId . " not found");
        } catch (Exception_Ci_UpdateFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Ci "' . $ciId . '" ', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('ciUpdateFailed');

            $this->getResponse()
                ->setHttpResponseCode(404)
                ->appendBody("requested cis for ci type " . $typeId . " not found");
        } catch (Exception_Ci_UpdateItemNotFound $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to update Ci "' . $ciId . '". No items where updated!', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('ciUpdateFailed');

            $this->getResponse()
                ->setHttpResponseCode(404)
                ->appendBody("requested cis for ci type " . $typeId . " not found");
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->appendBody("unexpected exception. please check your request and contact your administrator.");
        }
    }


    /**
     * retrieve single ci
     */
    public function getAction()
    {
        $ciId = $this->_getParam('id');

        try {
            $userInfo  = parent::getUserInformation();
            $themeId   = $userInfo[Db_User::THEME_ID];
            $userId    = $userInfo[Db_User::ID];
            $projectId = null;

            $ciService = new Service_Ci_Get($this->translator, $this->logger, $themeId);
            $ci        = $ciService->getCiDetail($ciId, $userId);

            $result              = array();
            $result['id']        = $ciId;
            $result['ci_type']   = $ci['ciTypeDto'][Db_CiType::NAME];
            $result['user']      = $ci['user'];
            $result['created']   = $ci['created'];
            $result['last_edit'] = $ci['ciHistoryDto'][Db_History::DATESTAMP];

            $result['projects'] = array();
            foreach ($ci['projectList'] as $project) {
                $result['projects'][$project[Db_Project::NAME]] = $project[Db_Project::ID];
            }

            if ($ci['icon'])
                $result['icon'] = $ci['iconPath'] . '/' . $ci['icon'];


            $result['attributes'] = array();
            foreach ($ci['attributeList'] as $attributeGroups) {

                $result['attributes'][$attributeGroups['name']] = array();

                if ($attributeGroups['attributes'])
                    foreach ($attributeGroups['attributes'] as $res) {
                        $result['attributes'][$attributeGroups['name']][$res['name']] = $res['value_text'] . $res['value_date'] . $res['value_default'];
                    }
            }

            $xml = parent::getReturnValue($result);


            $this->getResponse()
                ->setHttpResponseCode(200)
                ->appendBody($xml);

        } catch (Exception_AccessDenied $e) {
            parent::forbidden();
        } catch (Exception_Ci_Unknown $e) {
            $notification          = array();
            $notification['code']  = 404;
            $notification['error'] = "unexpected exception";

            $this->logger->log('User "' . $userId . '" encountered an unknownen error while retrieving Ci "' . $ciId . '" ', Zend_Log::ERR);
            $notification['code']  = 500;
            $notification['error'] = $this->translator->translate('ciGetFailed');

            $this->getResponse()
                ->setHttpResponseCode($notification['code'])
                ->appendBody($notification['error']);
        } catch (Exception_Ci_CiIdInvalid $e) {
            $notification          = array();
            $notification['code']  = 404;
            $notification['error'] = "unexpected exception";

            $this->logger->log('User "' . $userId . '" failed to get Ci "' . $ciId . '". ID invalid!', Zend_Log::ERR);
            $notification['code']  = 400;
            $notification['error'] = $this->translator->translate('ciGetCiIdInvalid');

            $this->getResponse()
                ->setHttpResponseCode($notification['code'])
                ->appendBody($notification['error']);
        } catch (Exception_Ci_RetrieveNotFound $e) {
            $notification          = array();
            $notification['code']  = 404;
            $notification['error'] = "unexpected exception";

            $this->logger->log('User "' . $userId . '" failed to get Ci "' . $ciId . '" Ci not found', Zend_Log::ERR);
            $notification['code']  = 404;
            $notification['error'] = $this->translator->translate('ciGetCiIdNotFound');

            $this->getResponse()
                ->setHttpResponseCode($notification['code'])
                ->appendBody($notification['error']);
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::WARN);
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->appendBody("unexpected exception. please check your request and contact your administrator.");
        }
    }


    public function newAction()
    {
        $this->getResponse()
            ->setHttpResponseCode(201)
            ->appendBody("created the article\n");
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
        $ciId = $this->_getParam('id');

        try {
            $userInfo  = parent::getUserInformation();
            $themeId   = $userInfo[Db_User::THEME_ID];
            $userId    = $userInfo[Db_User::ID];
            $ciDelete  = $userInfo[Db_User::IS_CI_DELETE_ENABLED];
            $projectId = null;

            $userDto = new Dto_UserDto();
            $userDto->setId($userId);
            $userDto->setCiDelete($ciDelete);

            $ciService = new Service_Ci_Delete($this->translator, $this->logger, $themeId);
            $ciService->deleteCi($userDto, $ciId);

            $this->getResponse()
                ->setHttpResponseCode(200);
        } catch (Exception_AccessDenied $e) {
            parent::forbidden();
        } catch (Exception_Ci_DeleteFailed $e) {
            $notification = "unexpected exception";

            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" failed to delete Ci "' . $ciId . '".', Zend_Log::ERR);
            $notification = $this->translator->translate('ciDeleteFailed');

            $this->getResponse()
                ->setHttpResponseCode(404)
                ->appendBody($notification);
        } catch (Exception $e) {
            $this->getResponse()
                ->setHttpResponseCode(500)
                ->appendBody("unexpected exception. please check your request and contact your administrator.");
        }
    }

}