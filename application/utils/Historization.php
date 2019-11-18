<?php

/**
 * This class handles the historization of ci's, ci_attributes, ci_relations and ci_projects
 *
 * Do not use it to retrieve history entries.
 *
 *
 */
class Util_Historization
{


    const MESSAGE_CI_INSERT      = "ci created";
    const MESSAGE_CI_DUPLICATE   = "ci duplicated";
    const MESSAGE_CI_SINGLE_EDIT = "ci single edit";
    const MESSAGE_CI_UPDATE      = "ci updated";
    const MESSAGE_CI_DELETE      = "ci deleted";

    const MESSAGE_CI_SCRIPT_UPDATE = "script ci updated";

    const MESSAGE_CI_ATTRIBUTE_RESOTRE = "ci attribute restore";
    const MESSAGE_CI_RELATION_RESTORE  = "ci relation restore";

    const MESSAGE_CITYPE_CHANGE = 'ci type change';

    const MESSAGE_RELATION_INSERT = "relation created";
    const MESSAGE_RELATION_DELETE = "relation deleted";

    const MESSAGE_PROJECT_INSERT = "project added";
    const MESSAGE_PROJECT_DELETE = "project removed";

    const MESSAGE_IMPORT_VALIDATION_MATCH  = 'validation match';
    const MESSAGE_IMPORT_VALIDATION_INSERT = 'validation ci created';

    const MESSAGE_IMPORT_INSERT = 'import ci created';
    const MESSAGE_IMPORT_UPDATE = 'import ci updated';

    const MESSAGE_FAQ_IMPORT = 'faq import';

    const MESSAGE_IMPORT_MAIL = 'mail import';

    private $historyDaoImpl = null;

    /**
     * initializes the class
     */
    function __construct()
    {
        $this->historyDaoImpl = new Dao_History();
    }


    public function createHistory($userId, $message)
    {
        return $this->historyDaoImpl->createHistory($userId, $message);
    }


    /**
     * creates a history entry without paramenter and returns the history id.
     */
    public function prepareCiHistoryEntry($ciId, $userId, $message = null)
    {
        if (!$message)
            $message = self::MESSAGE_CI_UPDATE;

        $historyId = $this->historyDaoImpl->createHistory($userId, $message);
        return $historyId;
    }

    /**
     * call this method if a new CI is created
     */
    public function handleCiInsert($ciId, $ciType, $userId, $message = null)
    {
        if (!$message)
            $message = self::MESSAGE_CI_INSERT;

        $historyId = $this->historyDaoImpl->createHistory($userId, $message);

        $this->historyDaoImpl->updateCiAttributeHistoryId($ciId, $historyId);
        $this->historyDaoImpl->updateCiProjectHistoryId($ciId, $historyId);

        $this->historyDaoImpl->updateCiHistoryId($ciId, $historyId);
    }


    /**
     * call this method if a CI is duplicated
     */
    public function handleCiDuplicate($ciId, $ciType, $userId)
    {
        $this->handleCiInsert($ciId, $ciType, $userId, self::MESSAGE_CI_DUPLICATE);
    }


    /**
     * call this method if an existing ci is updated
     */
    public function handleCiUpdate()
    {
        // TODO: needed??
    }


    /**
     * call this method if a single property of an existing ci is updated.
     * used for single-edit actions
     */
    public function handleCiSingleUpdate($ciId, $userId, $ciAttributeId, $historyId = null)
    {
        $message = self::MESSAGE_CI_SINGLE_EDIT;

        if (!$historyId) {
            $ciDaoImpl = new Dao_Ci();
            $ci        = $ciDaoImpl->getCi($ciId);
            $historyId = $this->historyDaoImpl->createHistory($userId, $message);
        }

        $this->historyDaoImpl->updateSingleCiAttributeHistoryId($ciId, $ciAttributeId, $historyId);
    }


    /**
     *
     */
    public function handleCiSingleUpdateDelete($ciId, $userId, $ciAttributeId, $historyId = null)
    {
        $message = self::MESSAGE_CI_SINGLE_EDIT;

        if (!$historyId) {
            $historyId = $this->historyDaoImpl->createHistory($userId, $message);
        }

        $this->historyDaoImpl->updateSingleCiAttributeHistoryIdDelete($ciAttributeId, $historyId);
        return $historyId;
    }


    /**
     * call this method if an existing ci is deleted
     */
    public function handleCiDelete($ciId, $userId, $message = null)
    {
        if (!$message)
            $message = self::MESSAGE_CI_DELETE;

        $ciDaoImpl = new Dao_Ci();
        $ciDaoImpl->procedureDeleteCi($ciId, $userId, $message);
    }


    public function handleCiTypeChangeDeleteCi($ciId, $userId)
    {
        $historyId = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_CITYPE_CHANGE);
        $this->historyDaoImpl->updateCiHistoryIdDelete($ciId, $historyId);
    }

    /**
     * call this method if the ci type of an existing ci is changed
     */
    public function handleCiTypeChange($ciId, $userId)
    {
        $historyId = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_CITYPE_CHANGE);

        $this->historyDaoImpl->updateCiHistoryId($ciId, $historyId);
    }


    public function handleCiRelationInsert($ciId, $userId, $linkedArray = array())
    {
        $ciDaoImpl   = new Dao_Ci();
        $ci          = $ciDaoImpl->getCi($ciId);
        $historyId_1 = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_RELATION_INSERT);

        if ($linkedArray)
            foreach ($linkedArray as $linked) {
                $ci_id2     = $linked['ci_id'];
                $relationId = $linked['relation_id'];

                // create second history entry
                $ci2         = $ciDaoImpl->getCi($ci_id2);
                $historyId_2 = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_RELATION_INSERT);

                // update ci_relation table
                $this->historyDaoImpl->updateCiRelationHistoryId($ciId, $ci_id2, $historyId_1, $historyId_2);
            }
    }


    public function handleCiRelationDelete($ciId, $ciId2, $userId, $relationId)
    {
        $ciDaoImpl = new Dao_Ci();
        $ci        = $ciDaoImpl->getCi($ciId);
        $ci2       = $ciDaoImpl->getCi($ciId2);

        $historyId_1 = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_RELATION_DELETE);
        $historyId_2 = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_RELATION_DELETE);

        $this->historyDaoImpl->updateCiRelationHistoryIdDelete($relationId, $historyId_1, $historyId_2);
    }



    // handle ci_project

    /**
     * call this method if a new project is assigned
     */
    public function handleCiProjectInsert($ciId, $projectId, $userId, $historyId = null)
    {
        if (!$historyId) {
            $ciDaoImpl = new Dao_Ci();
            $ci        = $ciDaoImpl->getCi($ciId);
            $historyId = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_PROJECT_INSERT);
        }

        $this->historyDaoImpl->updateSingleCiProjectHistoryId($ciId, $projectId, $historyId);
    }


    /**
     * call this method if a project is unassigned
     */
    public function handleCiProjectDelete($ciId, $projectId, $userId, $historyId = null)
    {
        if (!$historyId) {
            $ciDaoImpl = new Dao_Ci();
            $ci        = $ciDaoImpl->getCi($ciId);
            $historyId = $this->historyDaoImpl->createHistory($userId, self::MESSAGE_PROJECT_DELETE);
        }

        $this->historyDaoImpl->updateSingleCiProjectHistoryIdDelete($ciId, $projectId, $historyId);
    }


    public function restoreCi($ciId, $userId, $datestamp = null)
    {
        $historyId = $this->historyDaoImpl->createHistory($userId, 'restore ci');
        $this->historyDaoImpl->restoreCi($ciId, $datestamp, $historyId);
    }
}