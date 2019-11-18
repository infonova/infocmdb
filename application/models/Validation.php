<?php

class Dao_Validation extends Dao_Abstract
{

    public function createValidation($data)
    {
        $table = new Db_ImportFileValidation();
        return $table->insert($data);
    }

    public function updateValidation($id, $data)
    {
        $table = new Db_ImportFileValidation();
        $where = $table->getAdapter()->quoteInto(Db_ImportFileValidation::ID . ' = ?', $id);
        return $table->update($data, $where);
    }

    public function getValidation($id)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidation::TABLE_NAME)
            ->where(Db_ImportFileValidation::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function addValidationAttribute($data)
    {
        $table = new Db_ImportFileValidationAttributes();
        return $table->insert($data);
    }


    public function getValidationAttributesGroupByCiId($validationId)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME, array(Db_ImportFileValidationAttributes::CI_ID))
            ->joinLeft(Db_ImportFileValidation::TABLE_NAME, Db_ImportFileValidation::TABLE_NAME . '.' . Db_ImportFileValidation::ID . ' = ' . Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALIDATION_ID, array(Db_ImportFileValidation::CI_TYPE_ID))
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALIDATION_ID . ' =?', $validationId)
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::STATUS . ' =?', 'idle')
            ->group(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::CI_ID)
            ->order(Db_ImportFileValidation::TABLE_NAME . '.' . Db_ImportFileValidation::CI_TYPE_ID . ' DESC');
        return $this->db->fetchAll($select);
    }


    public function getInsertList($validationId, $ciId, $attributeList)
    {
        $comb = array();
        array_push($comb, Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::CI_ID);
        array_push($comb, Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::PROJECT_ID);
        array_push($comb, Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::CI_TYPE_ID);

        foreach ($attributeList as $attr) {
            $comb[$attr[Db_Attribute::NAME]] = new Zend_Db_Expr('MAX( CASE WHEN ' . Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . 'cast(' . Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALUE . ' as char) ' .
                ' ELSE \'\' END)');

        }

        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME, $comb)
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALIDATION_ID . ' =?', $validationId)
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::CI_ID . ' =?', $ciId)
            ->group(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::CI_ID);;

        return $this->db->fetchRow($select);
    }

    public function getValidationDetailForPagination($validationId, $isCount = false, $limitFrom = null, $limitTo = null, $orderBy = null, $direction = null)
    {
        $select = $this->db->select();

        if ($isCount) {
            $select->from(Db_ImportFileValidationAttributes::TABLE_NAME, array('cnt' => 'count(*)'));
        } else {
            $select->from(Db_ImportFileValidationAttributes::TABLE_NAME);
            $select->joinLeft(Db_Attribute::TABLE_NAME, Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array(Db_Attribute::DESCRIPTION, Db_Attribute::NAME, Db_Attribute::ATTRIBUTE_TYPE_ID));
            $select->joinLeft(Db_CiAttribute::TABLE_NAME, Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::UNIQUE_ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID . ' and ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' = ' . Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::CI_ID, array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_DEFAULT, Db_CiAttribute::VALUE_CI));
        }

        $select->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId);
        $select->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::STATUS . ' = ?', 'idle');

        if ($orderBy) {
            if ($orderBy == 'attribute') {
                if (!$direction)
                    $direction = 'DESC';

                $select->order(Db_Attribute::TABLE_NAME . '.' . Db_Attribute::NAME . ' ' . $direction);
            }
            if ($orderBy == 'valuenew') {
                if (!$direction)
                    $direction = 'DESC';
                $select->order(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALUE . ' ' . $direction);

            }
            if ($orderBy == 'valueold') {
                if (!$direction)
                    $direction = 'DESC';
                $select->order(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' ' . $direction);

            }

            if ($orderBy == 'ciid') {
                if (!$direction)
                    $direction = 'DESC';
                $select->order(Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID . ' ' . $direction);

            }


        }

        if ($limitTo) {
            $select->limit($limitTo, $limitFrom);
        }
        return $this->db->fetchAll($select);;
    }

    public function getIdleAttributesByIdList($validationId, $attributeIdList)
    {
        #update ci_attribute id in validation entry if no id is set to prevent double insert values
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId)
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::STATUS . ' = ?', 'idle')
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::ID . ' IN(' . $attributeIdList . ')');

        $result = $this->db->fetchAll($select);

        foreach ($result as $row) {
            if (!$row['ciAttributeId']) {
                $ciAttributeId = $this->getCiAttributeEntryIfExists($row['ci_id'], $row['attribute_id']);
                foreach ($ciAttributeId as $entry) {
                    if ($entry['id']) {
                        $data   = array(
                            Db_ImportFileValidationAttributes::UNIQUE_ID => $entry['id'],
                        );
                        $update = $this->db->update(Db_ImportFileValidationAttributes::TABLE_NAME, $data, Db_ImportFileValidationAttributes::ID . ' = ' . $row['id']);
                    }
                }
            }
        }

        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::UNIQUE_ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID, array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_DEFAULT, Db_CiAttribute::VALUE_CI, 'ciAttributeId' => Db_CiAttribute::ID))
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId)
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::STATUS . ' = ?', 'idle')
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::ID . ' IN(' . $attributeIdList . ')');
        $result = $this->db->fetchAll($select);
        #var_dump($result);
        return $result;
    }

    public function getCiAttributeEntryIfExists($ci_id, $attribute_id)
    {
        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::CI_ID . ' = ' . $ci_id)
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $attribute_id);
        return $this->db->fetchAll($select);
    }

    public function getIdleValidationAttributesForUpdate($validationId, $ciId = null)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::UNIQUE_ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID, array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_DEFAULT, Db_CiAttribute::VALUE_CI, 'ciAttributeId' => Db_CiAttribute::ID))
            ->where(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId)
            ->where(Db_ImportFileValidationAttributes::STATUS . ' = ?', 'idle');

        if ($ciId)
            $select->where(Db_ImportFileValidationAttributes::CI_ID . ' = ?', $ciId);

        return $this->db->fetchAll($select);
    }

    public function getValidationAttribtuesCheck($validationId)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME, array(Db_ImportFileValidationAttributes::ID))
            ->where(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId)
            ->where(Db_ImportFileValidationAttributes::STATUS . ' = ?', 'idle');
        return $this->db->fetchAll($select);
    }

    public function getImportFileAttributesByValidationId($validationId)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->joinLeft(Db_CiAttribute::TABLE_NAME,
                Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::UNIQUE_ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ID,
                array(Db_CiAttribute::VALUE_TEXT, Db_CiAttribute::VALUE_DATE, Db_CiAttribute::VALUE_DEFAULT, Db_CiAttribute::VALUE_CI)
            )
            ->where(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId);
        return $this->db->fetchAll($select);
    }

    public function getImportFileInsertAttributesByValidationId($validationId, $newCiNr)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->joinLeft(Db_Attribute::TABLE_NAME,
                Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID,
                array(Db_Attribute::ATTRIBUTE_TYPE_ID, Db_Attribute::DESCRIPTION)
            )
            ->where(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId);
        if ($newCiNr)
            $select->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::CI_ID . ' = ?', $newCiNr);
        return $this->db->fetchAll($select);
    }

    public function getImportFileNewCisByValidationId($validationId)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->join(Db_ImportFileValidation::TABLE_NAME,
                Db_ImportFileValidation::TABLE_NAME . '.' . Db_ImportFileValidation::ID . ' = ' . Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::VALIDATION_ID,
                array(Db_ImportFileValidation::CI_TYPE_ID)
            )
            ->join(Db_CiType::TABLE_NAME,
                Db_ImportFileValidation::TABLE_NAME . '.' . Db_ImportFileValidation::CI_TYPE_ID . ' = ' . Db_CiType::TABLE_NAME . '.' . Db_CiType::ID,
                array('ciType' => Db_CiType::NOTE)
            )
            ->where(Db_ImportFileValidationAttributes::TABLE_NAME . '.' . Db_ImportFileValidationAttributes::STATUS . ' like \'idle\' ')
            ->where(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId)
            ->group(Db_ImportFileValidationAttributes::CI_ID);
        return $this->db->fetchAll($select);

    }

    public function getImportFileAttributeById($attributeId)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->where(Db_ImportFileValidationAttributes::ID . ' = ?', $attributeId);
        $result = $this->db->fetchAll($select);
        return $result[0];
    }

    public function getImportFiles($status = "in_progress")
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidation::TABLE_NAME)
            ->where(Db_ImportFileValidation::STATUS . ' = ?', $status);

        return $select;
    }

    public function getImportFileByValidationId($validationId)
    {
        $select = $this->db->select()
            ->from(Db_ImportFileValidation::TABLE_NAME)
            ->where(Db_ImportFileValidation::ID . ' = ?', $validationId);

        $result = $this->db->fetchAll($select);
        return $result[0];
    }

    public function updateImportFile($validationId, $data)
    {
        $table = new Db_ImportFileValidation();
        $where = $table->getAdapter()->quoteInto(Db_ImportFileValidation::ID . ' = ?', $validationId);
        return $table->update($data, $where);
    }

    public function updateImportFileAttributes($validationId, $data)
    {
        $table = new Db_ImportFileValidationAttributes();
        $where = $table->getAdapter()->quoteInto(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId);
        return $table->update($data, $where);
    }

    public function deleteImportFileAttributesByValidationId($validationId, $userId)
    {
        $data = array(
            Db_ImportFileValidationAttributes::USER_ID   => $userId,
            Db_ImportFileValidationAttributes::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidationAttributes::STATUS    => 'deleted',
        );

        return $this->updateImportFileAttributes($validationId, $data);
    }

    public function matchImportFileAttributesByValidationId($validationId, $userId)
    {
        $data = array(
            Db_ImportFileValidationAttributes::USER_ID   => $userId,
            Db_ImportFileValidationAttributes::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidationAttributes::STATUS    => 'matched',
        );

        return $this->updateImportFileAttributes($validationId, $data);
    }

    public function deleteImportFileAttribute($attributeId, $userId)
    {
        $data  = array(
            Db_ImportFileValidationAttributes::USER_ID   => $userId,
            Db_ImportFileValidationAttributes::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidationAttributes::STATUS    => 'deleted',
        );
        $table = new Db_ImportFileValidationAttributes();
        $where = $table->getAdapter()->quoteInto(Db_ImportFileValidationAttributes::ID . ' = ?', $attributeId);
        return $table->update($data, $where);
    }

    public function deleteImportFileAttributesByCiNr($validationId, $ciNr, $userId)
    {
        $data  = array(
            Db_ImportFileValidationAttributes::USER_ID   => $userId,
            Db_ImportFileValidationAttributes::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidationAttributes::STATUS    => 'deleted',
        );
        $table = new Db_ImportFileValidationAttributes();
        $where = $table->getAdapter()->quoteInto(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = "' . $validationId . '" and ' . Db_ImportFileValidationAttributes::CI_ID . ' = ?', $ciNr);
        return $table->update($data, $where);
    }

    public function matchImportFileAttribute($attributeId, $userId)
    {
        $data  = array(
            Db_ImportFileValidationAttributes::USER_ID   => $userId,
            Db_ImportFileValidationAttributes::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidationAttributes::STATUS    => 'matched',
        );
        $table = new Db_ImportFileValidationAttributes();
        $where = $table->getAdapter()->quoteInto(Db_ImportFileValidationAttributes::ID . ' = ?', $attributeId);
        return $table->update($data, $where);
    }

    public function matchImportFileAttributes($validationId, $ciNr, $userId)
    {
        $data  = array(
            Db_ImportFileValidationAttributes::USER_ID   => $userId,
            Db_ImportFileValidationAttributes::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidationAttributes::STATUS    => 'matched',
        );
        $table = new Db_ImportFileValidationAttributes();
        $where = $this->db->quoteInto(Db_ImportFileValidationAttributes::STATUS . ' = "idle" and ' . Db_ImportFileValidationAttributes::VALIDATION_ID . ' = "' . $validationId . '" and ' . Db_ImportFileValidationAttributes::CI_ID . ' = ?', $ciNr);
        return $table->update($data, $where);
    }

    public function overwriteImportFileAttribute($attributeId, $userId)
    {
        $data  = array(
            Db_ImportFileValidationAttributes::USER_ID   => $userId,
            Db_ImportFileValidationAttributes::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidationAttributes::STATUS    => 'overwritten',
        );
        $table = new Db_ImportFileValidationAttributes();
        $where = $table->getAdapter()->quoteInto(Db_ImportFileValidationAttributes::ID . ' = ?', $attributeId);
        return $table->update($data, $where);
    }

    public function deleteImportFile($validationId)
    {
        return $this->completeImportFile($validationId);
    }

    public function matchImportFile($validationId)
    {
        return $this->completeImportFile($validationId);
    }

    public function completeImportFile($validationId)
    {
        $data = array(
            Db_ImportFileValidation::FINALIZED => date("Y-m-d H:i:s", time()),
            Db_ImportFileValidation::STATUS    => 'completed',
        );
        return $this->updateImportFile($validationId, $data);
    }

    public function uniqueCheck($attribute_id, $value)
    {

        $select = $this->db->select()
            ->from(Db_CiAttribute::TABLE_NAME)
            ->where(Db_CiAttribute::ATTRIBUTE_ID . ' = ?', $attribute_id)
            ->where(Db_CiAttribute::VALUE_TEXT . ' = "' . $value . '" or ' . Db_CiAttribute::VALUE_DEFAULT . ' = "' . $value . '" or ' . Db_CiAttribute::VALUE_DATE . ' = "' . $value . '"')
            ->join(Db_Attribute::TABLE_NAME,
                Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID);

        $result = $this->db->fetchAll($select);
        return count($result);


    }

    /**
     *
     * @param type $ciId
     * @param type $validationId
     */
    public function getValidationAttributeGroupByCi($ciId, $validationId)
    {
        //$table = new Db_ImportFileValidationAttributes();
        $select = $this->db->select()
            ->from(Db_ImportFileValidationAttributes::TABLE_NAME)
            ->where(Db_ImportFileValidationAttributes::VALIDATION_ID . ' = ?', $validationId)
            ->where(Db_ImportFileValidationAttributes::CI_ID . ' = ?', $ciId)
            ->group(Db_ImportFileValidationAttributes::CI_ID);
        return $this->db->fetchRow($select);
    }


}