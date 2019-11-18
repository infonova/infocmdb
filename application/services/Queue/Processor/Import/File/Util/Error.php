<?php

class Import_File_Util_Error
{

    public static function checkReturnStatus($result, $historyId, $currentLine)
    {
        if (!$result)
            $result = array();

        if (!$result['status']) {
            $errorList = $result['errors'];

            if ($errorList)
                foreach ($errorList as $column => $message) {
                    Import_File_Util_History::addErrorHistory($historyId, $currentLine, $column, $message);
                }

            return false;
        }
        return true;
    }
}