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

    public function isQueryDefaultQuery($queryName)
    {
        $sql = $this->db->select()
            ->from(Db_StoredQuery::TABLE_NAME)
            ->where(Db_StoredQuery::NAME . ' = ?', $queryName);

        $queryRecord = $this->db->fetchRow($sql);
        return $queryRecord[Db_StoredQuery::IS_DEFAULT];

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

    public function executeDefaultQuery($scriptname, $statement, $parameters = array())
    {
        switch ($scriptname){
            case "int_getCiIdByCiAttributeValue":
            case "int_getCiAttributeValue":
                $replaceParams = array();
                foreach ($parameters as $paramName => $paramValue) {
                    if ($paramName == "argv3") {
                        $replaceParams[':' . $paramName . ':'] = $paramValue;
                    }
                }
                $statement = str_replace(array_keys($replaceParams), array_values($replaceParams), $statement);
                unset($parameters["argv3"]);
                return $this->executeQueryPreparedStmt($statement,$parameters);
            case "int_updateCiAttribute":
            case "int_getCiTypeOfCi":
                $replaceParams = array();
                foreach ($parameters as $paramName => $paramValue) {
                    if ($paramName == "argv2") {
                        $replaceParams[':' . $paramName . ':'] = $paramValue;
                    }
                }
                $statement = str_replace(array_keys($replaceParams), array_values($replaceParams), $statement);
                unset($parameters["argv2"]);
                return $this->executeQueryPreparedStmt($statement,$parameters);
            case "int_createProject":
            case "int_updateProject ":
            case "int_updateProject":
                $replaceParams = array();
                foreach ($parameters as $paramName => $paramValue) {
                    $replaceParams[$paramName] = str_replace("'", "", $paramValue);
                }
                return $this->executeQueryPreparedStmt($statement,$replaceParams);
            case "int_deleteProject":
            case "int_setAttributeRole":
            case "int_deleteProject ":
                $replaceParams = array();
                foreach ($parameters as $paramName => $paramValue) {
                    if ($paramValue != "") {
                        $replaceParams[$paramName] = $paramValue;
                    }
                }
                return $this->executeQueryPreparedStmt($statement,$replaceParams);
            case "int_createAttribute":
            case "int_createAttributeGroup":
            case "int_createCIType":
                $replaceParams = array();
                foreach ($parameters as $paramName => $paramValue) {
                    if ($paramName == "argv1") {
                        $replaceParams[':' . $paramName . ':'] = $paramValue;
                    }
                }

                $statement = str_replace(array_keys($replaceParams), array_values($replaceParams), $statement);

                $values = array();
                $newParams = array();
                $newParmaPlaceHolders = array();

                if (strpos($parameters["argv1"],',') !== false){
                    $columns = explode(",",$parameters["argv1"]);
                    $numberOfColumns = count($columns);

                }else{
                    $numberOfColumns = 1;
                }

                if (strpos($parameters["argv2"],',') !== false){
                    $values = explode(",",$parameters["argv2"]);
                    $numberOfValues = count($values);
                }else{
                    $numberOfValues = 1;
                }

                if ($numberOfColumns == $numberOfValues){
                    for ($x = 0; $x < $numberOfColumns; $x++) {
                        $newParams[":argv". $x] = str_replace("'", "", $values[$x]);
                        $newParmaPlaceHolders[$x] = ":argv". $x . ":";
                    }
                }

                $statement = str_replace(":argv2:", implode(",",$newParmaPlaceHolders), $statement);

                return $this->executeQueryPreparedStmt($statement,$newParams);
            case "int_updateCIType":
                $numberOfFieldsToUpdate = 0;
                if (strpos($parameters["argv2"],',') !== false ){
                    $fieldsAndValues = explode(",",$parameters["argv2"]);
                    $numberOfFieldsToUpdate = count($fieldsAndValues);
                }

                $newArgv2 = array();
                $newParams = array();

                for ($x = 0; $x < $numberOfFieldsToUpdate; $x++) {
                    $fieldName = strtok($fieldsAndValues[$x],"=");
                    $valueOfField = strtok("");

                    $y = $x +3;

                    $newArgv2[] = $fieldName . " = :argv" . $y . ":";
                    $newParams["argv". $y ] = str_replace("'", "", $valueOfField);

                }

                $statement = str_replace(":argv2:", implode(",", $newArgv2) , $statement);
                unset($parameters["argv2"]);
                $paramsToPass = array_merge($parameters,$newParams);

                return $this->executeQueryPreparedStmt($statement,$paramsToPass);
            default:
                return $this->executeQueryPreparedStmt($statement,$parameters);
        }

    }


    public function executeQueryPreparedStmt($statement, $parameters = array()){

        $pattern = '/(:argv\d+:)/';

        $preparedSQL = preg_replace_callback($pattern, function ($matches){
            $match = $matches[1];
            $length = strlen($match);
            return substr($match,0,$length -1);
        },$statement);

        $replaceParams = array();
        foreach ($parameters as $paramName => $paramValue) {
            if ($paramName != "user_id"){
                $replaceParams[$paramName] = $paramValue;
            }
        }

        $result = array();
        $isResult = false;
        $isUpdate = false;
        $isSpecialResult = false;
        $isProcedureCall = false;
        $isInsert = false;
        $isAttributeRoleDelete = false;
        $querys   = preg_split("/;[\r|\n]/", $preparedSQL);

        foreach ($querys as $q) {

            $q = trim($q);

            if ($q != "") {
                if (substr(strtolower($q), 0, 6) == 'select') {
                    $isResult = true;
                }

                if (substr(strtolower($q), 0, 6) == 'select' && strpos($q,'when') !== false) {
                    $isSpecialResult = true;
                    $isResult = false;
                }

                if (substr(strtolower($q), 0, 6) == 'update') {
                    $isUpdate = true;
                }

                if (substr(strtolower($q), 0, 6) == 'insert') {
                    $isInsert = true;
                }

                if (substr(strtolower($q), 0, 6) == 'insert' && strpos($q,'project') !== false) {
                    $isInsert = false;
                }

                if (substr(strtolower($q), 0, 6) == 'update' && strpos($q,'project') !== false) {
                    $isUpdate = false;
                }

                if (substr(strtolower($q), 0, 4) == 'call') {
                    $isProcedureCall = true;
                }

                if (substr(strtolower($q), 0, 6) == 'delete' && strpos($q,'attribute_role') !== false) {
                    $isAttributeRoleDelete = true;
                }else{
                    $isAttributeRoleDelete = false;
                }

                if ($isResult == true || $isUpdate == true || $isProcedureCall == true || $isInsert == true)  {
                    $stmt = $this->db->prepare(str_replace("'", "", $q));
                } else {
                    $stmt = $this->db->prepare($q);
                }

                if ($isAttributeRoleDelete == true){
                    $slice = array_slice($replaceParams,0,2,true);
                    $stmt->execute($slice);
                }else{
                    $stmt->execute($replaceParams);
                }

                if ($isResult == true || $isSpecialResult == true) {
                    $result = $stmt->fetchAll();
                }
            }
        }
        return $result;
    }


    public function executeStatement($statement)
    {
        return $this->executeQuery($statement);
    }
}