<?php
require_once 'AbstractAppAction.php';

/**
 */
class RelationController extends AbstractAppAction
{

    public function init()
    {
        parent::init();

        try {
            $this->translator->addTranslation($this->languagePath . '/de/ci_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/ci_en.csv', 'en');
            $this->translator->addTranslation($this->languagePath . '/de/relation_de.csv', 'de');
            $this->translator->addTranslation($this->languagePath . '/en/relation_en.csv', 'en');
            parent::addUserTranslation('relation');
            parent::setTranslatorLocal();
        } catch (Exception $e) {
            throw new Exception_Translation_LoadTranslationFileFailed($e);
        }
    }

    public function indexAction()
    {
        // use this action to display ALL relations.
        // may be very slow, so redirect to another page until this problem can
        // be fixed
        $this->_redirect('index/index');
    }

    public function detailAction()
    {
        $ciId             = $this->_getParam('sourceCiid');
        $ciRelationTypeId = $this->_getParam('ciRelationTypeId');

        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $breadCrumbs     = $ciServiceGet->getCiBreadcrumbs($ciId);
        $this->elementId = $this->view->ciBreadcrumb($breadCrumbs, 10, 'text', true);

        $viewConfig            = new Util_Config('view.ini', APPLICATION_ENV);
        $maxRelationsPerCiType = $viewConfig->getValue('relation.detail.maxRelationsPerCiType', 5);

        $serviceRelation = new Service_Relation_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $relationData    = $serviceRelation->getRelationDetail($ciId, $ciRelationTypeId, parent::getUserInformation()->getId(), parent::getUserInformation()->getThemeId(), $maxRelationsPerCiType);


        $ciTypeTexts      = array();
        $showCiBreadcrumb = array();
        foreach ($relationData['ciTypes'] as $ciType) {
            $ciTypeText = $ciType[Db_CiType::DESCRIPTION];
            if (isset($ciTypeTexts[$ciTypeText])) {
                $showCiBreadcrumb[$ciTypeTexts[$ciTypeText]] = true;
                $showCiBreadcrumb[$ciType[Db_CiType::ID]]    = true;
            }
            $ciTypeTexts[$ciTypeText] = $ciType[Db_CiType::ID];
        }

        //if ci relation has only one ci type redirect to ci index
        if (sizeof($relationData['relations'][$ciRelationTypeId]['citypes']) == 1) {
            reset($relationData['relations'][$ciRelationTypeId]['citypes']);
            $ciTypeId = key($relationData['relations'][$ciRelationTypeId]['citypes']);
            $this->_redirect('ci/index/typeid/' . $ciTypeId . '/ciRelationTypeId/' . $ciRelationTypeId . '/sourceCiid/' . $ciId);
        }

        $this->view->relations         = $relationData['relations'];
        $this->view->ciTypes           = $relationData['ciTypes'];
        $this->view->ciRelationTypes   = $relationData['ciRelationTypes'];
        $this->view->ciId              = $ciId;
        $this->view->ciDisplayValue    = $serviceRelation->getDefaultAttributeName($ciId);
        $this->view->showCiBreadcrumbs = $showCiBreadcrumb;
        $this->view->breadcrumbDepth   = $viewConfig->getValue('relation.detail.breadcrums.depth', 10);

    }

    public function deleteAction()
    {
        $ciRelationId = $this->_getParam('relationId');

        $notification = array();
        try {
            $relationServiceDelete = new Service_Relation_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $relationServiceDelete->deleteRelation(parent::getUserInformation(), $ciRelationId);
            $notification = array(
                'success' => $this->translator->translate('relationDeleteSuccess'),
            );
        } catch (Exception_Relation_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting Relation', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationDeleteFailed');
        } catch (Exception_Relation_DeleteFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to delete Relation', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationDeleteFailed');
        } catch (Exception_AccessDenied $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while deleting Relation', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationDeleteAccessDenied');
        }

        $this->_helper->FlashMessenger($notification);

        echo 'finished';
        exit;
    }

    public function getcolorAction()
    {
        $this->_helper->layout->setLayout('clean', false);

        $relationType = $this->_getParam('relationtype');

        $dao   = new Dao_CiRelation();
        $color = $dao->getColorByRelationType($relationType);

        if (!$color) {
            $color = "000000";
        }
        $this->view->color = $color;
    }

    public function createAction()
    {
        $ciId   = $this->_getParam('ciid');
        $create = $this->_getParam('create');
        $page   = $this->_getParam('page');
        $tab    = 0;

        $relationServiceCreate = new Service_Relation_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        try {
            $retGet = $relationServiceCreate->getCreateRelationForm(parent::getUserInformation(), parent::getCurrentProjectId(), $ciId);
        } catch (Exception_Relation_NoAssignableRelationsFound $e) {
            $this->logger->log($e, Zend_Log::ERR);
            $notification = array(
                'error' => $this->translator->translate('relationAddError4'),
            );
            $this->_helper->FlashMessenger($notification);
            $this->_redirect('relationtype/create/');
        }

        $form          = $retGet['form'];
        $attributeList = $retGet['attributeList'];
        $ciList        = $retGet['ciList'];

        if ($this->_request->isPost() || $create) {
            $tab      = 1;
            $formData = $this->_request->getPost();

            $formData          = $relationServiceCreate->getChosenCisForRelationCreate($formData, parent::getUserInformation()->getId());
            $this->view->ciids = $formData['ci_id'];

            if ($create) {
                $form->populate($formData);

                $this->view->ciAllList = $formData['ci_id'];
                $this->view->create    = true;

                $ret = $relationServiceCreate->createRelationCreatePage(parent::getUserInformation(), parent::getCurrentProjectId(), $ciId, $create,
                    $formData['ci_id']);
                $form->addElement($ret['attribute']);
                $this->view->newValueList = $ret['newValueList'];
                $this->view->form         = $form;
            } else {
                $tab = 1;
                $form->populate($formData);
                $values = $form->getValues();

                if ($form->isValid($formData))
                    $values['page'] = null;

                if ($formData['searchButton']) {
                    unset($values['session']);
                }

                $session = $this->_getParam('sess');
                if (!$values || !is_null($session)) {
                    $values['session'] = $session;
                }

                if ($values['relation'])
                    $parameter = array(
                        'relation' => $values['relation'],
                    );

                $searchClass  = new Util_Search_Db(parent::getUserInformation(), parent::getCurrentProjectId(), 'Relation', $parameter);
                $searchResult = $searchClass->search($values);

                $valueList                = $searchResult['items'];
                $this->view->searchstring = $searchResult['searchstring'];
                $this->view->paginator    = $searchResult['paginator'];
                $this->view->session      = $searchResult['session'];
                $this->view->page         = $searchResult['page'];
                $this->view->numberRows   = $searchResult['numberRows'];

                $this->view->valueList = $valueList;
            }
        } else {
            $searchstring = '*';

            $this->view->searchstring = $searchstring;
            $this->view->valueList    = null;

            $relationServiceCreate->destroyRelationSession(parent::getUserInformation()->getId());
        }

        $this->view->tab           = $tab;
        $this->view->color         = $this->_getParam('color');
        $this->view->ciToLink      = $ciId;
        $this->view->form          = $form;
        $this->view->attributeList = $attributeList;
        $this->view->ciList        = $ciList;
    }

    public function addrelationAction()
    {
        $ciId     = $this->_getParam('ciid');
        $formData = $this->_request->getPost();

        $notification = array();
        try {
            $relationServiceCreate = new Service_Relation_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $relationServiceCreate->addRelation(parent::getUserInformation(), $ciId, $formData);
            $notification = array(
                'success' => $this->translator->translate('relationCreateSuccess'),
            );
        } catch (Exception_Relation_CreateFailed $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to create Relation', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationCreateFailed');
        } catch (Exception_Relation_CreateFailedMaxRelationsExceededLinked $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to create Relation. Max Relations reached for linked ci',
                Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationAddError3');
        } catch (Exception_Relation_CreateFailedMaxRelationsExceededRoot $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" was unable to create Relation Max Relations reached for root ci',
                Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationAddError2');
        } catch (Exception_Relation_Unknown $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while creating Relation', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationCreateFailed');
        } catch (Exception_AccessDenied $e) {
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" is not allowed to create a Relation', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationCreateAccessDenied');
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            $this->logger->log('User "' . parent::getUserInformation()->getId() . '" encountered an unknown Exception while creating Relation', Zend_Log::ERR);
            $notification['error'] = $this->translator->translate('relationCreateFailed');
        }

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('relation/detail/sourceCiid/' . $ciId);
    }

    public function deleteassignmentAction()
    {
        $ciDelete = $this->_getParam('ciDelete');
        $ciId     = $this->_getParam('ciid');

        $relationServiceCreate = new Service_Relation_Create($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $ids                   = $relationServiceCreate->deleteAssignment(parent::getUserInformation()->getId(), $ciDelete);

        if ($ids && count($ids) > 0)
            $this->_redirect('relation/create/ciid/' . $ciId . '/create/1');
        else
            $this->_redirect('relation/create/ciid/' . $ciId);
    }

    public function visualizationAction()
    {
        $page = $this->_getParam('page');

        $relationServiceGet = new Service_Relation_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $paginator          = $relationServiceGet->getVisualizationList($page);

        $this->view->paginator = $paginator;
    }

    public function delvisAction()
    {
        $file = $this->_getParam('file');

        $file = str_replace(' ', '+', $file);

        try {
            $relationServiceDelete = new Service_Relation_Delete($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
            $ret                   = $relationServiceDelete->deleteVisualizationFile($file);

            if (!$ret) {
                throw Exception_Relation_DeleteFailed();
            }
            $notification['success'] = 'Löschen erfolgreich!';
        } catch (Exception $e) {
            $notification['error'] = 'Löschen fehlgeschlagen!';
        }
        // TODO implement me!

        $this->_helper->FlashMessenger($notification);
        $this->_redirect('relation/visualization');
    }

    /**
     * Javascript Visualization
     *
     * @throws Exception_InvalidParameter
     */
    public function visualizeAction()
    {
        $ciId = $this->_getParam('ciid');

        $ciId = str_replace(' ', '+', $ciId);

        if (!$ciId) {
            throw new Exception_InvalidParameter();
        }

        $ciServiceGet    = new Service_Ci_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());
        $breadCrumbs     = $ciServiceGet->getCiBreadcrumbs($ciId);
        $this->elementId = $this->view->ciBreadcrumb($breadCrumbs, 10, 'text', true);

        if ($this->_getParam('confirm') == "1") {
            $confirm = true;
        } else {
            $confirm = false;
        }

        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/visualization.ini', APPLICATION_ENV);

        $url    = $config->visualization->url;
        $width  = $config->visualization->width;
        $height = $config->visualization->height;
        $name   = $config->visualization->name;
        $code   = $config->visualization->code;
        $user   = parent::getUserInformation()->getId();

        if (!$width) {
            $width = 800;
        }
        if (!$height) {
            $height = 600;
        }

        $relationServiceGet = new Service_Relation_Get($this->translator, $this->logger, parent::getUserInformation()->getThemeId());

        $data = $relationServiceGet->getCiRelationForGraph($ciId, parent::getCurrentProjectId(), parent::getUserInformation()->getId(), 20, $confirm);

        // if the user did not confirm yet and there are more than the given
        // amount of Cis
        if ($data === true) {
            echo '<script type="text/javascript"> if(confirm("' . $this->translator->translate('tooBigVisualisation') . '")){ location.href="' . APPLICATION_URL .
                '/relation/visualize/ciid/' . $ciId . '/confirm/1";}else{location.href="' . APPLICATION_URL . 'ci/detail/ciid/' . $ciId . '"}</script>';
            exit();
        } elseif ($data === false) { // if too much Ci for server processing
            echo '<script type="text/javascript">alert("' . $this->translator->translate('tooMuchData') . '"); location.href="' . APPLICATION_URL .
                'ci/detail/ciid/' . $ciId . '";</script>';
            exit();
        }

        $ciArray       = $data['ci'];
        $relationArray = $data['relation'];

        $nodes = array();
        $edges = array();

        foreach ($ciArray as $ci) {
            $newNode = array(
                'id'    => $ci['id'],
                // __newLine__ gets replaced after json conversation
                'label' => '__newLine__' . 'CI#' . $ci['id'] . '__newLine__' . $ci['ci_type'],
            );

            if (!empty($ci['icon'])) {
                $image = APPLICATION_URL . '_uploads/icons/' . $ci[icon];
            } else { // standard images used if no image is defined
                $image = APPLICATION_URL . '/images/ci_graph.png';
            }

            if (!empty($ci['default_text'])) {
                $newNode['title'] = $ci['default_text'];
            }

            $newNode['shape'] = 'image';
            $newNode['image'] = $image;
            $newNode['value'] = 1; // needed for picture scaling to work

            array_push($nodes, $newNode);
        }

        // var_dump($nodes);
        // die();

        foreach ($relationArray as $relation) {
            // length of the line is dependent to the length of the label
            $lineLength = (strlen($relation['ci_relation_type1']) + strlen($relation['ci_relation_type2'])) * 8;

            // each edge gets the same label, length
            $newEdge = array(
                'label'  => $relation['ci_relation_type1'] . ' /__newLine__' . $relation['ci_relation_type2'],
                'length' => $lineLength,
            );

            // for different direction types
            switch ($relation['direction']) {
                case 1: // from id1 to id2
                    $newEdge['from']  = $relation['ci_id_1'];
                    $newEdge['to']    = $relation['ci_id_2'];
                    $newEdge['style'] = 'arrow';
                    break;
                case 2: // from id2 to id1
                    $newEdge['to']    = $relation['ci_id_2'];
                    $newEdge['from']  = $relation['ci_id_1'];
                    $newEdge['style'] = 'arrow';
                    break;
                case 3: // bidirected between id1 and id2
                    $newEdge['from']  = $relation['ci_id_1'];
                    $newEdge['to']    = $relation['ci_id_2'];
                    $newEdge['style'] = 'arrow';
                    array_push($edges,
                        array(
                            'from'   => $relation['ci_id_2'],
                            'to'     => $relation['ci_id_1'],
                            'style'  => 'arrow',
                            'length' => $lineLength,
                        ));
                    break;
                default: // for 4 and null values undirected is used
                    $newEdge['from'] = $relation['ci_id_1'];
                    $newEdge['to']   = $relation['ci_id_2'];
                    break;
            }
            array_push($edges, $newEdge);
        }

        $this->view->ciId   = $ciId;
        $this->view->url    = $url;
        $this->view->width  = $width;
        $this->view->height = $height;
        $this->view->name   = $name;
        $this->view->code   = $code;
        $this->view->user   = $user;
        $this->view->nodes  = str_replace('__newLine__', '\\n', json_encode($nodes));
        $this->view->edges  = str_replace('__newLine__', '\\n', json_encode($edges));
    }
}