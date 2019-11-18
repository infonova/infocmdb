<?php

class Service_Attributetype_Update extends Service_Abstract
{

    public function __construct($translator, $logger, $themeId)
    {
        parent::__construct($translator, $logger, 4102, $themeId);
    }


    public function updateAttributetype($formData, $menuId)
    {
        $menu = array();
        if (isset($formData['order']))
            $menu[Db_AttributeType::ORDER_NUMBER] = $formData['order'];
        if (isset($formData[Db_AttributeType::IS_ACTIVE]))
            $menu[Db_AttributeType::IS_ACTIVE] = $formData[Db_AttributeType::IS_ACTIVE];

        try {
            $attributetypeDaoImpl = new Dao_AttributeType();
            $attributetypeDaoImpl->updateAttributetype($menu, $menuId);
        } catch (Exception $e) {
            throw new Exception_Menu_UpdateFailed($e);
        }
    }

    public function getUpdateAttributetypeForm()
    {
        $config = new Zend_Config_Ini(APPLICATION_PATH . '/configs/forms.ini', APPLICATION_ENV);

        $form = new Form_Attributetype_Update($this->translator, $attributeTypeList);
        return $form;
    }
}