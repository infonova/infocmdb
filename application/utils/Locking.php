<?php

class Util_Locking extends Dao_Lock
{
    /* Lock Types */
    const CI_LOCK = "ci_lock";

    /** @var Util_Config locking.ini */
    protected $config;

    /**
     * Util_Locking constructor.
     */
    public function __construct($enableMySQLBuffer = true)
    {
        parent::__construct($enableMySQLBuffer);

        $this->config = Zend_Registry::get('lockingConfig');
    }


    /**
     * Get current object as array (for database)
     *
     * @return array database row
     */
    public function getAsArray()
    {
        $array = parent::getAsArray();
        unset($array['config']);

        return $array;
    }


    /**
     * Factory
     */

    /**
     * Create instance by id of lock row
     *
     * @param int $id id of lock row
     *
     * @return Util_Locking
     */
    public static function getById($id)
    {
        $instance = new self();
        $row      = $instance->getLockRow($id);
        $instance->applyData($row);

        return $instance;
    }

    /**
     * Create instance by given data
     *
     * @param string $lockType
     * @param int    $resourceId
     *
     * @return Util_Locking
     */
    public static function getByLockTypeAndResourceId($lockType, $resourceId)
    {
        $instance = new self();
        $row      = $instance->getLockRowByResourceIdAndLockType($lockType, $resourceId);
        if (is_array($row)) {
            $instance->applyData($row);
        } else {
            $instance->setLockType($lockType);
            $instance->setResourceId($resourceId);
        }

        return $instance;
    }


    /**
     * Check if Locking is enabled in config
     *
     * @return bool
     */
    public function isEnabled()
    {
        $enabled = $this->config->getValue('lock.enabled', false, Util_Config::BOOL);
        return $enabled;
    }

    /**
     * Check if resource_id is locked
     *
     * @return bool returns true if resource has a valid lock or false if no lock exists or isn't valid anymore
     */
    public function isActive()
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if (!($this->getId())) {
            return false;
        }

        $now         = new DateTime();
        $valid_until = $this->getValidUntil();
        if ($now > $valid_until) {
            return false;
        }

        return true;
    }

    /**
     * Check if given user is holding the lock
     *
     * @param int $userId
     *
     * @return bool
     */
    public function isHeldBy($userId)
    {
        if (!$this->isEnabled()) {
            return true;
        }

        if (!$this->isActive()) {
            return false;
        }

        if ($this->getHeldBy() != $userId) {
            return false;
        }

        return true;
    }

    /**
     * Acquire lock for given user
     *
     * Will create lock if it does not exist or refreshes if exists
     *
     * @param int $userId
     *
     * @return bool returns true if there is a valid lock condition
     */
    public function acquireForUser($userId)
    {
        $id = $this->getId();

        if (!$this->isEnabled()) {
            return true;
        }

        if (empty($id)) {
            return $this->lock($userId);
        }

        if ($this->getHeldBy() == $userId) {
            return $this->refresh();
        }

        $now        = new DateTime();
        $validUntil = $this->getValidUntil();
        if ($this->getHeldBy() != $userId && $now > $validUntil) {
            return $this->handOverToUser($userId);
        }

        return false;
    }

    /**
     * Create lock for given user
     *
     * @param int $userId
     *
     * @return bool
     */
    protected function lock($userId)
    {
        if (!$this->isEnabled()) {
            return true;
        }

        $lockedSince = new DateTime();
        $validUntil  = $this->getRefreshedTime();

        $this->setLockedSince($lockedSince);
        $this->setValidUntil($validUntil);
        $this->setHeldBy($userId);
        return $this->save();
    }

    /**
     * Refresh lock
     *
     * @param mixed $seconds
     *
     * @return bool
     */
    public function refresh($seconds = null)
    {
        $id = $this->getId();

        if (!$this->isEnabled()) {
            return true;
        }

        if (empty($id)) {
            return false;
        }

        $datetime = $this->getRefreshedTime($seconds);

        // if multiple refresh requests are made within one second (e.g refresh, new tab)
        if ($this->getValidUntil() >= $datetime) {
            return true;
        }

        $this->setValidUntil($datetime);
        $result = $this->save();

        return $result;
    }

    /**
     * Get current time plus seconds defined in config or parameter
     *
     * @param mixed $seconds if defined seconds added to current time, otherwise seconds will be taken from configuration
     *
     * @return DateTime
     */
    public function getRefreshedTime($seconds = null)
    {
        if (empty($seconds)) {
            $seconds = $this->config->getValue('lock.duration', 120, Util_Config::INT);
        }

        $datetime  = new DateTime();
        $timestamp = $datetime->getTimestamp();
        $timestamp += $seconds;
        $datetime->setTimestamp($timestamp);

        return $datetime;
    }

    /**
     * Invalidate/Delete lock row
     *
     * @return bool
     */
    public function release()
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return $this->delete();
    }

    /**
     * Hand over lock to given user
     *
     * @param int $userId
     *
     * @return bool
     */
    public function handOverToUser($userId)
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return $this->lock($userId);
    }

    /**
     * Save lock row to database
     *
     * @return bool
     */
    public function save()
    {
        if (!$this->isEnabled()) {
            return true;
        }

        return parent::save();
    }
}