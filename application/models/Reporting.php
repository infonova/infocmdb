<?php

class Dao_Reporting extends Dao_Abstract
{


    public function getReportingForPagination($orderBy = null, $direction = null, $filter = null)
    {
        $table  = new Db_Reporting();
        $select = $table->select();

        if ($filter) {
            $select->where(Db_Reporting::TABLE_NAME . '.' . Db_Reporting::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Reporting::TABLE_NAME . '.' . Db_Reporting::DESCRIPTION . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_Reporting::NAME);
        }

        return $select;
    }

    public function getReporting($id)
    {
        $select = $this->db->select()
            ->from(Db_Reporting::TABLE_NAME)
            ->where(Db_Reporting::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function insertReporting($data)
    {
        $table = new Db_Reporting();
        return $table->insert($data);
    }

    public function updateReporting($data, $reportingId)
    {
        $table = new Db_Reporting();
        $where = array(Db_Reporting::ID . ' = ?' => $reportingId);
        $table->update($data, $where);
    }

    public function deleteReporting($reportingId)
    {
        $table = new Db_Reporting();
        $where = array(Db_Reporting::ID . ' = ?' => $reportingId);
        $table->delete($where);
    }


    public function deleteReportingMapping($reportingId)
    {
        $table = new Db_ReportingMapping();
        $where = array(Db_ReportingMapping::REPORTING_ID . ' = ?' => $reportingId);
        $table->delete($where);
    }


    public function deleteNotificationMapping($reportingId)
    {
        $table = new Db_Notification();
        $where = array(Db_Notification::NOTIFICATION_TYPE . ' = "reporting" AND ' . Db_Notification::NOTIFICATION_ID . ' = ?' => $reportingId);
        $table->delete($where);
    }

    public function deleteHistoryMapping($reportingId)
    {
        $table = new Db_ReportingHistory();
        $where = array(Db_ReportingHistory::REPORTING_ID . ' = ?' => $reportingId);
        $table->delete($where);
    }

    public function getReportingMailAddresses($id)
    {
        $select = $this->db->select()
            ->from(Db_Notification::TABLE_NAME)
            ->where(Db_Notification::NOTIFICATION_TYPE . ' = ?', 'reporting')
            ->where(Db_Notification::NOTIFICATION_ID . ' = ?', $id);
        return $this->db->fetchAll($select);
    }

    public function getReportingForCronjob()
    {
        $select = $this->db->select()
            ->from(Db_Reporting::TABLE_NAME)
            ->where(Db_Reporting::TABLE_NAME . '.' . Db_Reporting::IS_ACTIVE . ' =?', '1')
            ->where(Db_Reporting::TABLE_NAME . '.' . Db_Reporting::EXECUTION_TIME . ' IS NOT NULL')
            ->where(Db_Reporting::TABLE_NAME . '.' . Db_Reporting::TRIGGER . ' =?', 'time')
            ->joinLeft(Db_Cron::TABLE_NAME, Db_Cron::TABLE_NAME . '.' . Db_Cron::MAPPING_ID . ' = ' . Db_Reporting::TABLE_NAME . '.' . Db_Reporting::ID . ' AND ' . Db_Cron::TYPE . ' = "reporting"', array('cronId' => Db_Cron::ID, Db_Cron::LAST_EXECUTION));
        return $this->db->fetchAll($select);
    }

    public function insertReportingImportsForCronjob($data)
    {
        $cron = new Db_Cron();
        $cron->insert($data);
    }

    public function updateReportingImportsForCronjob($data, $cronId)
    {
        $cron  = new Db_Cron();
        $where = array(Db_Cron::ID . ' = ?' => $cronId);
        $cron->update($data, $where);
    }

    public function deleteReportingCronjob($reportingId)
    {
        $table = new Db_Cron();
        $where = array(Db_Cron::MAPPING_ID . ' = ?' => $reportingId, Db_Cron::TYPE . ' = ?' => 'reporting');
        $table->delete($where);
    }

    public function executeStatement($statement)
    {
        return $this->db->fetchAll($statement);
    }

    public function insertReportingHistory($reportingHistory)
    {
        $table = new Db_ReportingHistory();
        $table->insert($reportingHistory);
    }

    public function getReportingMappingAttributes($reportingId)
    {
        $select = $this->db->select()
            ->from(Db_Attribute::TABLE_NAME)
            ->join(Db_ReportingMapping::TABLE_NAME, Db_ReportingMapping::TABLE_NAME . '.' . Db_ReportingMapping::MAPPING_ID . ' = ' . Db_Attribute::TABLE_NAME . '.' . Db_Attribute::ID, array())
            ->where(Db_ReportingMapping::TABLE_NAME . '.' . Db_ReportingMapping::REPORTING_ID . ' =?', $reportingId)
            ->where(Db_ReportingMapping::TABLE_NAME . '.' . Db_ReportingMapping::TYPE . ' =?', "attribute");

        return $this->db->fetchAll($select);
    }

    public function getReportingMappingCitypes($reportingId)
    {
        $select = $this->db->select()
            ->from(Db_ReportingMapping::TABLE_NAME)
            ->where(Db_ReportingMapping::TABLE_NAME . '.' . Db_ReportingMapping::REPORTING_ID . ' =?', $reportingId)
            ->where(Db_ReportingMapping::TABLE_NAME . '.' . Db_ReportingMapping::TYPE . ' =?', "ci_type");

        return $this->db->fetchAll($select);
    }

    public function getReportingMappingResult($attributes, $ciTypes)
    {
        $comb = array();
        array_push($comb, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID);

        foreach ($attributes as $attr) {
            array_push($comb, 'MAX( CASE WHEN ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::ATTRIBUTE_ID . ' = ' . $attr[Db_Attribute::ID] . ' THEN '
                . 'CONCAT_WS("", cast(' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_TEXT . ' as char), '
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DATE . ', cast('
                . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::VALUE_DEFAULT . ' as char)) ' .
                ' ELSE \'\' END) AS ' . $attr[Db_Attribute::NAME] . '');

        }

        $ciTypeList = null;
        foreach ($ciTypes as $type) {
            if ($ciTypeList) {
                $ciTypeList .= ", ";
            }
            $ciTypeList .= $type[Db_ReportingMapping::MAPPING_ID];
        }

        $select = $this->db->select()
            ->from(Db_Ci::TABLE_NAME, $comb)
            ->joinLeft(Db_CiAttribute::TABLE_NAME, Db_Ci::TABLE_NAME . '.' . Db_Ci::ID . ' = ' . Db_CiAttribute::TABLE_NAME . '.' . Db_CiAttribute::CI_ID, array())
            ->where(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' IN(' . $ciTypeList . ')')
            ->group(Db_Ci::TABLE_NAME . '.' . Db_Ci::ID)
            ->order(Db_Ci::TABLE_NAME . '.' . Db_Ci::CI_TYPE_ID . ' DESC');

        return $this->db->fetchAll($select);
    }


    public function addNotification($data)
    {
        $table = new Db_Notification();
        return $table->insert($data);
    }


    public function getLatestReportingArchive($reportingId)
    {
        $select = $this->db->select()
            ->from(Db_ReportingHistory::TABLE_NAME)
            ->where(Db_ReportingHistory::REPORTING_ID . ' =?', $reportingId)
            ->order(Db_ReportingHistory::CREATED . ' DESC')
            ->limit(5);
        return $this->db->fetchAll($select);
    }

    public function getSingleArchiveId($archiveId)
    {
        $select = $this->db->select()
            ->from(Db_ReportingHistory::TABLE_NAME)
            ->where(Db_ReportingHistory::ID . ' =?', $archiveId);
        return $this->db->fetchRow($select);
    }

    public function deleteSingleArchive($archiveId)
    {
        $table = new Db_ReportingHistory();
        $where = array(Db_ReportingHistory::ID . ' = ?' => $archiveId);
        $table->delete($where);
    }

    public function activateReporting($reportingId)
    {
        $sql = "UPDATE " . Db_Reporting::TABLE_NAME . " SET " . Db_Reporting::IS_ACTIVE . " = '1' 
		WHERE " . Db_Reporting::ID . " = '" . $reportingId . "'";
        return $this->db->query($sql);
    }

    public function deactivateReporting($reportingId)
    {
        $sql = "UPDATE " . Db_Reporting::TABLE_NAME . " SET " . Db_Reporting::IS_ACTIVE . " = '0' 
		WHERE " . Db_Reporting::ID . " = '" . $reportingId . "'";
        return $this->db->query($sql);
    }

    public function countHistoryRoleByReportingId($reportingId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_ReportingHistory::TABLE_NAME . "
				   WHERE " . Db_ReportingHistory::REPORTING_ID . " = '" . $reportingId . "'
				   ";

        return $this->db->fetchRow($select);
    }

    public function countHistoryMappingByReportingId($reportingId)
    {
        $select = "SELECT COUNT(*) as cnt 
				   FROM " . Db_ReportingMapping::TABLE_NAME . "
				   WHERE " . Db_ReportingMapping::REPORTING_ID . " = '" . $reportingId . "'
				   ";

        return $this->db->fetchRow($select);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_Reporting::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_Reporting::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_Reporting::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }


}