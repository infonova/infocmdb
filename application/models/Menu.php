<?php

class Dao_Menu extends Dao_Abstract
{


    public function getMenus()
    {
        $table  = new Db_Menu();
        $select = $table->select()->order(Db_Menu::DESCRIPTION);
        return $this->db->fetchAll($select);
    }

    public function updateMenu($data, $menuId)
    {
        $table = new Db_Menu();
        $where = $this->db->quoteInto(Db_Menu::ID . ' =?', $menuId);
        return $table->update($data, $where);
    }

    public function getMenu($menuId)
    {
        $table  = new Db_Menu();
        $select = $table->select()->where(Db_Menu::TABLE_NAME . '.' . Db_Menu::ID . ' = ?', $menuId);
        return $this->db->fetchRow($select);
    }

    public function getMenuPagination($orderBy = null, $direction = null, $filter = null)
    {
        $select = $this->db->select()->from(Db_Menu::TABLE_NAME);

        if ($filter) {
            $select = $select
                ->where(Db_Menu::TABLE_NAME . '.' . Db_Menu::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Menu::TABLE_NAME . '.' . Db_Menu::DESCRIPTION . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else {
            $orderBy = Db_Menu::TABLE_NAME . '.' . Db_Menu::ID . ' desc';

            $select->order($orderBy);
        }
        return $select;
    }

    public function getMenusByThemeId($themeId)
    {
        $table = new Db_Menu();

        $select = $table->select(Zend_Db_Table::SELECT_WITH_FROM_PART);
        $select->setIntegrityCheck(false)
            ->joinLeft(Db_ThemeMenu::TABLE_NAME, Db_ThemeMenu::TABLE_NAME . '.' . Db_ThemeMenu::MENU_ID . ' = ' . Db_Menu::TABLE_NAME . '.' . Db_Menu::ID)
            ->where(Db_ThemeMenu::TABLE_NAME . '.' . Db_ThemeMenu::THEME_ID . ' = ?', $themeId)
            ->order(Db_Menu::ORDER_NUMBER);

        return $table->fetchAll($select);;
    }

    public function getActiveMenusByThemeId($themeId)
    {
        $table = new Db_Menu();

        $select = $table->select(Zend_Db_Table::SELECT_WITH_FROM_PART);
        $select->setIntegrityCheck(false)
            ->join(Db_ThemeMenu::TABLE_NAME,
                Db_ThemeMenu::TABLE_NAME . '.' . Db_ThemeMenu::MENU_ID . ' = ' . Db_Menu::TABLE_NAME . '.' . Db_Menu::ID)
            ->where(Db_ThemeMenu::TABLE_NAME . '.' . Db_ThemeMenu::THEME_ID . ' = ?', $themeId)
            ->where(Db_Menu::IS_ACTIVE . ' =?', '1')
            ->order(Db_Menu::ORDER_NUMBER);

        return $table->fetchAll($select);;
    }

    public function getStartpageMenusByThemeId($themeId)
    {
        $table = new Db_Menu();

        $select = $table->select(Zend_Db_Table::SELECT_WITH_FROM_PART);
        $select->setIntegrityCheck(false)
            ->join(Db_Theme::TABLE_NAME,
                Db_Theme::TABLE_NAME . '.' . Db_Theme::MENU_ID . ' = ' . Db_Menu::TABLE_NAME . '.' . Db_Menu::ID)
            ->where(Db_Theme::TABLE_NAME . '.' . Db_Theme::ID . ' = ?', $themeId)
            ->order(Db_Menu::ORDER_NUMBER);

        $rows = $table->fetchRow($select);

        return $rows;
    }

}