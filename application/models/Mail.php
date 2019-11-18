<?php

class Dao_Mail extends Dao_Abstract
{

    public function getMailTemplatesForPagination(
        $orderBy = null,
        $direction = null, $filter = null
    ) {
        $select = $this->db->select()->from(Db_Mail::TABLE_NAME);

        if ($filter) {
            $select->where(
                Db_Mail::TABLE_NAME . '.' . Db_Mail::NAME . ' LIKE "%' .
                $filter . '%"');
            $select->orWhere(
                Db_Mail::TABLE_NAME . '.' . Db_Mail::DESCRIPTION . ' LIKE "%' .
                $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $select->order(Db_Mail::NAME . ' ASC');
        }

        return $select;
    }

    public function getMailByName($scriptName)
    {
        $select = $this->db->select()
            ->from(Db_Mail::TABLE_NAME)
            ->where(Db_Mail::NAME . ' =?', $scriptName);
        return $this->db->fetchRow($select);
    }

    public function deleteMail($mailId)
    {
        $table = new Db_Mail();
        $where = $this->db->quoteInto(Db_Mail::ID . ' = ?', $mailId);
        return $table->delete($where);
    }

    public function insertMail($data)
    {
        $table = new Db_Mail();
        return $table->insert($data);
    }

    public function updateMail($data, $id)
    {
        $table = new Db_Mail();
        $where = array(
            Db_Mail::ID . ' = ?' => $id,
        );
        return $table->update($data, $where);
    }

    public function getMailImportsForPagination()
    {
        $table = new Db_MailImport();
        return $table->select();
    }

    public function getMailImportConfig($mailImportId = null)
    {
        $select = $this->db->select()
            ->from(Db_MailImport::TABLE_NAME)
            ->where(Db_MailImport::IS_ACTIVE . ' =?', '1');

        if ($mailImportId) {
            $select->where(Db_MailImport::ID . ' =?', $mailImportId);
        }
        return $this->db->fetchAll($select);
    }

    public function getMailImport($id)
    {
        $select = $this->db->select()
            ->from(Db_MailImport::TABLE_NAME)
            ->where(Db_MailImport::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getMail($id)
    {
        $select = $this->db->select()
            ->from(Db_Mail::TABLE_NAME)
            ->where(Db_Mail::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function deleteMailImport($mailImportId)
    {
        $table = new Db_MailImport();
        $where = $this->db->quoteInto(Db_MailImport::ID . ' =?', $mailImportId);
        return $table->delete($where);
    }

    public function insertMailImport($data)
    {
        $table = new Db_MailImport();
        return $table->insert($data);
    }

    public function updateMailImport($mailImportId, $data)
    {
        $table = new Db_MailImport();
        $where = $this->db->quoteInto(Db_MailImport::ID . ' =?', $mailImportId);

        return $table->update($data, $where);
    }

    public function getMailImportsForCronjob()
    {
        $select = $this->db->select()
            ->from(Db_MailImport::TABLE_NAME)
            ->where(Db_MailImport::IS_ACTIVE . ' =?', '1')
            ->where(Db_MailImport::EXECUTION_TIME . ' IS NOT NULL')
            ->joinLeft(Db_Cron::TABLE_NAME,
                Db_Cron::TABLE_NAME . '.' . Db_Cron::MAPPING_ID . ' = ' .
                Db_MailImport::TABLE_NAME . '.' . Db_MailImport::ID .
                ' AND ' . Db_Cron::TYPE . ' = "mailimport"',
                array(
                    'cronId' => Db_Cron::ID,
                    Db_Cron::LAST_EXECUTION,
                ));
        return $this->db->fetchAll($select);
    }

    public function updateMailImportsForCronjob($data, $cronId)
    {
        $cron  = new Db_Cron();
        $where = array(
            Db_Cron::ID . ' = ?' => $cronId,
        );
        $cron->update($data, $where);
    }

    public function insertMailImportsForCronjob($data)
    {
        $cron = new Db_Cron();
        $cron->insert($data);
    }

    public function updateMailimportCronjob($mailimportId, $cronjob)
    {
        $select = " UPDATE " . Db_MailImport::TABLE_NAME . " 
					SET " .
            Db_MailImport::EXECUTION_TIME . " = '" . $cronjob . "'
					WHERE " . Db_MailImport::ID .
            " = '" . $mailimportId . "'";
        $this->db->query($select);
    }

    public function getImportMailNotification($mailImportId)
    {
        $select = $this->db->select()
            ->from(Db_Notification::TABLE_NAME)
            ->where(Db_Notification::NOTIFICATION_TYPE . ' =?', 'import_mail')
            ->where(Db_Notification::NOTIFICATION_ID . ' =?', $mailImportId);
        return $this->db->fetchAll($select);
    }
}