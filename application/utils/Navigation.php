<?php

class Util_Navigation
{

    /**
     * @param $translator Zend_Translate
     * @param $userDto    Dto_UserDto
     * @param $projectId
     * @param $config
     *
     * @return string
     */
    public static function createNavigationTree($translator, $userDto, $projectId, $config)
    {
        $menu_root_name = "Home";

        $projectDao = new Dao_Project();

        // firts,retrieve the selected Project
        if (isset($projectId) && $projectId > 0) {
            $projectDto     = $projectDao->getProject($projectId);
            $menu_root_name = $projectDto[Db_Project::DESCRIPTION];
            unset($projectDto);
            unset($projectDao);
        }


        //             <input id='navigation-search' type='text' name='menu-search' autocomplete='off' style='width:130px; margin-left:15px'>
        // first, set the menu root node
        $menu_tree_controls    = "<div id='menutree-controls'>
            <a href=\"javascript:collapseAll();\" class=\"closeTree\" title=\"close all folders\"></a>
            <a href=\"javascript:expandAll();\" class=\"openTree\" title=\"open all folders\"></a>
        </div>";
        $menuTree              = '<div id="fancytree" class="fancytree-connectors"></div>' . $menu_tree_controls;
        $menu_tree_array       = array();
        $root_node             = self::createMenuNode("", APPLICATION_URL . 'index', $menu_root_name,
            "rootSprite", array(), "root");
        $root_node['expanded'] = true;
        array_push($menu_tree_array, $root_node);

        $menu_tree_actions = array();


        $menuDao  = new Dao_Menu();
        $menuList = $menuDao->getActiveMenusByThemeId($userDto->getThemeId());
        unset($menuDao);

        $men_1 = null;
        // folder1 - folder5
        $usermanagement = array("active" => false,
                                "data"   => self::createMenuActionNode($translator->getAdapter()->translate('usermanagement'), $translator->getAdapter()->translate('usermanagement'),
                                    "spriteNavUser", array(), "closed1 menu-action"));
        $datamodel      = array("active" => false,
                                "data"   => self::createMenuActionNode($translator->getAdapter()->translate('datamodel'), $translator->getAdapter()->translate('datamodel'),
                                    "spriteNavUser", array(), "closed2 menu-action"));
        $automation     = array("active" => false,
                                "data"   => self::createMenuActionNode($translator->getAdapter()->translate('automation'), $translator->getAdapter()->translate('automation'),
                                    "spriteNavUser", array(), "closed3 menu-action"));
        $interfaces     = array("active" => false,
                                "data"   => self::createMenuActionNode($translator->getAdapter()->translate('interfaces'), $translator->getAdapter()->translate('interfaces'),
                                    "spriteNavUser", array(), "closed4 menu-action"));
        $settings       = array("active" => false,
                                "data"   => self::createMenuActionNode($translator->getAdapter()->translate('settings'), $translator->getAdapter()->translate('settings'),
                                    "spriteNavUser", array(), "closed5 menu-action"));

        foreach ($menuList as $row) {

            switch ($row[Db_Menu::NAME]) {

                // do not show this menu actions
                case "admin":
                    break;
                case "file_upload":
                    break;

                /* Additional actions */
                case $config->search->name:
                    // class search
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "search"));
                    break;
                case $config->createCi->name:
                    // class createCi
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "createCi"));
                    break;
                case $config->attributeGroups->name:
                    // class attributeGroup
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "attributeGroup"));
                    break;
                case $config->history->name:
                    // class history
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "history"));
                    break;
                case $config->autoDiscovery->name:
                    // class autoDiscovery
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "autoDiscovery"));
                    break;
                case $config->searchList->name:
                    // class searchList
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "searchList"));
                    break;
                case $config->map->name:
                    // class map
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "map"));
                    break;
                case $config->favourites->name:
                    // class favourites
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "favourites"));
                    break;
                case $config->visualization->name:
                    // class visualization
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "visualization"));
                    break;
                // special handling for these
                case $config->browseRelations->name:
                    // class relationType
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "relationType"));
                    break;
                case $config->browseNetwork->name:
                    // class default
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "default"));
                    break;

                /* Data model */
                case $config->ciTypes->name:
                    // class ciType
                    $datamodel['active'] = true;
                    array_push($datamodel['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "ciType"));
                    break;
                case $config->attributes->name:
                    // class attribute
                    $datamodel['active'] = true;
                    array_push($datamodel['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "attribute"));
                    break;
                case $config->relationTypes->name:
                    // class relationType
                    $datamodel['active'] = true;
                    array_push($datamodel['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "relationType"));
                    break;

                case $config->attributeGroup->name:
                    // class attributeGroup
                    $datamodel['active'] = true;
                    array_push($datamodel['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "attributeGroup"));
                    break;
                case 'announcement':
                    // class announcement
                    $datamodel['active'] = true;
                    array_push($datamodel['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "announcement"));
                    break;

                /* User management */
                case $config->user->name:
                    // class user
                    $usermanagement['active'] = true;
                    array_push($usermanagement['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "user"));
                    break;
                case $config->roles->name:
                    // class role
                    $usermanagement['active'] = true;
                    array_push($usermanagement['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "role"));
                    break;
                case $config->themes->name:
                    // class theme
                    $usermanagement['active'] = true;
                    array_push($usermanagement['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "theme"));
                    break;

                case $config->projects->name:
                    // class project
                    $usermanagement['active'] = true;
                    array_push($usermanagement['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "project"));
                    break;
                /* Settings */
                case $config->config->name:
                    // class config
                    $settings['active'] = true;
                    array_push($settings['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "config"));
                    break;
                case $config->translation->name:
                    // class translation
                    $settings['active'] = true;
                    array_push($settings['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "translation"));
                    break;
                case $config->menu->name:
                    // class menu_konfiguration
                    $settings['active'] = true;
                    array_push($settings['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "menu_konfiguration"));
                    break;
                case $config->attributetype->name:
                    // class attribute_type
                    // this does not get set to active here - bug?
                    array_push($settings['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "attribute_type"));
                    break;
                case $config->logs->name:
                    // class logfiles
                    $settings['active'] = true;
                    array_push($settings['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "logfiles"));
                    break;

                /* Interfaces */
                case $config->fileImport->name:
                    // class fileImport
                    $interfaces['active'] = true;
                    array_push($interfaces['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "fileImport"));
                    break;
                case $config->mailImport->name:
                    // class mailImport
                    $interfaces['active'] = true;
                    array_push($interfaces['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "mailImport"));
                    break;
                case $config->cqlinterface->name:
                    // class cql_interface
                    $interfaces['active'] = true;
                    array_push($interfaces['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "cql_interface"));
                    break;
                case $config->notification->name:
                    // class notification
                    $interfaces['active'] = true;
                    array_push($interfaces['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "notification"));
                    break;
                case $config->validation->name:
                    // class validation
                    $interfaces['active'] = true;
                    array_push($interfaces['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "validation"));
                    break;

                /* Automation */
                case $config->reporting->name:
                    // class reporting
                    $automation['active'] = true;
                    array_push($automation['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "reporting"));
                    break;
                case $config->workflows->name:
                    // class workflows
                    $automation['active'] = true;
                    array_push($automation['data']['children'],
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME]), "spriteNavUser", null, "workflows"));
                    break;

                /* Ci List */
                case $config->browseItems->name:
                    $browse_item_parent_node =
                        self::createMenuActionNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), $row[Db_Menu::NAME],
                            "spriteCi", array(), "menu-action");

                    // select ci_types
                    $navigationDao    = new Dao_Navigation();
                    $ciTypes          = $navigationDao->getCiTypes();
                    $permittedCiTypes = $navigationDao->getPermittedCiTypeIds($userDto->getId(), $projectId);

                    // endless loop protection
                    foreach ($ciTypes as $key => $item) {
                        if ($item[Db_CiType::PARENT_CI_TYPE_ID] == $item[Db_CiType::ID]) {
                            $ciTypes[$key][Db_CiType::PARENT_CI_TYPE_ID] = '0';
                        }
                    }

                    $ciTypeHierarchy = self::buildTree($ciTypes, $permittedCiTypes);
                    $ciTypeHierarchy = self::handlePermissions($ciTypeHierarchy);
                    $ci_tree         = self::generateNodeArrayRecursive($ciTypeHierarchy);

                    $browse_item_parent_node['children'] = $ci_tree;
                    array_push($root_node['children'], $browse_item_parent_node);

                    unset($ciTypes);
                    unset($permittedCiTypes);
                    unset($navigationDao);
                    break;

                default:
                    array_push($menu_tree_actions,
                        self::createMenuNode($translator->getAdapter()->translate($row[Db_Menu::NAME]), APPLICATION_URL . $row[Db_Menu::FUNCTION_],
                            $translator->getAdapter()->translate($row[Db_Menu::NAME])));
                    break;
            }

        }

        if ($datamodel['active'] == true) {
            array_push($menu_tree_actions, $datamodel['data']);
        }
        if ($usermanagement['active'] == true) {
            array_push($menu_tree_actions, $usermanagement['data']);
        }
        if ($automation['active'] == true) {
            array_push($menu_tree_actions, $automation['data']);
        }
        if ($interfaces['active'] == true) {
            array_push($menu_tree_actions, $interfaces['data']);
        }
        if ($settings['active'] == true) {
            array_push($menu_tree_actions, $settings['data']);
        }

        unset($menuList);

        $root_node['children']       = array_merge($root_node['children'], $menu_tree_actions);
        $menu_tree_array['children'] = array($root_node);
        $jsonTree                    = json_encode($menu_tree_array);


        $menuTree .= "<script type='text/javascript'>" .
            'navigation_tree_json = ' . $jsonTree . ';' .
            "</script>";
        return $menuTree;
    }


    private static function buildTree($ciTypes, $permittedCiTypes, $parent = null)
    {
        $hierarchy = array();

        if (!$parent) {
            // handle root elements
            foreach ($ciTypes as $type) {
                if (!$type[Db_CiType::PARENT_CI_TYPE_ID] || $type[Db_CiType::PARENT_CI_TYPE_ID] == 0) {
                    if (in_array($type[Db_CiType::ID], $permittedCiTypes)) {
                        $type['permitted'] = 1;
                    } else {
                        $type['permitted'] = 0;
                    }
                    $type['childs'] = self::buildTree($ciTypes, $permittedCiTypes, $type[Db_CiType::ID]);
                    array_push($hierarchy, $type);
                }
            }
        } else {
            // subtree
            foreach ($ciTypes as $type) {
                if ($type[Db_CiType::PARENT_CI_TYPE_ID] == $parent) {
                    if (in_array($type[Db_CiType::ID], $permittedCiTypes)) {
                        $type['permitted'] = 1;
                    } else {
                        $type['permitted'] = 0;
                    }
                    $type['childs'] = self::buildTree($ciTypes, $permittedCiTypes, $type[Db_CiType::ID]);
                    array_push($hierarchy, $type);
                }
            }
        }

        return $hierarchy;
    }


    private static function handlePermissions($ciTypes)
    {
        foreach ($ciTypes as $key => $type) {
            $res = self::updateChildPermissions($type['childs']);
            if ($res['permitted'])
                $ciTypes[$key]['permitted'] = 1;

            $ciTypes[$key]['childs'] = $res['childs'];

            if (!$ciTypes[$key]['permitted']) {
                unset($ciTypes[$key]);
            }
        }

        return $ciTypes;
    }


    private static function updateChildPermissions($ciType)
    {
        $hasPermission = 0;

        if ($ciType && count($ciType) > 0) {
            foreach ($ciType as $key => $type) {
                $res = self::updateChildPermissions($type['childs']);

                if ($res['permitted'])
                    $ciType[$key]['permitted'] = 1;

                $ciType[$key]['childs'] = $res['childs'];

                if ($ciType[$key]['permitted']) {
                    $hasPermission = 1;
                }

                if (!$ciType[$key]['permitted']) {
                    unset($ciType[$key]);
                }
            }
        }

        return array('permitted' => $hasPermission, 'childs' => $ciType);
    }


    /**
     * generates the tree structure recursively.
     *
     * can be translated by adding a translator object and using the Db_CiType::NAME as key
     *
     * @param unknown_type $array  the arrayitem to add
     * @param unknown_type $output the parent key
     * @param unknown_type $child  the sub-child
     */
    private static function generateList($ciTypes)
    {
        $output = '';

        if ($ciTypes && count($ciTypes) > 0)
            foreach ($ciTypes as $key => $type) {

                $output .= '<ul><li class="folder closed">';
                $output .= '<a href="' . APPLICATION_URL . 'ci/index/typeid/' . $type[Db_CiType::ID] . '/' .
                    '" title="' . $type[Db_CiType::NOTE] . '">' . $type[Db_CiType::DESCRIPTION] . '</a>';
                $output .= self::generateList($type['childs']);

                unset($type);
                unset($ciTypes[$key]);

                $output .= '</li></ul>';
            }

        return $output;
    }

    private static function generateNodeArrayRecursive($ciTypes)
    {
        $nodes = array();
        if ($ciTypes && count($ciTypes) > 0) {
            foreach ($ciTypes as $ciType) {
                $current_node = array();
                $current_node = self::createMenuNode(Bootstrap::xssCleanView($ciType[Db_CiType::DESCRIPTION]), APPLICATION_URL . 'ci/index/typeid/' .
                    $ciType[Db_CiType::ID] . '/', $ciType[Db_CiType::NOTE], "spriteCi",
                    self::generateNodeArrayRecursive($ciType['childs']), "menu-element", $ciType[Db_CiType::ID]);
                array_push($nodes, $current_node);
            }
        }
        return $nodes;
    }


    /**
     * this function converts the chars configured in db to humand readable text
     *
     * @param $string
     *
     * @return unknown_type
     */
    function keephtml($string)
    {
        $res = htmlentities($string);
        $res = str_replace("&lt;", "<", $res);
        $res = str_replace("&gt;", ">", $res);
        $res = str_replace("&quot;", '"', $res);
        $res = str_replace("&amp;", '&', $res);
        $res = str_replace("�-", '�', $res);
        return $res;
    }

    private static function createMenuNode($title, $link, $tooltip, $icon = null, $children = null, $class = null, $id = null)
    {
        $new_node           = array();
        $new_node['title']  = "<a href='" . $link . "' title='" . $tooltip . "'>" . $title . "</a>";
        $new_node['href']   = $link;
        $new_node['folder'] = true;
        if (!is_null($tooltip)) {
            $new_node['tooltip'] = $tooltip;
        }
        if (!is_null($children)) {
            $new_node['children'] = $children;
        }
        if (!is_null($icon)) {
            $new_node['icon'] = $icon;
        }
        if (!is_null($class)) {
            $new_node['extraClasses'] = $class;
        }
        if (!is_null($id)) {
            $new_node['key'] = $id;
        }
        return $new_node;
    }

    private static function createMenuActionNode($title, $tooltip, $icon = null, $children, $class = null)
    {
        $new_node           = array();
        $new_node['title']  = $title;
        $new_node['href']   = "none";
        $new_node['folder'] = true;
        if (!is_null($tooltip)) {
            $new_node['tooltip'] = $tooltip;
        }
        if (!is_null($children)) {
            $new_node['children'] = $children;
        }
        if (!is_null($icon)) {
            $new_node['icon'] = $icon;
        }
        if (!is_null($class)) {
            $new_node['extraClasses'] = $class;
        }
        return $new_node;
    }
}