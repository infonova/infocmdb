<?php

/**
 *
 *
 *
 */
class Service_Dashboard_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3403, $themeId);
    }


    /**
     * updates a Todo-Item by by setting it to 'done'
     *
     * @param int $ciAttributeId
     */
    public function completeTodoItem($ciAttributeId)
    {
        try {
            // set Todo Deleted
            $calendarDaoImpl = new Dao_Calendar();
            $data            = array(
                Db_TodoItems::COMPLETED => date("Y-m-d H:i:s", time()),
                Db_TodoItems::STATUS    => 'done',
            );
            return $calendarDaoImpl->updateTodoItem($ciAttributeId, $data);
        } catch (Exception $e) {
            throw new Exception_Dashboard_DeleteFailed($e);
        }
    }

    /**
     * updates priority of a Todo-Item
     *
     * @param int $ciAttributeId
     */
    public function changePriority($ciAttributeId, $priority)
    {
        try {
            $calendarDaoImpl = new Dao_Calendar();
            $data            = array(
                Db_TodoItems::PRIORITY => $priority,
            );
            return $calendarDaoImpl->updateTodoItem($ciAttributeId, $data);
        } catch (Exception $e) {
            throw new Exception_Dashboard_DeleteFailed($e);
        }
    }


}