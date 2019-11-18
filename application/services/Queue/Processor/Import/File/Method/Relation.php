<?php

abstract class Import_File_Method_Relation extends Import_File_Method_Abstract implements Import_File_Method
{

    public function getAttributeList($data, $logger)
    {
        $result           = array();
        $result['status'] = true;
        $result['errors'] = array();

        $importDaoImpl = new Dao_Import();
        // TODO: check if $data[0] = null, empty or create/delete
        // TODO: check $data[1] = relation_type/rel_type
        if ($data[2] != 'ci_id')
            $data[2] = $importDaoImpl->getAttributeIdByName($data[2], false);

        if (!$data[2]) {
            $result['status']    = false;
            $result['errors'][3] = Import_File_Code::ERROR_UNEXPECTED;
        }
        if ($data[3] != 'ci_id')
            $data[3] = $importDaoImpl->getAttributeIdByName($data[3], false);
        if (!$data[3]) {
            $result['status']    = false;
            $result['errors'][4] = Import_File_Code::ERROR_UNEXPECTED;
        }

        // optional
        // TODO: $data[4] note

        $result['attributes'] = $data;
        return $result;
    }
}