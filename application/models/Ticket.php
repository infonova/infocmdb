<?php

class Dao_Ticket extends Dao_Abstract
{


    public function getTicketsForPagination($orderBy = null, $direction = null, $ciid = null, $ticketFilter = null)
    {
        $select = $this->db->select()
            ->from(Db_CiTicket::TABLE_NAME);

        if ($ciid)
            $select->where(Db_CiTicket::TABLE_NAME . '.' . Db_CiTicket::CI_ID . ' =?', $ciid);

        if ($ticketFilter)
            $select->where(Db_CiTicket::TABLE_NAME . '.' . Db_CiTicket::TICKET_NAME . ' =?', $ticketFilter);

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        }

        return $select;
    }
}