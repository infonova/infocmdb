<?php

/**
 * use procedure based search
 * currently incomplete
 *
 *
 */
class Util_Search_Method_Original implements Util_Search_Method_Interface
{

    const ACTION_ADD      = 'add';
    const ACTION_REMOVE   = 'remove';
    const ACTION_RESTRICT = 'restrict';

    public static function search($config, $searchstring, $pid_string, $searchParameter = array(), $limit, $offset, $session = null, $history = false)
    {
        $searchDao = new Dao_Search();

        if ($session) {
            // select if valid!!
            $ret = $searchDao->getCurrentSearchSession($session);

            if ($ret && $ret[Db_SearchSession::ID])
                goto selectPos;
        }

        $session = $searchDao->getSearchSession();

        $addString      = "";
        $removeString   = "";
        $restrictString = "";

        $firstRemove   = true;
        $firstRestrict = true;

        $ciArray = array();

        $isFirst = true;
        foreach ($searchstring['value'] as $key => $search) {
            $search = trim($search);
            $action = self::ACTION_ADD;
            if ($isFirst) {
                $addString = $search;
            } else if ($searchstring['action'][$key] == '-') {
                if (!$firstRemove)
                    $removeString .= '|';

                $removeString = $search;
                $firstRemove  = false;
            } else {
                if (!$firstRestrict)
                    $removeString .= '|';

                $restrictString = $search;
                $firstRestrict  = false;
            }

            $isFirst = false;

            if ($config->search->db->ci->enabled) {
                if (is_numeric($search))
                    array_push($ciArray, $search);
            }
        }

        if ($searchParameter['relation']) {
            $searchDao->callSearchProcedure($addString, $removeString, $restrictString, $pid_string, $session, 5, $searchParameter['relation'], $history);
        } else {
            $searchDao->callSearchProcedure($addString, $removeString, $restrictString, $pid_string, $session, 5, '', $history);
        }


        foreach ($ciArray as $search) {
            $searchDao->getCiSearch($session, $search);
        }
        selectPos:
        $cnt = $searchDao->countSearchResult($session);
        $res = $searchDao->getSearchResult($session, $limit, $offset);

        return array('number' => $cnt['cnt'], 'items' => $res, 'session' => $session);
    }
}