<?php

/**
 *
 *
 *
 */
class Service_Dashboard_Delete extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3404, $themeId);
    }


    public function deleteTodoItem($ciAttributeId)
    {
        try {
            // set Todo Deleted
            $calendarDaoImpl = new Dao_Calendar();
            $data            = array(
                Db_TodoItems::COMPLETED => date("Y-m-d H:i:s", time()),
                Db_TodoItems::STATUS    => 'deleted',
            );
            return $calendarDaoImpl->updateTodoItem($ciAttributeId, $data);
        } catch (Exception $e) {
            throw new Exception_Dashboard_DeleteFailed($e);
        }
    }

}