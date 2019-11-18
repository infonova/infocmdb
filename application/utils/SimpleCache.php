<?php

class Util_SimpleCache
{
    protected $_cache = array();


    public function get($group, $name)
    {

        if (isset($this->_cache[$group]) && isset($this->_cache[$group][$name])) {
            return $this->_cache[$group][$name];
        }

        return false;
    }

    public function set($group, $name, $value)
    {

        if (!isset($this->_cache[$group])) {
            $this->_cache[$group] = array();
        }
        $this->_cache[$group][$name] = $value;

        return true;
    }

    /* NOT USED BUT MAY BE USEFULL
    public function memoizeMethod() {
        $args = func_get_args();
        $key = serialize($args);
        $functionName = array_pop($args);

        $value = $this->get('method', $key);
        if($value === false) {
            $value = call_user_func($functionName, $args);
            $this->set('method', $key, $value);
        }

        return $value;
    }
    */


}

