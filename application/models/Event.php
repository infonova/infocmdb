<?php

class Dao_Event extends Dao_Abstract
{


    public function getEventsForPagination($orderBy = null, $direction = null, $ciid = null, $eventFilter = null)
    {
        $select = $this->db->select()
            ->from(Db_CiEvent::TABLE_NAME);

        if ($ciid)
            $select->where(Db_CiEvent::TABLE_NAME . '.' . Db_CiEvent::CI_ID . ' =?', $ciid);

        if ($eventFilter)
            $select->where(Db_CiEvent::TABLE_NAME . '.' . Db_CiEvent::EVENT_NAME . ' =?', $ticketFilter);

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        }

        return $select;
    }
}