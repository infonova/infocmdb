<?php

class Util_SessionSaveHandler extends Zend_Session_SaveHandler_DbTable
{
    /**
     * Write session data
     *
     * @param string $id
     * @param string $data
     * @return boolean
     */
    public function write($id, $data)
    {
        $bootstrap = Zend_Controller_Front::getInstance()->getParam('bootstrap');
        $options   = $bootstrap->getOptions();
        $isConsole = $options['isConsole'] ?? false;

        if ($isConsole === true) {
            return true;
        }

        $return = false;

        $sessionData = self::unserialize($data);
        $dbData      = array(
            $this->_modifiedColumn     => time(),
            $this->_dataColumn         => (string)$data,
            Db_UserSession::USER_ID    => $sessionData["UserStore"]["id"] ?? null,
            Db_UserSession::IP_ADDRESS => $sessionData["UserStore"]["ipAddress"] ?? null,
        );

        $rows = call_user_func_array(array(&$this, 'find'), $this->_getPrimary($id));

        if (count($rows)) {
            $dbData[$this->_lifetimeColumn] = $this->_getLifetime($rows->current());

            if ($this->update($dbData, $this->_getPrimary($id, self::PRIMARY_TYPE_WHERECLAUSE))) {
                $return = true;
            }
        } else {
            $dbData[$this->_lifetimeColumn] = $this->_lifetime;

            if ($this->insert(array_merge($this->_getPrimary($id, self::PRIMARY_TYPE_ASSOC), $dbData))) {
                $return = true;
            }
        }

        return $return;
    }

    private static function unserializePhp($sessionData)
    {
        $return_data = array();
        $offset      = 0;
        while ($offset < strlen($sessionData)) {
            if (!strstr(substr($sessionData, $offset), "|")) {
                throw new Exception("invalid data, remaining: " . substr($sessionData, $offset));
            }
            $pos                   = strpos($sessionData, "|", $offset);
            $num                   = $pos - $offset;
            $varname               = substr($sessionData, $offset, $num);
            $offset                += $num + 1;
            $data                  = unserialize(substr($sessionData, $offset));
            $return_data[$varname] = $data;
            $offset                += strlen(serialize($data));
        }
        return $return_data;
    }

    private static function unserializePhpBinary($sessionData)
    {
        $return_data = array();
        $offset      = 0;
        while ($offset < strlen($sessionData)) {
            $num                   = ord($sessionData[$offset]);
            $offset                += 1;
            $varname               = substr($sessionData, $offset, $num);
            $offset                += $num;
            $data                  = unserialize(substr($sessionData, $offset));
            $return_data[$varname] = $data;
            $offset                += strlen(serialize($data));
        }
        return $return_data;
    }

    public static function unserialize($sessionData)
    {
        $method = ini_get("session.serialize_handler");
        switch ($method) {
            case "php":
                return self::unserializePhp($sessionData);
                break;
            case "php_binary":
                return self::unserializePhpBinary($sessionData);
                break;
            default:
                throw new Exception("Unsupported session.serialize_handler: " . $method . ". Supported: php, php_binary");
        }
    }
}