<?php

/**
 *
 *
 *
 */
class Service_Relation_Create extends Service_Abstract
{


    private static $relationNamespace = 'RelationController';

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2002, $themeId);
    }


    public function getCreateRelationForm($userDto, $projectId, $ciId)
    {
        $ciTypeDao = new Dao_CiType();
        $ciTypeDto = $ciTypeDao->getCiTypeByCiId($ciId);

        $ciDao        = new Dao_Ci();
        $attributeDao = new Dao_Attribute();

        $attributeList = $attributeDao->getAttributesByTypeId($ciTypeDto[Db_CiType::ID], $userDto->getThemeId());
        $ciList        = $ciDao->getCiConfigurationStatementForRelation($ciId, $attributeList, $projectId);

        $relationDaoImpl = new Dao_CiRelation();
        $rels            = $relationDaoImpl->getRelationTypesByCiTypeId($ciTypeDto[Db_CiType::ID]);
        if (!$rels) {
            throw new Exception_Relation_NoAssignableRelationsFound();
        }
        $relationList = array();
        foreach ($rels as $rel) {
            $relationList[$rel[Db_CiRelationType::ID]] = (!$rel[Db_CiRelationType::DESCRIPTION_OPTIONAL]) ? $rel[Db_CiRelationType::DESCRIPTION] : $rel[Db_CiRelationType::DESCRIPTION] . " <> " . $rel[Db_CiRelationType::DESCRIPTION_OPTIONAL];
        }

        $directions    = $relationDaoImpl->getDirections();
        $directionList = array();
        foreach ($directions as $dir) {
            $directionList[$dir[Db_CiRelationDirection::ID]] = $dir[Db_CiRelationDirection::DESCRIPTION];
        }

        $attributes      = $attributeDao->getAttributeListForFormSelectByCiId($ciId, $userDto->getId());
        $attributeList_1 = array();
        foreach ($attributes as $attribute) {
            $attributeList_1[$attribute['id']] = $attribute['description'];
        }

        $form = $this->generateSearchForm($ciId, $relationList, $directionList, $attributeList_1);
        return array(
            'form'          => $form,
            'attributeList' => $attributeList,
            'ciList'        => $ciList,
            'attributeList' => $attributeList,
        );
    }


    public function getChosenCisForRelationCreate($formData, $userId)
    {
        $chosenItems = $this->addCisToSession($userId, $formData['ci_id']);

        foreach ($chosenItems as $item) {
            $formData['ci_id'][$item] = $item;
        }

        return $formData;
    }


    public function createRelationCreatePage($userDto, $projectId, $ciId, $create, $values)
    {
        try {
            if (!$values)
                $values = array();
            $attributeDao = new Dao_Attribute();
            $ciDao        = new Dao_Ci();

            foreach ($values as $id) {
                $al = $attributeDao->getAttributeListForFormSelectByCiId($id, $userDto->getId());
                foreach ($al as $atr) {
                    $attributesListTmp[$atr['id']] = $atr['description'];
                }
                $attributesLists[] = $attributesListTmp;
            }

            if ($attributesLists[0])
                foreach ($attributesLists[0] as $key => $value) {
                    foreach ($attributesLists as $attributesList) {
                        if (!in_array($key, array_keys($attributesList)))
                            unset($attributesLists[0][$key]);
                    }
                }
            $possibleAttributesList = $attributesLists[0];

            foreach ($values as $val) {
                $ciAllList[$val] = $ciDao->getCiCiType($val);
            }

            $currentCi = null;
            foreach ($ciAllList as $ci) {
                if (!$currentCi) {
                    $currentCi['citype_id'] = $ci['citype_id'];
                } else if ($currentCi['citype_id'] != $ci['citype_id']) {
                    // create new ciType
                    $listOfCiTypes[]          = $currentCi;
                    $currentCi['citype_id']   = $ci['citype_id'];
                    $currentCi['citype_name'] = $ci['citype_name'];
                }
                $currentCi[] = $ci;
            }
            $listOfCiTypes[] = $currentCi;

            foreach ($listOfCiTypes as $ciTypeList) {

                // make joined to ci_type_attribute table
                $attributeList = $attributeDao->getAttributesByTypeId($ciTypeList['citype_id'], $userDto->getThemeId(), $userDto->getId());

                foreach ($attributeList as $attribute) {
                    $attributesToUse[] = $attribute[Db_Attribute::ID];
                }

                // retrieve all ci's of the given type and project id(optional)
                $searchDaoImpl = new Dao_Search();


                $ciList            = null;
                $currentCiTypeName = null;
                // select attributes for each ci
                $i = 0;
                while ($ciTypeList[$i]) {

                    if (!$currentCiTypeName) {
                        $currentCiTypeName = $ciTypeList[$i]['citype_name'];
                    }

                    $ciList[] = $ciTypeList[$i]['ci_id'];
                    $i++;
                }

                $newCiList = $searchDaoImpl->getAtrributeValuesForCi(implode(',', $ciList), $ciTypeList['citype_id'], $attributeList);

                $temp                = array();
                $temp['citype_name'] = $currentCiTypeName;
                $temp['attribList']  = $attributeList;
                $temp['ciList']      = $newCiList;

                $newValueList[$ciTypeList['citype_id']] = $temp;
            }

            if (!$possibleAttributesList)
                $possibleAttributesList = array();

            $attributes2 = new Zend_Form_Element_Select('attributes2');
            $attributes2->addMultiOptions(array(0 => 'bitte wählen'));
            $attributes2->addMultiOptions($possibleAttributesList);
            $attributes2->setLabel('attribute');
            $attributes2->setAttrib('class', 'relation_attribute_select');
            if (!$possibleAttributesList || count($possibleAttributesList) == 0) {
                $attributes2->setAttrib('disabled', true);
            }

            return array(
                'attribute'    => $attributes2,
                'newValueList' => $newValueList,
            );
        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::CRIT);
            throw new Exception_Relation($e);
        }
    }


    public function destroyRelationSession($userId)
    {
        $var        = $userId . 'RelationItems';
        $sess       = new Zend_Session_Namespace(self::$relationNamespace);
        $sess->$var = null;
    }


    /**
     * persist a new relation to BD
     */
    public function addRelation($userDto, $ciId, $formData)
    {
        $switchDirection = $formData['switch'];
        $relationTypeId  = $formData['relation'];
        $directionId     = $formData['direction'];
        $note            = $formData['note'];
        $color           = $formData['color'];
        $weight          = $formData['weight'];

        $chosenItems       = $this->addCisToSession($userDto->getId(), $formData['ci_id']);
        $ciRelationDaoImpl = new Dao_CiRelation();

        $linkedArray = array();
        if ($userDto->getRelationEdit()) {
            $historizationUtil = new Util_Historization();
            $historyId         = $historizationUtil->createHistory($userDto->getId(), Util_Historization::MESSAGE_RELATION_INSERT);

            foreach ($chosenItems as $linkedCiId) {
                $data = array();
                /*
                 * ATTENTION! If "change direction" is checked in
                 * assign-relation-form, linkedAttributeId and attributeId
                 * have to be changed as well as linkedCiId and CiId --
                 * additionally the directionId has to be switched, if it's a
                 * directed relation (according to the switch of IDs)
                 */
                if ($switchDirection) {
                    $data[Db_CiRelation::CI_ID_1]             = $linkedCiId;
                    $data[Db_CiRelation::CI_ID_2]             = $ciId;
                    $data[Db_CiRelation::CI_RELATION_TYPE_ID] = $relationTypeId;
                    $data[Db_CiRelation::ATTRIBUTE_ID]        = $formData['attributes2'];
                    $data[Db_CiRelation::LINKED_ATTRIBUTE_ID] = $formData['attributes1'];

                    if ($directionId == Db_CiRelationDirection::abDirected()) {
                        $data[Db_CiRelation::DIRECTION] = Db_CiRelationDirection::baDirected();
                    } elseif ($directionId == Db_CiRelationDirection::baDirected()) {
                        $data[Db_CiRelation::DIRECTION] = Db_CiRelationDirection::abDirected();
                    } else {
                        $data[Db_CiRelation::DIRECTION] = $directionId;
                    }
                } else {
                    $data[Db_CiRelation::CI_ID_1]             = $ciId;
                    $data[Db_CiRelation::CI_ID_2]             = $linkedCiId;
                    $data[Db_CiRelation::CI_RELATION_TYPE_ID] = $relationTypeId;
                    $data[Db_CiRelation::DIRECTION]           = $directionId;
                    $data[Db_CiRelation::ATTRIBUTE_ID]        = $formData['attributes1'];
                    $data[Db_CiRelation::LINKED_ATTRIBUTE_ID] = $formData['attributes2'];
                }

                if ($note)
                    $data[Db_CiRelation::NOTE] = $note;
                if ($color)
                    $data[Db_CiRelation::COLOR] = $color;
                $data[Db_CiRelation::WEIGHTING] = $weight;

                $data[Db_CiRelation::HISTORY_ID] = $historyId;

                // check if relation can be established! (limit, etc)
                $ci1Max = $ciRelationDaoImpl->getAmountOfRelationsForCi($ciId, $relationTypeId);
                if ($ci1Max && $ci1Max['max_amount'] && ($ci1Max['cnt'] >= $ci1Max['max_amount'])) {
                    throw new Exception_Relation_CreateFailedMaxRelationsExceededRoot();
                }

                $ci1Max = $ciRelationDaoImpl->getAmountOfRelationsForCi($linkedCiId, $relationTypeId);
                if ($ci1Max && $ci1Max['max_amount'] && ($ci1Max['cnt'] >= $ci1Max['max_amount'])) {
                    throw new Exception_Relation_CreateFailedMaxRelationsExceededLinked();
                }

                $relationId = $ciRelationDaoImpl->addCiRelationArray($data);

                array_push($linkedArray, array('ci_id' => $linkedCiId, 'relation_id' => $relationId));

                // customization handling
                $triggerUtil = new Util_Trigger($this->logger);
                $triggerUtil->createRelation($relationId, $userDto->getId());
            }

            if (!$relationId) {
                throw new Exception_Relation_CreateFailed();
            }
        } else {
            throw new Exception_AccessDenied();
        }
    }


    public function deleteAssignment($userId, $ciDelete)
    {
        $sess = new Zend_Session_Namespace(self::$relationNamespace);
        $var  = $userId . 'RelationItems';
        $ids  = $sess->$var;
        unset($ids[$ciDelete]);
        $sess->$var = $ids;

        return $ids;
    }


    private function addCisToSession($userId, $values)
    {
        $var      = $userId . 'RelationItems';
        $sess     = new Zend_Session_Namespace(self::$relationNamespace);
        $oldArray = $sess->$var;

        if (!$oldArray)
            $oldArray = array();

        $newArray = ($values && is_array($values)) ? array_merge($values, $oldArray) : $oldArray;

        //print_r($newArray); exit;
        $ar = array();
        foreach ($newArray as $array) {
            $ar[$array] = $array;
        }
        $sess->$var = $ar;

        return $ar;
    }

    private function generateSearchForm($ciId, $relationTypeList, $directionList, $attributeList_1 = array())
    {
        return new Form_Relation_Search($this->translator, $ciId, $relationTypeList, $directionList, $attributeList_1);
    }
}