<?php

interface Util_Search_Method_Interface
{

    public static function search($config, $searchstring, $pid_string, $searchParameter = array(), $limit, $offset, $session = null, $history = false);
}