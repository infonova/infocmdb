<?php

class MenuResources
{
    public static function getResourceIds($menuId)
    {
        $menu_resources     = array();
        $menu_resources[0]  = array(601, 1001, 1003, 4101, 4104); // abstract
        $menu_resources[1]  = array(303, 311, 2001, 2002, 2003, 2004); // create ci (incl relation)
        $menu_resources[2]  = array(2301); // search
        $menu_resources[3]  = array(2601, 2602, 2603, 2604, 2605, 2606); // user
        $menu_resources[4]  = array(1901, 1902, 1903, 1904, 1905); //project
        $menu_resources[5]  = array(2201, 2202, 2203, 2204, 2205);
        $menu_resources[6]  = array(2501, 2502, 2503, 2504); //theme
        $menu_resources[7]  = array(2701, 2702, 2703, 2704); //view type
        $menu_resources[8]  = array(401, 402, 403, 404, 405, 406); // citype
        $menu_resources[9]  = array(2801, 2802, 2803, 2804, 2001, 2002, 2003, 2004); // relation type + relation edit
        $menu_resources[10] = array(101, 102, 103, 104, 105, 106, 107, 108, 109, 110, 111); // attributes
        $menu_resources[18] = array(); // history
        $menu_resources[19] = array(301, 302, 304, 305, 306, 307, 308, 309, 310, 2001); // browse ci
        $menu_resources[20] = array(201, 202, 203); // auto discovery
        $menu_resources[21] = array(2401, 2402, 2403); // searchlist
        $menu_resources[22] = array(2901, 2902, 2904); //favourites
        $menu_resources[23] = array(3101, 3102, 3103, 3104, 3201, 3202, 3203, 3204); // mail config
        $menu_resources[24] = array(8001, 8002, 8003, 8004); // mail_import
        $menu_resources[25] = array(701, 702, 703, 704); // customization
        $menu_resources[26] = array(2101, 2102, 2103, 2104); // reporting
        $menu_resources[27] = array(501, 502, 503); // config
        $menu_resources[28] = array(1101, 1102, 1104); //fileimport
        $menu_resources[29] = array(); // map
        $menu_resources[30] = array(3001, 3002, 3003, 3004); // cql interface
        $menu_resources[31] = array(3401, 3402, 3403, 3404); // dashboard
        $menu_resources[32] = array(); // translation
        $menu_resources[33] = array(3301, 3302, 3303, 3304); // validation
        $menu_resources[34] = array(3801, 3802); // menu
        $menu_resources[35] = array(3901); // logs
        $menu_resources[36] = array(3601, 3602, 3603, 3604); // workflows
        $menu_resources[37] = array(); // visualization
        $menu_resources[38] = array(4001, 4002); // ticket
        $menu_resources[39] = array(3701); // events
        $menu_resources[40] = array(4101, 4102); // attributetypes
        $menu_resources[41] = array(1501, 1502, 1503, 1504); // announcements
        $menu_resources[42] = array(9001, 9002, 9003, 9004); // administration
        $menu_resources[43] = array(901); // file upload

        if (isset($menu_resources[$menuId])) {
            return $menu_resources[$menuId];
        }
        return array();
    }

    public static function getControllerActions()
    {
        $front = Zend_Controller_Front::getInstance();
        $list  = array();

        foreach ($front->getControllerDirectory() as $module => $path) {
            foreach (scandir($path) as $file) {
                if (strstr($file, "Controller.php") !== false) {
                    include_once $path . DIRECTORY_SEPARATOR . $file;
                    foreach (get_declared_classes() as $class) {
                        if (is_subclass_of($class, 'Zend_Controller_Action')) {
                            $controller = substr($class, 0, strpos($class, "Controller"));
                            $controller = str_replace(ucfirst($module) . "_", '', $controller);
                            $controller = strtolower($controller);
                            $actions    = array();

                            foreach (get_class_methods($class) as $action) {

                                if (strstr($action, "Action") !== false) {
                                    $actions[] = substr($action, 0, strpos($action, "Action"));
                                }
                            }
                        }
                    }

                    $list[$module][$controller] = $actions;
                }
            }
        }

        return $list;
    }

    public static function printControllerActions()
    {
        $actions = self::getControllerActions();

        echo "<pre>";

        foreach ($actions as $moduleName => $controllers) {
            foreach ($controllers as $controllerName => $actions) {
                foreach ($actions as $actionName) {
                    echo $moduleName . "/" . $controllerName . '::' . $actionName . "\n";
                }
            }
        }

        echo "</pre>";
    }

    /**
     * @param string $moduleName     name of module (folder)
     * @param string $controllerName name of controller (in url)
     * @param string $actionName     name of action (in url)
     * @return array resource ids the user needs permission for
     */
    public static function getResourceIdsForAction(string $identifier)
    {

        /*
         * Meaning of ID's:
         *      no id - ALLOW for all users
         *      -1    - DISALLOW for all users
         *
         *      last two digits:
         *          01 - GET
         *          02 - CREATE
         *          03 - UPDATE
         *          04 - DELETE
         *          05 - 1:n table handling
         *          07 - EXPORT
         *          11 - m:n table handling
         */

        $mappingCmdb = array(
            // common actions
            'cmdb/error::csrfForbidden'     => array(),
            'cmdb/error::error'             => array(),
            'cmdb/index::index'             => array(),
            'cmdb/index::maintenance'       => array(),
            'cmdb/favourites::add'          => array(),
            'cmdb/favourites::index'        => array(),
            'cmdb/favourites::remove'       => array(),
            'cmdb/admin::disable'           => array(),
            'cmdb/admin::enable'            => array(),
            'cmdb/project::change'          => array(),
            'cmdb/user::refresh'            => array(),
            'cmdb/user::enable-tfa'          => array(),
            'cmdb/user::updateusersettings' => array(),
            'cmdb/user::usersettings'       => array(),
            'cmdb/announcement::display'    => array(),

            'cmdb/admin::killsession' => array(9001, 9004),
            'cmdb/admin::session'     => array(9001),
            'cmdb/log::detail'        => array(9001),
            'cmdb/log::index'         => array(9001),

            'cmdb/announcement::create'       => array(1501, 1502),
            'cmdb/announcement::delete'       => array(1501, 1504),
            'cmdb/announcement::edit'         => array(1501, 1503),
            'cmdb/announcement::index'        => array(1501),
            'cmdb/announcement::itemsperpage' => array(1501),

            'cmdb/attribute::activate'              => array(101, 103),
            'cmdb/attribute::activateoption'        => array(101, 103),
            'cmdb/attribute::addoption'             => array(101, 103),
            'cmdb/attribute::assigncitypeattribute' => array(101, 103),
            'cmdb/attribute::attributetypehint'     => array(101),
            'cmdb/attribute::citypeattribute'       => array(-1),
            'cmdb/attribute::create'                => array(101, 102),
            'cmdb/attribute::delete'                => array(101, 104),
            'cmdb/attribute::detail'                => array(101),
            'cmdb/attribute::edit'                  => array(101, 103),
            'cmdb/attribute::index'                 => array(101),
            'cmdb/attribute::individualwizardtab'   => array(101, 103),
            'cmdb/attribute::itemsperpage'          => array(101),
            'cmdb/attribute::mail'                  => array(-1),
            'cmdb/attribute::optionwizard'          => array(101, 103),
            'cmdb/attribute::ordercitypeattribute'  => array(101, 103),
            'cmdb/attribute::removemail'            => array(-1),
            'cmdb/attribute::removeoption'          => array(101, 103),

            'cmdb/attributegroup::activate'     => array(2701, 2703),
            'cmdb/attributegroup::create'       => array(2701, 2702),
            'cmdb/attributegroup::delete'       => array(2701, 2704),
            'cmdb/attributegroup::detail'       => array(2701),
            'cmdb/attributegroup::edit'         => array(2701, 2703),
            'cmdb/attributegroup::index'        => array(2701),
            'cmdb/attributegroup::itemsperpage' => array(2701),

            'cmdb/ci::autocompleteattributeid'   => array(301, 303),
            'cmdb/ci::autocompletecitype'        => array(301, 303),
            'cmdb/ci::autocompletemultiselect'   => array(301, 303),
            'cmdb/ci::changecitype'              => array(301, 303),
            'cmdb/ci::checkdelete'               => array(301, 304),
            'cmdb/ci::checkuniqueinput'          => array(301, 303),
            'cmdb/ci::color'                     => array(301),
            'cmdb/ci::create'                    => array(301),
            'cmdb/ci::delete'                    => array(301, 304),
            'cmdb/ci::destroysession'            => array(301),
            'cmdb/ci::detail'                    => array(301),
            'cmdb/ci::duplicate'                 => array(301, 302),
            'cmdb/ci::edit'                      => array(301, 303),
            'cmdb/ci::export'                    => array(301, 307),
            'cmdb/ci::historydetail'             => array(301, 1001),
            'cmdb/ci::historyprint'              => array(301, 1001),
            'cmdb/ci::index'                     => array(301),
            'cmdb/ci::itemsperpage'              => array(301),
            'cmdb/ci::pdfexport'                 => array(301),
            'cmdb/ci::print'                     => array(301),
            'cmdb/ci::project'                   => array(301, 303),
            'cmdb/ci::refreshlock'               => array(301, 303),
            'cmdb/ci::removeattribute'           => array(301, 303),
            'cmdb/ci::singleedit'                => array(301, 303),
            'cmdb/ci::user'                      => array(301, 303),
            'cmdb/attribute::addattributeform'   => array(301, 303),
            'cmdb/attribute::autocomplete'       => array(301, 303),
            'cmdb/attribute::autocompleteactive' => array(301),
            'cmdb/console::executionscript'      => array(301, 303),
            'cmdb/download::ci'                  => array(301),
            'cmdb/history::ci'                   => array(301, 1001),
            'cmdb/history::cidetail'             => array(301),
            'cmdb/history::index'                => array(301, 1001),
            'cmdb/history::itemsperpage'         => array(301, 1001),
            'cmdb/history::restore'              => array(301, 1001, 1003),
            'cmdb/history::restoreattribute'     => array(301, 1001, 1003),
            'cmdb/fileupload::ciattachment'      => array(301, 303),

            'cmdb/citype::activate'                   => array(401, 403),
            'cmdb/citype::chooseicon'                 => array(401, 403),
            'cmdb/citype::create'                     => array(401, 402),
            'cmdb/citype::delete'                     => array(401, 404),
            'cmdb/citype::detail'                     => array(401),
            'cmdb/citype::edit'                       => array(401, 403),
            'cmdb/citype::index'                      => array(401),
            'cmdb/citype::itemsperpage'               => array(401),
            'cmdb/citype::removeimage'                => array(401, 403),
            'cmdb/citype::updateformforparent'        => array(401, 403),
            'cmdb/citype::updatepersistentattributes' => array(401, 403),

            'cmdb/fileimport::ajaximport' => array(1101, 1102),
            'cmdb/fileimport::delete'     => array(1101, 1104),
            'cmdb/fileimport::deletefile' => array(1101, 1104),
            'cmdb/fileimport::errorcsv'   => array(1101),
            'cmdb/fileimport::index'      => array(1101),
            'cmdb/fileimport::log'        => array(1101),
            'cmdb/fileimport::queue'      => array(1101),
            'cmdb/fileimport::result'     => array(1101, 1102),
            'cmdb/fileimport::upload'     => array(1101, 1102),
            'cmdb/download::fileimport'   => array(1101),

            'cmdb/fileupload::edit'          => array(-1),
            'cmdb/fileupload::index'         => array(-1),
            'cmdb/fileupload::recreateindex' => array(901),
            'cmdb/fileupload::unlinkfile'    => array(-1),

            'cmdb/login::change'  => array(),
            'cmdb/login::index'   => array(),
            'cmdb/login::login'   => array(),
            'cmdb/login::logout'  => array(),
            'cmdb/login::recover' => array(),

            'cmdb/mail::autocompletemultiselect' => array(3201, 3203),
            'cmdb/mail::create'                  => array(3201, 3202),
            'cmdb/mail::delete'                  => array(3201, 3204),
            'cmdb/mail::edit'                    => array(3201, 3203),
            'cmdb/mail::index'                   => array(3201),
            'cmdb/mail::testmail'                => array(3201),
            'cmdb/mail::view'                    => array(3201),

            'cmdb/mailimport::autocomplete'         => array(8001, 8003),
            'cmdb/mailimport::changecronjob'        => array(8001, 8003),
            'cmdb/mailimport::create'               => array(8001, 8002),
            'cmdb/mailimport::delete'               => array(8001, 8004),
            'cmdb/mailimport::edit'                 => array(8001, 8002),
            'cmdb/mailimport::editcronjob'          => array(8001, 8003),
            'cmdb/mailimport::index'                => array(8001),
            'cmdb/mailimport::retrievemailmessages' => array(8001),

            'cmdb/menu::activate'   => array(3801, 3802),
            'cmdb/menu::deactivate' => array(3801, 3802),
            'cmdb/menu::detail'     => array(3801),
            'cmdb/menu::edit'       => array(3801, 3802),
            'cmdb/menu::index'      => array(3801),

            'cmdb/project::activate'     => array(1901, 1903),
            'cmdb/project::create'       => array(1901, 1902),
            'cmdb/project::delete'       => array(1901, 1904),
            'cmdb/project::edit'         => array(1901, 1903),
            'cmdb/project::index'        => array(1901),
            'cmdb/project::itemsperpage' => array(1901),

            'cmdb/query::create'        => array(3001, 3002),
            'cmdb/query::delete'        => array(3001, 3004),
            'cmdb/query::detail'        => array(3001),
            'cmdb/query::edit'          => array(3001, 3003),
            'cmdb/query::error'         => array(3001),
            'cmdb/query::index'         => array(3001),
            'cmdb/query::test'          => array(3001, 3003),
            'cmdb/query::teststatement' => array(3001, 3003),

            'cmdb/relation::addrelation'      => array(2001, 2002),
            'cmdb/relation::create'           => array(2001, 2002),
            'cmdb/relation::delete'           => array(2001, 2004),
            'cmdb/relation::deleteassignment' => array(2001, 2002),
            'cmdb/relation::delvis'           => array(-1),
            'cmdb/relation::detail'           => array(2001),
            'cmdb/relation::getcolor'         => array(2001),
            'cmdb/relation::index'            => array(2001),
            'cmdb/relation::visualization'    => array(-1),
            'cmdb/relation::visualize'        => array(2001),

            'cmdb/relationtype::activate'     => array(2801, 2803),
            'cmdb/relationtype::create'       => array(2801, 2802),
            'cmdb/relationtype::delete'       => array(2801, 2804),
            'cmdb/relationtype::detail'       => array(2801),
            'cmdb/relationtype::edit'         => array(2801, 2803),
            'cmdb/relationtype::index'        => array(2801),
            'cmdb/relationtype::itemsperpage' => array(2801),

            'cmdb/reporting::activate'      => array(2101, 2103),
            'cmdb/reporting::create'        => array(2101, 2102),
            'cmdb/reporting::delete'        => array(2101, 2104),
            'cmdb/reporting::detail'        => array(2101),
            'cmdb/reporting::edit'          => array(2101, 2103),
            'cmdb/reporting::execute'       => array(2101),
            'cmdb/reporting::index'         => array(2101),
            'cmdb/reporting::input'         => array(2101, 2103),
            'cmdb/reporting::inputwizard'   => array(2101, 2103),
            'cmdb/reporting::itemsperpage'  => array(2101),
            'cmdb/reporting::removearchive' => array(2101, 2104),
            'cmdb/download::report'         => array(2101),

            'cmdb/role::activate'     => array(2201, 2203),
            'cmdb/role::create'       => array(2201, 2202),
            'cmdb/role::delete'       => array(2201, 2204),
            'cmdb/role::detail'       => array(2201),
            'cmdb/role::edit'         => array(2201, 2203),
            'cmdb/role::index'        => array(2201),
            'cmdb/role::itemsperpage' => array(2201),

            'cmdb/scheduler::index'    => array(),
            'cmdb/scheduler::kill'     => array(),
            'cmdb/scheduler::listen'   => array(),
            'cmdb/scheduler::process'  => array(),
            'cmdb/scheduler::sprocess' => array(),

            'cmdb/search::filesearch' => array(2301),
            'cmdb/search::index'      => array(2301),
            'cmdb/search::searchajax' => array(2301),

            'cmdb/theme::activate'     => array(2601, 2603),
            'cmdb/theme::create'       => array(2601, 2602),
            'cmdb/theme::delete'       => array(2601, 2604),
            'cmdb/theme::detail'       => array(2601),
            'cmdb/theme::edit'         => array(2601, 2603),
            'cmdb/theme::index'        => array(2601),
            'cmdb/theme::itemsperpage' => array(2601),

            'cmdb/user::activate'     => array(2601, 2603),
            'cmdb/user::create'       => array(2601, 2602),
            'cmdb/user::delete'       => array(2601, 2604),
            'cmdb/user::detail'       => array(2601),
            'cmdb/user::disable-tfa'   => array(2601, 2603),
            'cmdb/user::edit'         => array(2601, 2603),
            'cmdb/user::index'        => array(2601),
            'cmdb/user::itemsperpage' => array(2601),

            'cmdb/validation::ajaxcidetail' => array(3301),
            'cmdb/validation::delete'       => array(3301, 3304),
            'cmdb/validation::detail'       => array(3301),
            'cmdb/validation::index'        => array(3301),
            'cmdb/validation::match'        => array(3301, 3303),

            'cmdb/workflow::activate'                 => array(3601, 3603),
            'cmdb/workflow::cancel'                   => array(3601, 3603),
            'cmdb/workflow::continue'                 => array(3601, 3603),
            'cmdb/workflow::create'                   => array(3601, 3603),
            'cmdb/workflow::createvisimage'           => array(3601),
            'cmdb/workflow::cronform'                 => array(3601, 3603),
            'cmdb/workflow::delete'                   => array(3601, 3604),
            'cmdb/workflow::detail'                   => array(3601),
            'cmdb/workflow::edit'                     => array(3601, 3603),
            'cmdb/workflow::execute'                  => array(3601, 3603),
            'cmdb/workflow::fileimporttriggermapping' => array(3601, 3603),
            'cmdb/workflow::index'                    => array(3601),
            'cmdb/workflow::instance'                 => array(3601),
            'cmdb/workflow::itemsperpage'             => array(3601),
            'cmdb/workflow::mapping'                  => array(3601, 3603),
            'cmdb/workflow::mappingform'              => array(3601, 3603),
            'cmdb/workflow::placedetail'              => array(3601),
            'cmdb/workflow::rebuild'                  => array(3601, 3603),
            'cmdb/workflow::retry'                    => array(3601, 3603),
            'cmdb/workflow::scripttemplate'           => array(3601, 3603),
            'cmdb/workflow::solve'                    => array(3601, 3603),
            'cmdb/workflow::solvecase'                => array(3601, 3603),
            'cmdb/workflow::suspend'                  => array(3601, 3603),
            'cmdb/workflow::transitiondetail'         => array(3601),
            'cmdb/workflow::validatescript'           => array(3601, 3603),
            'cmdb/workflow::wakeup'                   => array(3601, 3603),

            /** @deprecated  not used / not working */
            'cmdb/admin::index'                       => array(-1),
            'cmdb/config::edit'                       => array(-1),
            'cmdb/config::index'                      => array(-1),
            'cmdb/console::index'                     => array(-1),
            'cmdb/dashboard::calendar'                => array(-1),
            'cmdb/dashboard::changePrio'              => array(-1),
            'cmdb/dashboard::complete'                => array(-1),
            'cmdb/dashboard::delete'                  => array(-1),
            'cmdb/dashboard::index'                   => array(-1),
            'cmdb/download::index'                    => array(-1),
            'cmdb/event::create'                      => array(-1),
            'cmdb/event::index'                       => array(-1),
            'cmdb/import::config'                     => array(-1),
            'cmdb/import::delete'                     => array(-1),
            'cmdb/import::failed'                     => array(-1),
            'cmdb/import::import'                     => array(-1),
            'cmdb/import::importattribute'            => array(-1),
            'cmdb/import::index'                      => array(-1),
            'cmdb/import::queue'                      => array(-1),
            'cmdb/import::restart'                    => array(-1),
            'cmdb/import::success'                    => array(-1),
            'cmdb/installer::index'                   => array(-1),
            'cmdb/attributetype::activate'            => array(-1),
            'cmdb/attributetype::deactivate'          => array(-1),
            'cmdb/attributetype::detail'              => array(-1),
            'cmdb/attributetype::edit'                => array(-1),
            'cmdb/attributetype::index'               => array(-1),
            'cmdb/map::index'                         => array(-1),
            'cmdb/message::create'                    => array(-1),
            'cmdb/message::delete'                    => array(-1),
            'cmdb/message::index'                     => array(-1),
            'cmdb/message::outgoing'                  => array(-1),
            'cmdb/message::read'                      => array(-1),
            'cmdb/statistics::index'                  => array(-1),
            'cmdb/template::create'                   => array(-1),
            'cmdb/template::delete'                   => array(-1),
            'cmdb/template::edit'                     => array(-1),
            'cmdb/template::index'                    => array(-1),
            'cmdb/translation::edit'                  => array(-1),
            'cmdb/translation::index'                 => array(-1),
            'cmdb/searchlist::detail'                 => array(-1),
            'cmdb/searchlist::index'                  => array(-1),
            'cmdb/workflow::visworkflow'              => array(-1),
        );

        $mappingApi = array(
            'api/adapter::index'       => array(),
            'api/adapter::head'        => array(),
            'api/adapter::get'         => array(),
            'api/adapter::put'         => array(),
            'api/adapter::list'        => array(),
            'api/adapter::post'        => array(),
            'api/adapter::delete'      => array(),
            'api/ci::index'            => array(),
            'api/ci::get'              => array(),
            'api/ci::new'              => array(),
            'api/ci::post'             => array(),
            'api/ci::put'              => array(),
            'api/ci::delete'           => array(),
            'api/ci::head'             => array(),
            'api/ci::list'             => array(),
            'api/cirecursive::index'   => array(),
            'api/cirecursive::get'     => array(),
            'api/cirecursive::post'    => array(),
            'api/cirecursive::put'     => array(),
            'api/cirecursive::delete'  => array(),
            'api/cirecursive::head'    => array(),
            'api/cirecursive::list'    => array(),
            'api/citype::index'        => array(),
            'api/citype::get'          => array(),
            'api/citype::head'         => array(),
            'api/citype::list'         => array(),
            'api/citype::post'         => array(),
            'api/citype::put'          => array(),
            'api/citype::delete'       => array(),
            'api/error::error'         => array(),
            'api/error::access'        => array(),
            'api/error::timeout'       => array(),
            'api/history::index'       => array(),
            'api/history::get'         => array(),
            'api/history::head'        => array(),
            'api/history::list'        => array(),
            'api/history::post'        => array(),
            'api/history::put'         => array(),
            'api/history::delete'      => array(),
            'api/login::get'           => array(),
            'api/login::index'         => array(),
            'api/login::head'          => array(),
            'api/login::list'          => array(),
            'api/login::post'          => array(),
            'api/login::put'           => array(),
            'api/login::delete'        => array(),
            'api/notification::index'  => array(),
            'api/notification::get'    => array(),
            'api/notification::put'    => array(),
            'api/notification::head'   => array(),
            'api/notification::list'   => array(),
            'api/notification::post'   => array(),
            'api/notification::delete' => array(),
        );

        $mappingApiV2 = array(
            'apiV2/attribute::index'  => array(101),
            // 'apiV2/attribute::head'    => array(), // not implemented
            'apiV2/attribute::get'    => array(101),
            'apiV2/attribute::post'   => array(101, 102),
            'apiV2/attribute::put'    => array(101, 103),
            'apiV2/attribute::delete' => array(101, 104),

            'apiV2/auth::token'        => array(),
            'apiV2/auth::refresh'      => array(),
            // 'apiV2/auth::index'        => array(), // not implemented
            // 'apiV2/auth::head'         => array(), // not implemented
            // 'apiV2/auth::get'          => array(), // not implemented
            // 'apiV2/auth::post'         => array(), // not implemented
            // 'apiV2/auth::put'          => array(), // not implemented
            // 'apiV2/auth::delete'       => array(), // not implemented

            'apiV2/ci::index'  => array(301),
            // 'apiV2/ci::head'           => array(), // not implemented
            'apiV2/ci::get'    => array(301),
            'apiV2/ci::post'   => array(301, 302),
            'apiV2/ci::put'    => array(301, 303),
            'apiV2/ci::delete' => array(301, 304),

            'apiV2/citype::index'  => array(401),
            // 'apiV2/citype::head'           => array(), // not implemented
            'apiV2/citype::get'    => array(401),
            'apiV2/citype::post'   => array(401, 402),
            'apiV2/citype::put'    => array(401, 403),
            'apiV2/citype::delete' => array(401, 404),

            'apiV2/index::index'     => array(), // swagger
            'apiV2/doc::index'       => array(), // json for swagger

            // 'apiV2/fileupload::index'  => array(), // not implemented
            // 'apiV2/fileupload::head'   => array(), // not implemented
            // 'apiV2/fileupload::get'    => array(), // not implemented
            'apiV2/fileupload::post' => array(901),
            // 'apiV2/fileupload::put'    => array(), // not implemented
            // 'apiV2/fileupload::delete' => array(), // not implemented


            'apiV2/query::put' => array(3001),
            // 'apiV2/query::index'       => array(), // not implemented
            // 'apiV2/query::head'        => array(), // not implemented
            // 'apiV2/query::get'         => array(), // not implemented
            // 'apiV2/query::post'        => array(), // not implemented
            // 'apiV2/query::delete'      => array(), // not implemented
        );


        $mapping = array_merge($mappingApi, $mappingApiV2, $mappingCmdb);

        $resourceIds = $mapping[$identifier] ?? array(-1);

        return $resourceIds;
    }

}
