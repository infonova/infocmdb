<?php

/**
 *
 *
 *
 */
class Service_Theme_Create extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2502, $themeId);
    }


    /**
     * retreive Create Form
     */
    public function getCreateThemeForm()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/theme.ini', APPLICATION_ENV);
        $form   = new Form_Theme_Create($this->translator, $config);

        $menuDaoImpl = new Dao_Menu();
        $menuList    = $menuDaoImpl->getMenus();

        $modifiedMenuList    = array();
        $modifiedMenuList[0] = ' ';

        foreach ($menuList as $menu) {
            $modifiedMenuList[$menu[Db_Menu::ID]] = $menu[Db_Menu::DESCRIPTION];
        }

        $form->addStartPage($modifiedMenuList);

        // add check boxes
        foreach ($menuList as $menu) {
            $form->addMenu($menu[Db_Menu::ID], $menu[Db_Menu::NAME], $menu[Db_Menu::DESCRIPTION]);
        }

        $form->addSubmitButton();
        return array('form' => $form, 'menuList' => $menuList);
    }


    /**
     * creates a Theme by the given values
     *
     * @param array $values
     */
    public function createTheme($theme, $menuList)
    {
        try {
            $themeDaoImpl = new Dao_Theme();
            $primary      = $themeDaoImpl->saveThemeInformation($theme, $menuList);

            if (!$primary) {
                throw new Exception();
            }

            // add required
            array_push($menuList, 0);
            foreach ($menuList as $menu) {
                $resourceIdList = Service_Abstract::getRecourceIds($menu);
                if ($resourceIdList)
                    foreach ($resourceIdList as $resourceId) {
                        $privilege                                 = array();
                        $privilege[Db_ThemePrivilege::THEME_ID]    = $primary;
                        $privilege[Db_ThemePrivilege::RESOURCE_ID] = $resourceId;
                        $themeDaoImpl->saveThemePrivileges($privilege);
                    }
            }

            return $primary;

        } catch (Exception $e) {
            $this->logger->log($e, Zend_Log::ERR);
            throw new Exception_Theme_InsertFailed($e);
        }
    }
}