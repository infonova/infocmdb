<?php


class Zend_View_Helper_RelationDirection extends Zend_View_Helper_Abstract
{

    protected $ciRelationDirections = array();

    public function __construct()
    {
        $daoCiRelation           = new Dao_CiRelation();
        $ciRelationDirectionList = $daoCiRelation->getDirections();
        $ciRelationDirections    = array();
        foreach ($ciRelationDirectionList as $ciRelationDirection) {
            $ciRelationDirections[$ciRelationDirection[Db_CiRelationDirection::ID]] = $ciRelationDirection;
        }
        $this->ciRelationDirections = $ciRelationDirections;
    }

    public function relationDirection($ciRelationData, $ciRelationType)
    {
        $translator = $this->view->translate();

        $relationDescription = $ciRelationType[Db_CiRelationType::DESCRIPTION];

        if ($ciRelationData['foreign_column'] === 1) {
            if ($ciRelationType[Db_CiRelationType::DESCRIPTION_OPTIONAL] !== '' && !is_null($ciRelationType[Db_CiRelationType::DESCRIPTION_OPTIONAL])) {
                $relationDescription = $ciRelationType[Db_CiRelationType::DESCRIPTION_OPTIONAL];
            }
        }

        $relationDescriptionHoverText = '';

        if (isset($ciRelationData['ciRelationValidFrom'])) {
            $relationDescriptionHoverText .= $ciRelationData['ciRelationValidFrom'];

            if (isset($ciRelationData['ciRelationNote'])) {
                $relationDescriptionHoverText .= ' - ';
            }
        }

        if (isset($ciRelationData['ciRelationNote'])) {
            $relationDescriptionHoverText .= $ciRelationData['ciRelationNote'];
        }

        $returnString = '<span alt="' . $relationDescriptionHoverText . '" title="' . $relationDescriptionHoverText . '">' . $relationDescription . '</span>';

        return $returnString;
    }

    /**
     * Lazily fetches Helper Instance.
     *
     * @return Zend_Controller_Action_Helper_FlashMessenger
     */
    public function _getRelationDirection()
    {
        if (null === $this->_relationdirection) {
            $this->_relationdirection =
                Zend_Controller_Action_HelperBroker::getStaticHelper(
                    'RelationDirection');
        }
        return $this->_relationdirection;
    }

}