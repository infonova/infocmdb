<?php

class Util_Search_Method_Speedup implements Util_Search_Method_Interface
{

    const ACTION_ADD      = 'add';
    const ACTION_REMOVE   = 'remove';
    const ACTION_RESTRICT = 'restrict';

    public static function search($config, $searchstring, $pid_string, $searchParameter = array(), $limit, $offset, $session = null)
    {
        $searchDao = new Dao_Search();

        if ($session) {
            // select if valid!!
            $ret = $searchDao->getCurrentSearchSession($session);

            if ($ret && $ret[Db_SearchSession::ID])
                goto selectPos;
        }

        $session = $searchDao->getSearchSession();

        $addArray     = array();
        $addArrayTemp = array();
        $removeArray  = array();

        $typeEnabled        = $config->search->db->citype->enabled;
        $ciAttributeEnabled = $config->search->db->ciattribute->enabled;
        $attributeEnabled   = $config->search->db->attribute->enabled;
        $relationEnabled    = $config->search->db->relation->enabled;


        $isFirst = true;
        foreach ($searchstring['value'] as $key => $search) {
            $action = self::ACTION_ADD;
            if ($isFirst) {
                $action = self::ACTION_ADD;
            } else if ($searchstring['action'][$key] == '-') {
                // delete all found items
                $action = self::ACTION_REMOVE;
            } else {
                // must be +
                $action = self::ACTION_RESTRICT;
            }

            $isFirst = false;

            if ($typeEnabled) {
                $tType = Util_Thread::create(APPLICATION_PATH . "/thread" . "/search/" . $action . "/CiType.php -a " . APPLICATION_PATH . " -b  '$search' -c '$session'");
            }

            if ($ciAttributeEnabled) {
                $tCiAText = Util_Thread::create(APPLICATION_PATH . "/thread" . "/search/" . $action . "/CiAttribute.php -a " . APPLICATION_PATH . " -b  '$search' -c '$session'");
            }

            if ($attributeEnabled) {
                $tAttribute = Util_Thread::create(APPLICATION_PATH . "/thread" . "/search/" . $action . "/Attribute.php -a " . APPLICATION_PATH . " -b  '$search' -c '$session'");
            }

            if ($relationEnabled) {
                $tRelation = Util_Thread::create(APPLICATION_PATH . "/thread" . "/search/" . $action . "/Relation.php -a " . APPLICATION_PATH . " -b  '$search' -c '$session'");
            }

            if ($config->search->db->ci->enabled) {
                if (is_numeric($search))
                    $searchDao->getCiSearch($session, $search);
            }

            $tTypeRes;
            $tCiATextRes;
            $tAttributeRes;
            $tRelationRes;
            $tCiACiTypeRes;

            while (($tType && $tType->isActive())
                || ($tCiAText && $tCiAText->isActive())
                || ($tAttribute && $tAttribute->isActive())
                || ($tRelation && $tRelation->isActive())
                || ($tCiACiType && $tCiACiType->isActive())
            ) {

                if ($tType) {
                    $tTypeRes .= $tType->listen();
                }
                if ($tCiAText) {
                    $tCiATextRes .= $tCiAText->listen();
                }
                if ($tAttribute) {
                    $tAttributeRes .= $tAttribute->listen();
                }
                if ($tRelation) {
                    $tRelationRes .= $tRelation->listen();
                }
                if ($tCiACiType) {
                    $tCiACiTypeRes .= $tCiACiType->listen();
                }
            }

            if ($tType) {
                $tType->close();
            }
            if ($tCiAText) {
                $tCiAText->close();
            }
            if ($tAttribute) {
                $tAttribute->close();
            }
            if ($tRelation) {
                $tRelation->close();
            }
            if ($tCiACiType) {
                $tCiACiType->close();
            }
        }

        $searchDao->deleteForbiddenSearchResult($session, $pid_string);

        if ($searchParameter['relation']) {
            $searchDao->deleteNotMatchingRelationTypes($session, $searchParameter['relation']);
        }

        selectPos:
        $cnt = $searchDao->countSearchResult($session);
        $res = $searchDao->getSearchResult($session, $limit, $offset);

        return array('number' => $cnt['cnt'], 'items' => $res, 'session' => $session);
    }
}