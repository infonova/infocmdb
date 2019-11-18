<?php

class Process_Reporting_Input_Cql extends Process_Reporting_Input
{


    protected function processValid($reporting)
    {
        // TODO: parameter to replace??
        $statement = $reporting[Db_Reporting::STATEMENT];


        $queryClass = Query_Factory::get($statement);
        $queryClass->handleQuery();
        $result = $queryClass->getResponseMessage();

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