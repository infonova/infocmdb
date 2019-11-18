<?php

/**
 *
 *
 *
 */
class Service_Menu_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 3802, $themeId);
    }


    public function updateMenu($formData, $menuId)
    {
        $menu = array();
        if (isset($formData['description']))
            $menu[Db_Menu::DESCRIPTION] = $formData['description'];
        if (isset($formData['note']))
            $menu[Db_Menu::NOTE] = $formData['note'];
        if (isset($formData['order']))
            $menu[Db_Menu::ORDER_NUMBER] = $formData['order'];
        if (isset($formData[Db_Menu::IS_ACTIVE]))
            $menu[Db_Menu::IS_ACTIVE] = $formData[Db_Menu::IS_ACTIVE];

        try {
            $menuDaoImpl = new Dao_Menu();
            $menuDaoImpl->updateMenu($menu, $menuId);
        } catch (Exception $e) {
            throw new Exception_Menu_UpdateFailed($e);
        }
    }

    public function getUpdateMenuForm()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        $form = new Form_Menu_Update($this->translator, $attributeTypeList);
        return $form;
    }
}