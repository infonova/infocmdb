<?php

class Dao_Lock extends Dao_Abstract
{


    /** @var  integer */
    private $id;

    /** @var  string */
    private $lock_type;

    /** @var  integer */
    private $resource_id;

    /** @var  integer */
    private $held_by;

    /** @var  string */
    private $locked_since;

    /** @var  string */
    private $valid_until;


    /**
     * Apply Data to object
     *
     * @param array $lock lock row
     */
    public function applyData($lock = array())
    {
        if (isset($lock[Db_Lock::ID])) {
            $this->setId($lock[Db_Lock::ID]);
        }

        if (isset($lock[Db_Lock::LOCK_TYPE])) {
            $this->setLockType($lock[Db_Lock::LOCK_TYPE]);
        }

        if (isset($lock[Db_Lock::RESOURCE_ID])) {
            $this->setResourceId($lock[Db_Lock::RESOURCE_ID]);
        }

        if (isset($lock[Db_Lock::HELD_BY])) {
            $this->setHeldBy($lock[Db_Lock::HELD_BY]);
        }

        if (isset($lock[Db_Lock::LOCKED_SINCE])) {
            $this->setLockedSince($lock[Db_Lock::LOCKED_SINCE]);
        }

        if (isset($lock[Db_Lock::VALID_UNTIL])) {
            $this->setValidUntil($lock[Db_Lock::VALID_UNTIL]);
        }
    }

    /**
     * @return array Object properties
     */
    public function getAsArray()
    {
        $array = get_object_vars($this);
        unset($array['db']);

        return $array;
    }


    /**
     * DB Helper
     */

    /**
     * Get full lock row by id
     *
     * @param $id id
     *
     * @return mixed
     */
    public function getLockRow($id)
    {
        $select = $this->db->select()
            ->from(Db_Lock::TABLE_NAME)
            ->where(Db_Lock::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    /**
     * Get full lock row by lock_type and resource_id
     *
     * @param $lockType
     * @param $resourceId
     *
     * @return mixed
     */
    public function getLockRowByResourceIdAndLockType($lockType, $resourceId)
    {
        $select = $this->db->select()
            ->from(Db_Lock::TABLE_NAME)
            ->where(Db_Lock::LOCK_TYPE . ' =?', $lockType)
            ->where(Db_Lock::RESOURCE_ID . ' =?', $resourceId);
        $lock   = $this->db->fetchRow($select);
        return $lock;
    }

    /**
     * Gets all locks held by a given user
     *
     * @param $userId integer id of the user to get the locks from
     *
     * @return array returns an array of locks or null if none were found
     */
    public function getLockRowsOfUser($userId)
    {
        $select = $this->db->select()
            ->from(Db_Lock::TABLE_NAME)
            ->where(Db_Lock::HELD_BY . ' =?', $userId);
        $locks  = $this->db->fetchAll($select);
        return $locks;
    }


    /**
     * DB Handling
     */

    /**
     * Insert object into database
     *
     * @return bool
     */
    public function create()
    {
        $table = new Db_Lock();
        $data  = $this->getAsArray();

        $id = $table->insert($data);
        if (!empty($id)) {
            $this->setId($id);
            return true;
        }

        return false;
    }

    /**
     * Update object in database
     *
     * @return bool
     */
    public function update()
    {
        $table = new Db_Lock();
        $data  = $this->getAsArray();
        $where = $this->db->quoteInto(Db_Lock::ID . ' =?', $this->id);

        $status = $table->update($data, $where);
        if (!empty($status)) {
            return true;
        }

        return false;
    }

    /**
     * Delete object in database
     *
     * @return bool
     */
    public function delete()
    {
        if (!$this->id) {
            return false;
        }

        $table        = new Db_Lock();
        $where        = $this->db->quoteInto(Db_Lock::ID . ' = ?', $this->id);
        $deleteStatus = $table->delete($where);

        if (!empty($deleteStatus)) {
            $this->setId(null);
            return true;
        }

        return false;
    }

    /**
     * Deletes all locks held by user (f.e. on logout)
     *
     * @param $userId integer id of the user which locks should be deleted
     */
    public function deleteLocksOfUser($userId)
    {
        $table = new Db_Lock();
        $where = $this->db->quoteInto(Db_Lock::HELD_BY . ' = ?', $userId);
        $table->delete($where);
    }

    /**
     * Insert or update object in database
     *
     * @return bool
     */
    public function save()
    {
        if (empty($this->id)) {
            return $this->create();
        } else {
            return $this->update();
        }
    }

    /**
     * Getters and Setters
     */

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getLockType()
    {
        return $this->lock_type;
    }

    /**
     * @param string $lock_type
     */
    public function setLockType($lock_type)
    {
        $this->lock_type = $lock_type;
    }

    /**
     * @return int
     */
    public function getResourceId()
    {
        return $this->resource_id;
    }

    /**
     * @param int $resource_id
     */
    public function setResourceId($resource_id)
    {
        $this->resource_id = $resource_id;
    }

    /**
     * @return int
     */
    public function getHeldBy()
    {
        return $this->held_by;
    }

    /**
     * @param int $held_by
     */
    public function setHeldBy($held_by)
    {
        $this->held_by = $held_by;
    }

    /**
     * @return DateTime
     */
    public function getLockedSince()
    {
        return new DateTime($this->locked_since);
    }

    /**
     * @param DateTime/string $locked_since
     */
    public function setLockedSince($locked_since)
    {
        if ($locked_since instanceof DateTime) {
            $locked_since = $locked_since->format('Y-m-d H:i:s');
        }

        $this->locked_since = $locked_since;
    }

    /**
     * @return DateTime
     */
    public function getValidUntil()
    {
        if (empty($this->valid_until)) {
            return new DateTime('0000-00-00');
        }

        return new DateTime($this->valid_until);
    }

    /**
     * @param DateTime/string $validUntil
     */
    public function setValidUntil($valid_until)
    {
        if ($valid_until instanceof DateTime) {
            $valid_until = $valid_until->format('Y-m-d H:i:s');
        }

        $this->valid_until = $valid_until;
    }

}