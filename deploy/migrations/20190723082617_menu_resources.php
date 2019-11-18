<?php

use PhinxExtend\AbstractPhinxMigration;

class MenuResources extends AbstractPhinxMigration
{
    private $workflowMenuId = 36;

    private $mailImportMenuId      = 24;
    private $mailImportResourceIds = array(8001, 8002, 8003, 8004);

    private $adminMenuId      = 42;
    private $adminResourceIds = array(9001, 9002, 9003, 9004);

    private $fileUploadMenuId      = 42;
    private $fileUploadResourceIds = array(901);

    public function up()
    {
        $this->down();

        // mail import - add new resource ids
        $mailImportThemeIds = $this->getThemeIdsWithMenuId($this->mailImportMenuId);
        foreach ($mailImportThemeIds as $themeId) {
            foreach ($this->mailImportResourceIds as $resourceId) {
                $this->execute(sprintf("INSERT INTO theme_privilege (resource_id, theme_id) VALUES(%d, %d)", $resourceId, $themeId));
            }
        }

        // admin - add new menu and resource ids
        $this->execute("INSERT INTO menu (id, name, description, note, function, order_number, is_active) VALUES (42, 'admin', 'Administration', 'infoCMDB Administration', 'admin/index', 255, '1')");

        // whom to authorize for admin - theme can handle workflows? --> more permissions shouldn't be a problem
        $workflowThemeIds = $this->getThemeIdsWithMenuId($this->workflowMenuId);
        foreach ($workflowThemeIds as $themeId) {
            $this->execute(sprintf("INSERT INTO theme_menu (theme_id, menue_id) VALUES (%d, %d)", $themeId, $this->adminMenuId));

            foreach ($this->adminResourceIds as $resourceId) {
                $this->execute(sprintf("INSERT INTO theme_privilege (resource_id, theme_id) VALUES(%d, %d)", $resourceId, $themeId));
            }
        }

        // file upload - add new menu and resource ids
        $this->execute("INSERT INTO menu (id, name, description, note, function, order_number, is_active) VALUES (43, 'file_upload', 'Fileupload', 'Verwaltung von Datei-Uploads', 'fileupload/index', 255, '1')");

        // whom to authorize for file upload admin privileges - theme can handle workflows? --> more permissions shouldn't be a problem
        $workflowThemeIds = $this->getThemeIdsWithMenuId($this->workflowMenuId);
        foreach ($workflowThemeIds as $themeId) {
            $this->execute(sprintf("INSERT INTO theme_menu (theme_id, menue_id) VALUES (%d, %d)", $themeId, $this->fileUploadMenuId));

            foreach ($this->fileUploadResourceIds as $resourceId) {
                $this->execute(sprintf("INSERT INTO theme_privilege (resource_id, theme_id) VALUES(%d, %d)", $resourceId, $themeId));
            }
        }

        echo "\n   WARNING:    apiV2 users now need a theme with permissions for the specific endpoint!!\n\n";

    }

    public function down()
    {
        // mail import - delete resource ids
        $mailImportThemeIds = $this->getThemeIdsWithMenuId($this->mailImportMenuId);
        if (count($mailImportThemeIds) > 0) {
            $mailImportResourceIds = implode(", ", $this->mailImportResourceIds);
            $mailImportThemeIds    = implode(", ", $mailImportThemeIds);

            $query = sprintf("DELETE FROM theme_privilege WHERE theme_id IN (%s) AND resource_id IN (%s)", $mailImportThemeIds, $mailImportResourceIds);
            $this->execute($query);
        }

        // admin - delete menu and resource ids
        $adminResourceIds = implode(",", $this->adminResourceIds);
        $this->execute("DELETE FROM menu WHERE id = 42");
        $this->execute(sprintf("DELETE FROM theme_privilege WHERE resource_id IN (%s)", $adminResourceIds));

        // file upload - delete menu and resource ids
        $fileUploadResourceIds = implode(",", $this->fileUploadResourceIds);
        $this->execute("DELETE FROM menu WHERE id = 43");
        $this->execute(sprintf("DELETE FROM theme_privilege WHERE resource_id IN (%s)", $fileUploadResourceIds));
    }

    protected function getThemeIdsWithMenuId($menuId): array
    {
        $mailImportThemes = $this->fetchAll("SELECT theme_id FROM theme_menu WHERE menue_id = " . $menuId);
        $ids              = array_column($mailImportThemes, 'theme_id');

        return $ids;
    }
}
