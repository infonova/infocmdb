<?php

class Process_Reporting_Input_Gui extends Process_Reporting_Input
{


    protected function processValid($reporting)
    {
        $reportingDaoImpl = new Dao_Reporting();

        $attributes = $reportingDaoImpl->getReportingMappingAttributes($reporting[Db_Reporting::ID]);
        $ciTypes    = $reportingDaoImpl->getReportingMappingCitypes($reporting[Db_Reporting::ID]);

        $result = $reportingDaoImpl->getReportingMappingResult($attributes, $ciTypes);

        unset($attributes);
        unset($ciTypes);
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