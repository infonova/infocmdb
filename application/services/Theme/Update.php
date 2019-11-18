<?php

/**
 *
 *
 *
 */
class Service_Theme_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 2503, $themeId);
    }


    /**
     * retrieve Update Form
     *
     * @param int $themeId
     *
     * @return multitype:Form_Theme_Update unknown
     */
    public function getUpdateThemeForm($themeId)
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms/theme.ini', APPLICATION_ENV);
        $form   = new Form_Theme_Update($this->translator, $config, $themeId);

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

        $form->addSubmitButton(false);
        return array('form' => $form, 'menuList' => $menuList);
    }


    /**
     * updates a Theme by the given Theme Id and values
     *
     * @param int   $themeId
     * @param array $theme
     * @param array $menuList
     *
     * @throws Exception_Theme_UpdateItemNotFound if no items are updated
     * @throws Exception_Theme_UpdateFailed on all other errors
     */
    public function updateTheme($themeId, $theme, $menuList)
    {
        try {
            $themeDaoImpl = new Dao_Theme();
            $themeDaoImpl->deleteThemeMenuByThemeId($themeId);
            $rows = $themeDaoImpl->updateThemeInformation($theme, $menuList, $themeId);
            if ($rows < 1) {
                throw new Exception_Theme_UpdateItemNotFound();
            }

            $themeDaoImpl->deleteThemePrivileges($themeId);
            $alreadyAdded = array();
            array_push($menuList, 0);
            foreach ($menuList as $menu) {
                $resourceIdList = Service_Abstract::getRecourceIds($menu);
                if ($resourceIdList) {

                    foreach ($resourceIdList as $resourceId) {
                        $privilege                                 = array();
                        $privilege[Db_ThemePrivilege::THEME_ID]    = $themeId;
                        $privilege[Db_ThemePrivilege::RESOURCE_ID] = $resourceId;

                        if (!$alreadyAdded[$resourceId]) {
                            $themeDaoImpl->saveThemePrivileges($privilege);
                            $alreadyAdded[$resourceId] = true;
                        }
                    }
                }
            }
        } catch (Exception $e) {
            if ($e instanceof Exception_Theme)
                throw $e;
            throw new Exception_Theme_UpdateFailed($e);
        }
    }

}