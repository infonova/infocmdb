<?php

/**
 *
 *
 *
 */
class Service_Dashboard_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3402, $themeId);
    }


    /**
     * creates a Todo-Item by the given values
     *
     * @param array $values
     */
    public function createTodoItem($ciAttributeId, $userId)
    {
        try {
            $calendarDaoImpl = new Dao_Calendar();
            $primary         = $calendarDaoImpl->createTodoItem($ciAttributeId, $userId);

            if (!$primary) {
                throw new Exception_Dashboard_InsertFailed();
            } else {
                return $primary;
            }
        } catch (Exception $e) {
            throw new Exception_Dashboard_InsertFailed($e);
        }
    }
}