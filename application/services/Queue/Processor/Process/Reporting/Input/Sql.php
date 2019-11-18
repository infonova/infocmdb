<?php

class Process_Reporting_Input_Sql extends Process_Reporting_Input
{


    protected function processValid($reporting)
    {
        // TODO: parameter to replace??
        $statement = $reporting[Db_Reporting::STATEMENT];

        $reportingDaoImpl = new Dao_Reporting();
        $result           = $reportingDaoImpl->executeStatement($statement);

        $attributes = array();
        if ($result && count($result) > 0) {
            foreach ($result[0] as $attribute => $val) {
                array_push($attributes, $attribute);
            }
        }

        $this->setAttributes($attributes);
        $this->setData($result);
    }
}