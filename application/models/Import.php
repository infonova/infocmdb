<?php

class Dao_Import extends Dao_Abstract
{

    public function createCi($ciTypeId)
    {
        $ci = new Db_Ci();

        $data                    = array();
        $data[Db_Ci::CI_TYPE_ID] = $ciTypeId;

        return $ci->insert($data);
    }

    public function createCiProject($ciId, $projectId)
    {
        $ci = new Db_CiProject();

        $data                           = array();
        $data[Db_CiProject::CI_ID]      = $ciId;
        $data[Db_CiProject::PROJECT_ID] = $projectId;

        return $ci->insert($data);
    }


    public function checkCiTypeExistance($ciType)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::ID))
            ->where(Db_CiType::ID . ' =?', $ciType);

        return $this->db->fetchRow($select);
    }

    public function checkCiExistence($ciid)
    {
        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, array(Db_Ci::ID))
            ->where(Db_Ci::ID . ' =?', $ciid);

        return $this->db->fetchRow($select);
    }

    public function getCiIdByAttributeValue($value, $attributeId)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' =?', $attributeId)
            ->where('(' . Db_CiAttribute::VALUE_TEXT . ' =? or ' . Db_CiAttribute::VALUE_DEFAULT . ' =? or ' . Db_CiAttribute::VALUE_DATE . ' =? or ' . Db_CiAttribute::VALUE_CI . ' =?)', $value);

        return $this->db->fetchRow($select);
    }

    public function getCiAttributesByCiId($ciid)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->join(Db_Attribute::TABLE_NAME, Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID, array())
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('type' => Db_AttributeType::NAME))
            ->where(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' =?', $ciid);

        return $this->db->fetchAll($select);
    }

    public function getCiType($id)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME);

        if (is_numeric($id)) {
            $select->where(Db_CiType::ID . ' =?', $id);
        } else {
            // is name
            $select->where(Db_CiType::NAME . ' =?', $id);
        }

        return $this->db->fetchRow($select);
    }

    public function getProject($id)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME);

        if (is_numeric($id)) {
            $select->where(Db_Project::ID . ' =?', $id);
        } else {
            // is name
            $select->where(Db_Project::NAME . ' =?', $id);
        }

        return $this->db->fetchRow($select);
    }

    public function getRelationType($name)
    {
        $select = $this->db->select()
            ->from(Db_CiRelationType::TABLE_NAME)
            ->where(Db_CiRelationType::NAME . ' =?', $name);

        return $this->db->fetchRow($select);
    }

    public function updateCiAttribute($ciAttributeId, $data)
    {
        $table = new Db_CiAttribute();

        $where = $table->getAdapter()->quoteInto(Db_CiAttribute::ID . ' = ?', $ciAttributeId);
        return $table->update($data, $where);
    }

    public function getCiTypeIdByName($ciType)
    {
        $select = $this->db->select()
            ->from(Db_CiType::TABLE_NAME, array(Db_CiType::ID))
            ->where(Db_CiType::NAME . ' =?', $ciType);

        return $this->db->fetchRow($select);
    }


    public function checkProjectExistance($project)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME, array(Db_Project::ID))
            ->where(Db_Project::ID . ' =?', $project);

        return $this->db->fetchRow($select);
    }

    public function getProjectIdByName($project)
    {
        $select = $this->db->select()
            ->from(Db_Project::TABLE_NAME, array(Db_Project::ID))
            ->where(Db_Project::NAME . ' =?', $project);

        return $this->db->fetchRow($select);
    }


    public function checkAttributeExistance($attribute, $allowInactive)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::ID))
            ->where(Db_Attribute::ID . ' =?', $attribute);

        if (!$allowInactive) {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' =?', '1');
        }

        return $this->db->fetchRow($select);
    }

    public function getAttributeIdByName($attribute, $allowInactive)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME, array(Db_Attribute::ID, Db_Attribute::IS_UNIQUE))
            ->join(Db_AttributeType::TABLE_NAME, Db_AttributeType::TABLE_NAME . '.' . Db_AttributeType::ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ATTRIBUTE_TYPE_ID, array('type' => Db_AttributeType::NAME));

        if (is_numeric($attribute)) {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID . ' =?', $attribute);
        } else {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME . ' =?', $attribute);
        }

        if (!$allowInactive) {
            $select->where(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::IS_ACTIVE . ' =?', '1');
        }

        return $this->db->fetchRow($select);
    }


    public function insertCiAttribute(&$data)
    {

        $table = new Db_CiAttribute();
        return $table->insert($data);


    }


    public function insertValidation($validation)
    {
        $table = new Db_ImportFileValidation();
        return $table->insert($validation);
    }

    public function insertValidationAttribute($validation)
    {
        $table = new Db_ImportFileValidationAttributes();
        return $table->insert($validation);
    }

    public function insertCi($data)
    {
        $table = new Db_Ci();
        return $table->insert($data);
    }


    public function insertCiProject($data)
    {
        $table = new Db_CiProject();
        return $table->insert($data);
    }

    public function getDefaultValueIdByName($attributeId, $value)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultValues::TABLE_NAME)
            ->where(Db_AttributeDefaultValues::ATTRIBUTE_ID . ' =?', $attributeId)
            ->where('TRIM(' . Db_AttributeDefaultValues::VALUE . ') LIKE ?', trim($value));
        return $this->db->fetchRow($select);
    }

    public function getDefaultValueNameById($defaultvalue_id)
    {
        $select = $this->db->select()
            ->from(Db_AttributeDefaultValues::TABLE_NAME)
            ->where(Db_AttributeDefaultValues::ID . ' = ?', $defaultvalue_id);
        return $this->db->fetchRow($select);
    }


    public function getImportConfigForPagination($orderBy, $direction)
    {
        $select = $this->db->select()
            ->from(Db_FileImport::TABLE_NAME);

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        }

        return $select;
    }

    public function getImportConfig()
    {
        $select = $this->db->select()
            ->from(Db_FileImport::TABLE_NAME);

        return $this->db->fetchAll($select);
    }


    public function getImportFileHistoryForPagination($queueList, $status)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME)
            ->where(Db_ImportFileHistory::STATUS . ' =?', $status);

        $where = null;
        $first = true;
        foreach ($queueList as $queue) {
            if ($first) {
                $where = '(' . Db_ImportFileHistory::QUEUE . ' = "' . $queue . '" ';
                $first = false;
            } else {
                $where .= ' OR ' . Db_ImportFileHistory::QUEUE . ' = "' . $queue . '" ';
            }
        }
        if ($where)
            $select->where($where . ')');
        $select->order(Db_ImportFileHistory::CREATED . ' ASC');
        return $select;
    }


    public function historizeFileImport($data)
    {
        $table = new Db_ImportFileHistory();
        return $table->insert($data);
    }

    public function getFileHistory($id)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME)
            ->where(Db_ImportFileHistory::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getCountDetailFileImportHistory($id)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistoryDetail::TABLE_NAME, array('cnt' => 'COUNT(distinct line)'))
            ->where(Db_ImportFileHistoryDetail::IMPORT_FILE_HISTORY_ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getCountDetailFileImportHistoryAll($id)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistoryDetail::TABLE_NAME, array('cnt' => 'COUNT(id)'))
            ->where(Db_ImportFileHistoryDetail::IMPORT_FILE_HISTORY_ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function updateHistory($historyId, $data)
    {
        $table = new Db_ImportFileHistory();
        $where = $this->db->quoteInto(Db_ImportFileHistory::ID . ' =?', $historyId);
        return $table->update($data, $where);
    }

    public function finalizeFileImportHistory($historyId, $status)
    {
        $data                               = array();
        $data[Db_ImportFileHistory::STATUS] = $status;

        $table = new Db_ImportFileHistory();
        $where = $this->db->quoteInto(Db_ImportFileHistory::ID . ' =?', $historyId);
        return $table->update($data, $where);
    }

    public function createValidation($filename)
    {
        $data                                  = array();
        $data[Db_ImportFileValidation::NAME]   = $filename;
        $data[Db_ImportFileValidation::STATUS] = 'in_progress';

        $table = new Db_ImportFileValidation();
        return $table->insert($data);
    }


    public function deleteImportHistory($id)
    {
        $table = new Db_ImportFileHistory();
        $where = $this->db->quoteInto(Db_ImportFileHistory::ID . ' =?', $id);
        $table->delete($where);
    }


    public function getSingleImportHistory($id)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME)
            ->where(Db_ImportFileHistory::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }

    public function searchActiveHistoryEntry($filename, $queue)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME)
            ->where(Db_ImportFileHistory::FILENAME . ' =?', $filename)
            ->where(Db_ImportFileHistory::QUEUE . ' =?', $queue)
            ->where(Db_ImportFileHistory::STATUS . ' =?', 'in_progress');

        return $this->db->fetchRow($select);
    }

    public function insertImportFileErrorHistory($data)
    {
        $table = new Db_ImportFileHistoryDetail();
        return $table->insert($data);
    }

    public function getFilenameByHistoryId($historyId)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME, array(Db_ImportFileHistory::FILENAME))
            ->where(Db_ImportFileHistory::ID . ' =?', $historyId);
        return $this->db->fetchRow($select);

    }

    public function getImportFileHistoryForIdSearch($filename, $validation, $queue, $status)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME)
            ->where(Db_ImportFileHistory::FILENAME . ' =?', $filename)
            ->where(Db_ImportFileHistory::VALIDATION . ' =?', $validation)
            ->where(Db_ImportFileHistory::QUEUE . ' =?', $queue)
            ->where(Db_ImportFileHistory::STATUS . ' =?', $status);
        return $this->db->fetchRow($select);
    }

    public function getDetailFileImportHistory($id)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistoryDetail::TABLE_NAME)
            ->where(Db_ImportFileHistoryDetail::IMPORT_FILE_HISTORY_ID . ' =?', $id);
        return $this->db->fetchAll($select);
    }

    public function getCountImportFileHistoryByUserId($userId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_ImportFileHistory::TABLE_NAME . "
				   WHERE " . Db_ImportFileHistory::USER_ID . " = '" . $userId . "'
				   ";

        return $this->db->fetchRow($select);
    }

    public function getErrorLinesHistory($history_id)
    {

        $select = "SELECT DISTINCT " . Db_ImportFileHistoryDetail::LINE . "
				   FROM " . Db_ImportFileHistoryDetail::TABLE_NAME . "
				   WHERE " . Db_ImportFileHistoryDetail::IMPORT_FILE_HISTORY_ID . " = '" . $history_id . "'
				   ";

        return $this->db->fetchAll($select);


    }

    /**
     * get header line errors for history_id
     *
     * @param type $history_id
     */
    public function getHeaderErrorHistory($history_id)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistoryDetail::TABLE_NAME)
            ->where(Db_ImportFileHistoryDetail::IMPORT_FILE_HISTORY_ID . ' =?', $history_id)
            ->where(Db_ImportFileHistoryDetail::LINE . ' =?', 1);
        return $this->db->fetchAll($select);
    }


    public function getFileHistoryForPagination($page)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME)
            ->order(Db_ImportFileHistory::CREATED . ' DESC');

        return $select;
    }

    public function deleteImportFileHistoryById($Id)
    {
        $table      = new Db_ImportFileHistory();
        $where      = array(Db_ImportFileHistory::ID . ' = ?' => $Id,
        );
        $statusCode = 0;
        try {
            $table->delete($where);
            $statusCode = 1;
        } catch (Exception $e) {
            $statusCode = 0;
        }
        return $statusCode;
    }


    public function deleteRelation($typeId, $ciId1, $ciId2)
    {
        $table = new Db_CiRelation();
        $where = array(Db_CiRelation::CI_RELATION_TYPE_ID . ' = ?' => $typeId,
                       Db_CiRelation::CI_ID_1 . ' = ?'             => $ciId1,
                       Db_CiRelation::CI_ID_2 . ' = ?'             => $ciId2,
        );

        $where2 = array(Db_CiRelation::CI_RELATION_TYPE_ID . ' = ?' => $typeId,
                        Db_CiRelation::CI_ID_2 . ' = ?'             => $ciId1,
                        Db_CiRelation::CI_ID_1 . ' = ?'             => $ciId2,
        );

        $statusCode = 0;
        try {
            $table->delete($where);
            $table->delete($where2);
            $statusCode = 1;
        } catch (Exception $e) {
            $statusCode = 0;
        }

        return $statusCode;
    }


    public function createRelation($relationTypeId, $ciId1, $ciId2, $note = null)
    {
        $data                                     = array();
        $data[Db_CiRelation::CI_RELATION_TYPE_ID] = $relationTypeId;
        $data[Db_CiRelation::CI_ID_1]             = $ciId1;
        $data[Db_CiRelation::CI_ID_2]             = $ciId2;

        if ($note)
            $data[Db_CiRelation::NOTE] = $note;

        $data[Db_CiRelation::WEIGHTING] = '5';
        $data[Db_CiRelation::DIRECTION] = '4';


        $table = new Db_CiRelation();
        return $table->insert($data);
    }


    public function getRelationByParams($relationTypeId, $ciId1, $ciId2)
    {
        $select = $this->db->select()
            ->from()
            ->where(Db_CiRelation::CI_RELATION_TYPE_ID . ' =?', $relationTypeId)
            ->where(Db_CiRelation::CI_ID_1 . ' =?', $ciId1)
            ->where(Db_CiRelation::CI_ID_2 . ' =?', $ciId2);

        return $this->db->fetchRow($select);
    }

    public function updateRelationNote($relId, $note)
    {
        $data                      = array();
        $data[Db_CiRelation::NOTE] = $note;
        $where                     = $this->db->quoteInto(Db_CiRelation::TABLE_NAME . '.' . Db_CiRelation::ID . ' =?', $relId);

        $table = new Db_CiRelation();
        return $table->update($data, $where);
    }

    /**
     * Gets the last $limit names of file imports. $limit defaults to 1000
     * Used for the regex check for file import trigger
     *
     * @param int $limit amount of file names to fetch; defaults to 1000
     *
     * @return array|null array of strings, the file names; returns null if limit is set to 0
     */
    public function getFileImportNamesForRegExCheck($limit = 1000)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileHistory::TABLE_NAME, Db_ImportFileHistory::FILENAME)
            ->group(Db_ImportFileHistory::FILENAME)
            ->order(Db_ImportFileHistory::ID . ' DESC');

        $select->limit($limit);
        return $this->db->fetchAll($select);
    }
}