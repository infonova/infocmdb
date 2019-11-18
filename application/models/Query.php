<?php

class Dao_Query extends Dao_Abstract
{


    public function getQueryById($queryId)
    {
        $sql = $this->db->select()
            ->from(Db_StoredQuery::TABLE_NAME)
            ->where(Db_StoredQuery::ID . ' = ?', $queryId);
        return $this->db->fetchRow($sql);
    }

    public function getQueryByName($queryName)
    {
        $sql = $this->db->select()
            ->from(Db_StoredQuery::TABLE_NAME)
            ->where(Db_StoredQuery::NAME . ' = ?', $queryName);
        return $this->db->fetchRow($sql);
    }

    public function getDefaultQuery($orderBy = null, $direction = null)
    {
        $select = $this->db->select()
            ->from(Db_StoredQuery::TABLE_NAME)
            ->where(Db_StoredQuery::IS_DEFAULT . ' =?', '1');
        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;
            $select->order($orderBy);
        } else {
            $select->order(Db_StoredQuery::NAME, ' ASC');
        }
        return $this->db->fetchAll($select);
    }

    public function updateQueryStatus($queryId, $status = '1', $message = null)
    {
        $table = new Db_StoredQuery();

        $data                                 = array();
        $data[Db_StoredQuery::STATUS]         = $status;
        $data[Db_StoredQuery::STATUS_MESSAGE] = $message;

        $where = $this->db->quoteInto(Db_StoredQuery::ID . ' =?', $queryId);
        return $table->update($data, $where);
    }

    public function getQueryForPagination($orderBy = null, $direction = null, $filter = null)
    {
        $select = $this->db->select()
            ->from(Db_StoredQuery::TABLE_NAME)
            ->where(Db_StoredQuery::IS_DEFAULT . ' =?', '0');

        if ($filter) {
            $select->where(Db_StoredQuery::TABLE_NAME . '.' . Db_StoredQuery::NAME . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;
            $select->order($orderBy);
        } else $select->order(Db_StoredQuery::NAME, ' ASC');

        return $select;
    }

    public function insertQuery($query)
    {
        $table = new Db_StoredQuery();
        return $table->insert($query);
    }

    public function updateQuery($queryId, $query)
    {
        $table = new Db_StoredQuery();
        $where = $this->db->quoteInto(Db_StoredQuery::ID . ' = ?', $queryId);
        return $table->update($query, $where);
    }

    public function deleteQuery($queryId)
    {
        $table = new Db_StoredQuery();
        $where = $this->db->quoteInto(Db_StoredQuery::ID . ' = ?', $queryId);
        return $table->delete($where);
    }

    public function execute($statement)
    {
        return $this->executeQuery($statement);
    }

    public function executeQuery($statement, $parameters = array(), &$generatedStatement = '')
    {
        $replaceParams = array();
        foreach ($parameters as $paramName => $paramValue) {
            $replaceParams[':' . $paramName . ':'] = $paramValue;
        }
        $generatedStatement = str_replace(array_keys($replaceParams), array_values($replaceParams), $statement);

        // split queries, only return last one --> works only with line-break
        $querys   = preg_split("/;[\r|\n]/", $generatedStatement);
        $isResult = false;
        foreach ($querys as $q) {
            $q = trim($q);
            if ($q != '') {
                $sql      = $this->db->query($q);
                $isResult = false;
                if (substr(strtolower($q), 0, 6) == 'select') {
                    $isResult = true;
                }
            }
        }
        if (isset($sql)) {
            if ($isResult == true) {
                return $sql->fetchAll();
            } else {
                return array();
            }
        }

    }


    public function executeStatement($statement)
    {
        return $this->executeQuery($statement);
    }
}