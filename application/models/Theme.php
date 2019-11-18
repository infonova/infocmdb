<?php

class Dao_Theme extends Dao_Abstract
{

    public function getTheme($id)
    {
        $select = $this->db->select()
            ->from(Db_Theme::TABLE_NAME)
            ->where(Db_Theme::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }

    public function getExtendedTheme($id)
    {
        $select = $this->db->select()
            ->from(Db_Theme::TABLE_NAME)
            ->join(Db_Menu::TABLE_NAME, Db_Theme::TABLE_NAME . '.' . Db_Theme::MENU_ID . ' = ' . Db_Menu::TABLE_NAME . '.' . Db_Menu::ID, array('menuName' => Db_Menu::TABLE_NAME . '.' . Db_Menu::NAME))
            ->where(Db_Theme::TABLE_NAME . '.' . Db_Theme::ID . ' =?', $id);
        return $this->db->fetchRow($select);
    }

    public function getThemeStartPage($id)
    {
        $select = $this->db->select()
            ->from(Db_Theme::TABLE_NAME)
            ->join(Db_Menu::TABLE_NAME, Db_Theme::TABLE_NAME . '.' . Db_Theme::MENU_ID . ' = ' . Db_Menu::TABLE_NAME . '.' . Db_Menu::ID, array(Db_Menu::FUNCTION_))
            ->where(Db_Theme::TABLE_NAME . '.' . Db_Theme::ID . ' =?', $id);

        return $this->db->fetchRow($select);
    }

    public function getThemesForPaginator($orderBy = null, $direction = null, $filter = null)
    {
        $table  = new Db_Theme();
        $select = $table->select();

        if ($filter) {
            $select = $select
                ->where(Db_Theme::TABLE_NAME . '.' . Db_Theme::NAME . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Theme::TABLE_NAME . '.' . Db_Theme::DESCRIPTION . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Theme::TABLE_NAME . '.' . Db_Theme::NOTE . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Theme::TABLE_NAME . '.' . Db_Theme::MENU_ID . ' LIKE "%' . $filter . '%"')
                ->orWhere(Db_Theme::TABLE_NAME . '.' . Db_Theme::ID . ' LIKE "%' . $filter . '%"');
        }

        if ($orderBy) {
            if ($direction)
                $orderBy = $orderBy . ' ' . $direction;

            $select->order($orderBy);
        } else $select->order(Db_Theme::NAME);

        return $select;
    }

    public function getThemes()
    {
        $table  = new Db_Theme();
        $select = $table->select()
            ->where(Db_Theme::IS_ACTIVE . ' =?', '1')
            ->order(Db_Theme::NAME);

        return $this->db->fetchAll($select);
    }

    public function saveThemeInformation($theme, $menuList)
    {
        $table = new Db_Theme();

        $theme[Db_Theme::IS_ACTIVE] = '1';
        $themeId                    = $table->insert($theme);

        // insert menus
        $tableMenu = new Db_ThemeMenu();
        foreach ($menuList as $menu) {
            $data                         = array();
            $data[Db_ThemeMenu::THEME_ID] = $themeId;
            $data[Db_ThemeMenu::MENU_ID]  = $menu;

            $tableMenu->insert($data);
        }

        return $themeId;
    }

    public function deleteThemePrivileges($themeId)
    {
        $table = new Db_ThemePrivilege();
        $where = $this->db->quoteInto(Db_ThemePrivilege::THEME_ID . ' = ?', $themeId);

        return $table->delete($where);
    }

    public function saveThemePrivileges($privilege)
    {
        $table = new Db_ThemePrivilege();
        return $table->insert($privilege);
    }

    public function deleteThemeMenuByThemeId($themeId)
    {
        $table = new Db_ThemeMenu();
        $where = $this->db->quoteInto(Db_ThemeMenu::THEME_ID . ' = ?', $themeId);

        return $table->delete($where);
    }

    public function deleteTheme($themeId)
    {
        $table = new Db_Theme();
        $where = $this->db->quoteInto(Db_Theme::ID . ' = ?', $themeId);

        return $table->delete($where);
    }

    public function deactivateTheme($themeId)
    {
        $sql = "UPDATE " . Db_Theme::TABLE_NAME . " SET " . Db_Theme::IS_ACTIVE . " = '0' 
		WHERE " . Db_Theme::ID . " = '" . $themeId . "'";
        return $this->db->query($sql);
    }

    public function activateTheme($themeId)
    {
        $sql = "UPDATE " . Db_Theme::TABLE_NAME . " SET " . Db_Theme::IS_ACTIVE . " = '1' 
		WHERE " . Db_Theme::ID . " = '" . $themeId . "'";
        return $this->db->query($sql);
    }

    public function updateThemeInformation($theme, $newMenuList, $themeId)
    {
        $table = new Db_Theme();
        $where = $this->db->quoteInto(Db_Theme::ID . ' = ?', $themeId);

        $upd = $table->update($theme, $where);

        // insert menus
        $tableMenu = new Db_ThemeMenu();
        foreach ($newMenuList as $menu) {
            $data                         = array();
            $data[Db_ThemeMenu::THEME_ID] = $themeId;
            $data[Db_ThemeMenu::MENU_ID]  = $menu;

            $ret = $tableMenu->insert($data);
            if ($ret) {
                $upd++;
            }
        }
        return $upd;
    }

    public function getThemeMenusByThemeId($themeId)
    {
        $select = $this->db->select()
            ->from(Db_ThemeMenu::TABLE_NAME)
            ->where(Db_ThemeMenu::THEME_ID . ' = ?', $themeId);

        return $this->db->fetchAll($select);
    }

    public function getThemeByUserId($userId)
    {
        $select = $this->db->select()
            ->from(Db_Theme::TABLE_NAME)
            ->join(Db_User::TABLE_NAME, Db_User::TABLE_NAME . '.' . Db_User::THEME_ID . ' = ' . Db_Theme::TABLE_NAME . '.' . Db_Theme::ID)
            ->where(Db_User::TABLE_NAME . '.' . Db_User::ID . ' = ?', $userId);

        return $this->db->fetchRow($select);
    }

    public function checkUnique(string $value, int $id = 0)
    {
        $select = $this->db->select()
            ->from(Db_Theme::TABLE_NAME, 'count(*) as cnt')
            ->where(Db_Theme::NAME . ' LIKE ?', $value);

        if($id > 0) {
            $select->where(Db_Theme::ID . ' != ?', $id);
        }

        return $this->db->fetchRow($select);
    }
}