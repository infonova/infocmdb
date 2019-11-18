<?php

class Import_File_Factory
{


    public static function getType($filetype, $logger, $file, $historyId, $options = array())
    {
        try {
            $classname = 'Import_File_Type_' . $filetype;
            $class     = new $classname($logger, $file, $historyId, $options);

            return $class;
        } catch (Exception $e) {
            $logger->log($e, Zend_Log::CRIT);
            return false;
        }
    }


    public static function getMethod($method, $validation, $historyId, $parameter, $logger)
    {
        try {
            $createValidationId  = false;
            $validationContainer = array();


            $val               = null;
            $historyValidation = null;
            switch ($validation) {
                case 'auto':
                    $val               = 'Auto';
                    $historyValidation = Import_File_Util_History::VALIDATION_AUTO;
                    break;
                case 'manual':
                    $val                = 'Manual';
                    $historyValidation  = Import_File_Util_History::VALIDATION_MANUAL;
                    $createValidationId = true;
                    break;
                default:
                    $val               = 'Auto';
                    $historyValidation = Import_File_Util_History::VALIDATION_AUTO;
                    break;
            }
            Import_File_Util_History::updateHistoryValidation($historyId, $historyValidation);

            $meth  = null;
            $queue = null;
            switch ($method) {
                case Service_Queue_Message::METHOD_ATTRIBUTE:
                    $meth  = 'Attribute';
                    $queue = Import_File_Util_History::QUEUE_ATTRIBUTE;
                    break;
                case Service_Queue_Message::METHOD_INSERT:
                    $meth                                = 'Insert';
                    $queue                               = Import_File_Util_History::QUEUE_INSERT;
                    $validationContainer['validationId'] = Import_File_Util_History::QUEUE_INSERT;
                    break;
                case Service_Queue_Message::METHOD_UPDATE:
                    $meth                                = 'Update';
                    $queue                               = Import_File_Util_History::QUEUE_UPDATE;
                    $validationContainer['validationId'] = Import_File_Util_History::QUEUE_UPDATE;
                    break;
                case Service_Queue_Message::METHOD_IMPORT:
                    $meth                                      = 'Import';
                    $queue                                     = Import_File_Util_History::QUEUE_IMPORT;
                    $validationContainer['validationIdInsert'] = Import_File_Util_History::QUEUE_INSERT;
                    $validationContainer['validationIdUpdate'] = Import_File_Util_History::QUEUE_UPDATE;
                    break;
                case Service_Queue_Message::METHOD_RELATION:
                    $meth  = 'Relation';
                    $queue = Import_File_Util_History::QUEUE_RELATION;
                    break;
                default:
                    // TODO: exception!;
                    return false;
                    break;
            }
            Import_File_Util_History::updateHistoryQueue($historyId, $queue);

            // create validationId
            if ($createValidationId) {
                foreach ($validationContainer as $key => $type) {
                    $validationId    = Import_File_Util_History::generateValidationId($type, $parameter['filename']);
                    $parameter[$key] = $validationId;
                }
            }


            $class = 'Import_File_Method_' . $val . '_' . $meth;
            if (class_exists($class)) {
                $ret              = array();
                $ret['class']     = $class;
                $ret['parameter'] = $parameter;
                return $ret;
            } else {
                return false;
            }
            return $class;
        } catch (Exception $e) {
            $logger->log($e, Zend_Log::CRIT);
            return false;
        }
    }

}