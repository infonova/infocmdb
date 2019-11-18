<?php
/**
 * Config Util for reading Ini-Config files in the Zend Framework.
 * Tested in Zend 1.8 - 1.12.
 * This class uses the standard Zend_Config_Ini to read the config file but simplifies the use for you:
 *  # it has a try/catch block to prevent uncaught errors in case config file could not be read
 *  # it typecasts values as you need
 *  # if the config file could not be read, the class returns the fallback value you want to have set instead
 *  This class was created out of my frustration with having to check every single value I want to read from a config file
 * Author: Jonathan Trummer (Github Gist: https://gist.github.com/JonathanTru/a01158b084e591316d3bc56c9dc35833)
 *
 * # example call:
 * ###############
 * $importConfig    = new Util_Config('import.ini', APPLICATION_ENV);
 * $max_count       = $importConfig->getValue('file.import.rotation.default.max_count', 1000, Util_Config::INT);
 * $queue           = $importConfig->getValue('file->import->queue', "queue/");
 *
 * ################################################
 *
 * # if you need to check for errors that occured when using this class:
 * # use the getErrorMessages() function to get an array of all errors (returns null if no error occurred)
 * $errorMsgs       = $importConfig->getErrorMessages();
 * if (isset($errorMsgs)) $this->logger->log(json_encode($errorMsgs), Zend_Log::ERR);
 *
 *
 */

class Util_Config
{
    protected $_config = null;
    protected $_basePath;
    protected $_values = null;

    protected $messages = array();

    // consts for getValues() Parameter $dataType
    CONST BOOL   = 'bool';
    CONST STRING = 'string';
    CONST INT    = 'int';
    CONST ARR    = 'array';

    /**
     * @param string $configPath config file relative to $basePath
     * @param mixed  $section    [optional] section that should be loaded (doc: Zend_Config_Ini)
     * @param mixed  $options    [optional] options for loading the file (doc: Zend_Config_Ini)
     * @param string $basePath   [optional] base Path of config folder, default is <b>APPLICATION_PATH . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR </b>
     */
    public function __construct($configPath, $section = null, $options = false, $basePath = null)
    {

        // set default folder containing config file
        $this->_basePath = APPLICATION_PATH . DIRECTORY_SEPARATOR . 'configs' . DIRECTORY_SEPARATOR;
        // if user passes a $basePath to constructor, set $this->_basePath to $basePath
        if (isset($basePath)) {
            $this->_basePath = $basePath;
        }
        // try to load the config file
        try {
            $this->_config = new Zend_Config_Ini($this->_basePath . DIRECTORY_SEPARATOR . $configPath, $section, $options);
        } catch (Exception $ex) {
            // error reading config file -> add message to $messages, set $_config to null
            $this->messages['errors'][] = "Error reading config file " . $this->_basePath . $configPath;
            $this->messages['errors'][] = "Arguments passed to constructor: " . implode(', ', func_get_args());
            $this->_config              = null;
        }
        // if config is set (file successfully loaded) -> get values as array and set to $_values;
        if (isset($this->_config)) {
            $this->_values               = $this->_config->toArray();
            $this->messages['success'][] = "Successfully loaded config file " . $this->_basePath . $configPath . " Arguments: " . implode(', ', func_get_args());
        }

    }

    /**
     *
     * @param string $identifier either in format 'database->adapter' OR 'database.adapter'
     * @param mixed  $default    default value to be set if config value cannot be retrieved
     * @param string $dataType   [optional] set dataType -> performs operations on value (eg cast to int, cast to bool) use CONST from this class as parameter! default: Util_Config::STRING
     *
     * @return mixed if exists and valid: <b>Config-Value</b> else: <b>$default</b>
     */
    public function getValue($identifier, $default, $dataType = self::STRING)
    {
        // $_values is not set || is not array -> return $default
        if (!isset($this->_values) || !is_array($this->_values)) {
            $this->messages['warn'][] = "Error reading Config File -> using default for " . $identifier;
            return $default;
        }

        // choose delimiter for $identifier, either '->' (usually used when using Zend_Config_Ini)
        // or '.' if path is simply copied from ini File
        $delimiter = strpos($identifier, '->') ? '->' : '.';

        // explode the identifier using $delimiter
        $keys = explode($delimiter, $identifier);
        // make copy of $_values
        $val = $this->_values;

        // iterate through each $keys
        foreach ($keys as $key) {
            // key exists -> access the key and reset $val
            if (is_array($val) && array_key_exists($key, $val)) {
                $val = $val[$key];
            } else {
                $this->messages['warn'][] = "There is no config value -> using default for " . $identifier;
                // key does not exist -> return $default
                return $default;
            }
        }

        // manipulate value based on dataType
        switch ($dataType) {
            case self::INT:
                // if dataType is int, cast $val to int (otherwise would be string)
                $val = (int)$val;
                break;
            case self::BOOL:
                // if dataType is bool
                // neccessary, because bools in ini files are parsed to '1' ( = true, on, yes) or '' ( = false, off, no, none)
                // see: http://php.net/parse_ini_file
                if ($val === '1') {
                    $val = true;
                } else {
                    $val = false;
                }
                break;
            case self::STRING:
                $val = (string)$val;
                break;
        }
        // empty() evaluates false as empty. but in case of datatype bool we actually want the value 'false'
        // workaround: if datatype is bool AND the value is false -> return the value (=false) right away
        if (($dataType === self::BOOL) && ($val === false)) {
            return false;
        }
        // $val is empty -> return $default
        if (empty($val)) {
            $this->messages['warn'][] = "Value is empty -> using default for " . $identifier;
            return $default;
        }

        return $val;
    }

    /**
     * get all messages
     * eg: successfully reading config file, error reading config file
     *
     * @return array messages
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * get all error messages
     * eg: error reading config file
     *
     * @return array|null
     */
    public function getErrorMessages()
    {
        if (array_key_exists('errors', $this->messages)) {
            return $this->messages['errors'];
        }
        return null;
    }

    /**
     * get all warnings
     * eg: value in config file is empty,
     *
     * @return array|null
     */
    public function getWarnMessages()
    {
        if (array_key_exists('warn', $this->messages)) {
            return $this->messages['warn'];
        }
        return null;
    }

    /**
     * returns whole config file as array |
     * returns null if config file empty / could not be parsed
     *
     * @return mixed
     */
    public function getValues()
    {
        return $this->_values;
    }

    /**
     * get loaded config (returns null if config could not be loaded)
     * -> this is the same as calling Zend_Config_Ini() yourself, except that you don't need to worry about a try/catch block
     *
     * @return Zend_Config_Ini|null
     */
    public function getConfig()
    {
        return $this->_config;
    }

    /**
     * Convert string into lower camelcase
     * @param        $string    string to convert
     * @param string $delimiter delimiter for words
     *
     * @return string lower camelcase string
     */
    public static function camelize($string, $delimiter = '_')
    {
        $words     = explode($delimiter, $string);
        $words     = array_map('ucfirst', $words);
        $newString = implode('', $words);
        $newString = lcfirst($newString);

        return $newString;
    }
}
