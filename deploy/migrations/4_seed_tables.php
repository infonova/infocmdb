<?php

use PhinxExtend\AbstractPhinxMigration;

class SeedTables extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
		$seedCheck = $this->fetchRow("SELECT COUNT(*) FROM attribute_type");

        //only seed if migration version matches
        if ($this->getCurrentMigrateVersion() == 3 && $seedCheck[0] == 0) {
            echo "\n";

            $this->execute("
            INSERT INTO `attribute_type` (`id`, `name`, `description`, `note`, `customization_level`, `is_regex_enabled`, order_number, is_active) VALUES
            (1, 'input', 'Input Field', 'Single line of unformatted text', 1, '1', 10, '1'),
            (2, 'textarea', 'Text Area', 'Multiple lines of unformatted text (textbox)', 2, '0', 20, '1'),
            (3, 'textEdit', 'Editor Area', 'Multiple lines of formatted text (WYSIWYG editor)', 2, '0', 30, '1'),
            (4, 'select', 'Dropdown (static)', 'Standard dropdown (single select)', 0, '0', 40, '1'),
            (5, 'checkbox', 'Checkbox', 'Standard checkbox (multi select)', 0, '0', 50, '1'),
            (6, 'radio', 'Radiobutton', 'Standard radiobutton (single select)', 0, '0', 60, '1'),
            (7, 'date', 'Date', 'Date field (without time)', 6, '0', 70, '1'),
            (8, 'dateTime', 'Date and Time', 'Date and time field', 6, '0', 80, '1'),
            (9, 'zahlungsmittel', 'Currency', 'Zahlungsmittel (1.000,00)', 0, '0', 90, '1'),
            (10, 'password', 'Password', 'Password (masked value)', 0, '0', 100, '1'),
            (11, 'link', 'Link (URL)', 'Text formatted as a html link', 0, '0', 110, '1'),
            (12, 'attachment', 'Attachment', 'File attachment', 0, '0', 120, '1'),
            (17, 'info', 'Fixed Info', 'Static note for all CIs of a specific CI Type', 5, '0', 130, '1'),
            (16, 'ciType', 'Dropdown (CI Type)', 'Dropdown filled with all CIs of a defined CI Type (stored value is a reference to the attribute value of the selected CI)', 4, '0', 140, '1'),
            (19, 'ciTypePersist', 'Dropdown (CI Type persistent)', 'Dropdown filled with all Cis of a defined CI Type (stored value is the attribute value of the selected CI)', 4, '0', 150, '1'),
            (21, 'selectQuery', 'Dropdown (SQL filled)', 'Dropdown filled with the output of a select query (multi select is optional)', 7, '0', 160, '1'),
            (20, 'filter', 'Dropdown (Attribute linked)', 'Dropdown persistently filled with the output of a select query', 0, '0', 170, '0'),
            (15, 'query', 'SQL Select', 'Dynamically displays the output of a select query (query will be executed each time the CI is displayed)', 0, '0', 180, '1'),
            (18, 'queryPersist', 'SQL Select (persistent)', 'Dynamically displays the output of a select query (query will be executed each time the CI is created or edited)', 0, '0', 190, '1'),
            (14, 'executeable', 'Script', 'Script triggered via link (execution parameter is CI-ID)', 3, '0', 200, '1'),
            (22, 'selectPopup', 'Select Popup', 'Select Popup', 8, '0', 999, '0'),
            (13, 'script', 'Externes Script', 'script', 0, '0', 999, '1')
        ");


            $this->execute("
            INSERT INTO `ci_relation_direction` (`id`, `name`, `description`, `note`) VALUES
            (1, 'ab_direction', 'gerichtet A - B', 'gerichtet A - B'),
            (2, 'ba_direction', 'gerichtet B - A', 'gerichtet B - A'),
            (3, 'bidirected', 'bi-gerichtet', 'A - B und B - A'),
            (4, 'undirected', 'nicht gerichtet', 'keine Richtung')
        ");

            $this->execute("
            INSERT INTO `menu` (`id`, `name`, `description`, `note`, `function`, `order_number`, `is_active`) VALUES
            (1, 'new_ci', 'CI anlegen', 'Neues CI erstellen', 'ci/create', 14, '1'),
            (2, 'search', 'Suche', 'Nach CIs suchen', 'search/index', 13, '1'),
            (3, 'user', 'Benutzer', 'Benutzer anzeigen, anlegen, bearbeiten', 'user/index', 50, '1'),
            (4, 'project', 'Projekte', 'Projekte anzeigen, anlegen, bearbeiten', 'project/index', 52, '1'),
            (5, 'role', 'Rollen', 'Rollen anzeigen, anlegen, bearbeiten', 'role/index', 51, '1'),
            (6, 'theme', 'Themen', 'Themen anzeigen, anlegen, bearbeiten', 'theme/index', 53, '1'),
            (7, 'view_type', 'View Types', 'View Types anzeigen, anlegen, bearbeiten', 'attributegroup/index', 42, '1'),
            (8, 'ci_type', 'CI Types', 'CI Type anzeigen, anlegen, bearbeiten', 'citype/index', 40, '1'),
            (9, 'ci_relation_types', 'Relation Types', 'Relation Types anzeigen, anlegen, bearbeiten', 'relationtype/index', 43, '1'),
            (10, 'attribute', 'Attributes', 'Attribute anzeigen, anlegen, bearbeiten', 'attribute/index', 41, '1'),
            (18, 'history', 'History', 'zeigt die History aller CIs an', 'history/index', 15, '1'),
            (19, 'browse_ci', 'Browse Items', 'Strukturierte Anzeige der aktiven CIs', 'index/index', 10, '1'),
            (20, 'auto_discovery', 'Auto Discovery', 'Automatisierte &Auml;nderungen', 'autodiscovery/index', 12, '1'),
            (21, 'search_list', 'Such Listen', 'Such Listen', 'searchlist/index', 18, '1'),
            (22, 'favourites', 'Favoriten', 'Favoriten anzeigen', 'favourites/index', 11, '1'),
            (23, 'mail_config', 'Notifications', 'zur Verwaltung von Mails und Mail Templates', 'mail/index', 74, '1'),
            (24, 'mail_import', 'Mail Import', 'Mail Import', 'mailimport/index', 73, '1'),
            (25, 'customization', 'Customization Modul', 'Customization Modul', 'customization/index', 22, '1'),
            (26, 'reporting', 'Reporting', 'Reporting', 'reporting/index', 61, '1'),
            (27, 'config', 'Configuration', 'Configuration', 'config/index', 81, '1'),
            (28, 'file_import', 'File Import', 'File Import', 'fileimport', 72, '1'),
            (29, 'map', 'Map', 'Map', 'map/index', 17, '1'),
            (30, 'cql_interface', 'CQL-Interface', 'CQL-Interface', 'query/index', 70, '1'),
            (31, 'dashboard', 'Dashboard', 'Dashboard', 'dashboard/index', 19, '1'),
            (32, 'translation', 'Translation Files', 'Translation Files', 'translation/index', 83, '1'),
            (33, 'validation', 'Validation', 'Validierung', 'validation/index', 71, '1'),
            (34, 'menu', 'Menu', 'Menu', 'menu/index', 80, '1'),
            (35, 'logs', 'Log-Files', 'Log-Files', 'log/index', 84, '1'),
            (36, 'workflow', 'Workflow', 'Workflow', 'workflow/index', 60, '1'),
            (37, 'visualization', 'Visualisierung', NULL, 'relation/visualization', 16, '1'),
            (38, 'ticket', 'Tickets', 'Zeigt alle offenen tickets', 'ticket/index', 20, '1'),
            (39, 'event', 'Monitoring Events', NULL, 'event/index', 21, '1'),
            (40, 'attributetype', 'Attribute Type', NULL, 'attributetype/index', 82, '1')
        ");

            $this->execute("
            INSERT INTO `queue` (`id`, `name`, `note`, `is_active`) VALUES
            (1, 'import_file', 'File processing queue', '1'),
            (2, 'import_mail', 'Mail processing queue', '1'),
            (3, 'customization', 'Costimization Queue', '1'),
            (4, 'reporting', 'Reporting Queue', '1'),
            (5, 'update', 'Updates persistent values', '1'),
            (6, 'workflow', 'Norkflow Queue', '1');
        ");


            //if($this->ask('create default-role "admin"', array('y', 'n'), 'n') === 'y') {
            $this->execute("INSERT INTO `role` (`name`, `is_active`, `description`, `note`, `user_id`, `valid_from`) VALUES ('admin', '1', 'Administrator', 'Vollzugriff', 1, now())");
            //}
            $adminRoleId = $this->fetchRow("SELECT id FROM `role` WHERE name = 'admin'");
            $adminRoleId = $adminRoleId[0];

            //if($this->ask('create default-theme "Admin"', array('y', 'n'), 'n') === 'y') {
            $this->execute("INSERT INTO `theme` (`name`, `description`, `note`, `is_active`, `is_wildcard_enabled`, `menu_id`, `user_id`, `valid_from`) 
										VALUES  ('admin', 'Admin', 'Administration Theme with access to admin actions.', '1', '1', 19, '1', now())");
            $adminThemeId = $this->fetchRow("SELECT id FROM `theme` WHERE name = 'admin'");
            $adminThemeId = $adminThemeId[0];

            $this->execute("
                INSERT INTO `theme_menu` (`theme_id`, `menue_id`) VALUES
                (" . $adminThemeId . ", 1),
				(" . $adminThemeId . ", 2),
				(" . $adminThemeId . ", 3),
				(" . $adminThemeId . ", 4),
				(" . $adminThemeId . ", 5),
				(" . $adminThemeId . ", 6),
				(" . $adminThemeId . ", 7),
				(" . $adminThemeId . ", 8),
				(" . $adminThemeId . ", 9),
				(" . $adminThemeId . ", 10),
				(" . $adminThemeId . ", 18),
				(" . $adminThemeId . ", 19),
				(" . $adminThemeId . ", 22),
				(" . $adminThemeId . ", 23),
				(" . $adminThemeId . ", 24),
				(" . $adminThemeId . ", 26),
				(" . $adminThemeId . ", 28),
				(" . $adminThemeId . ", 30),
				(" . $adminThemeId . ", 33),
				(" . $adminThemeId . ", 34),
				(" . $adminThemeId . ", 35),
				(" . $adminThemeId . ", 36)
            ");

            $this->execute("
                INSERT INTO `theme_privilege` (`resource_id`, `theme_id`) VALUES
                ( 101," . $adminThemeId . "),
				( 102," . $adminThemeId . "),
				( 103," . $adminThemeId . "),
				( 104," . $adminThemeId . "),
				( 105," . $adminThemeId . "),
				( 106," . $adminThemeId . "),
				( 107," . $adminThemeId . "),
				( 108," . $adminThemeId . "),
				( 109," . $adminThemeId . "),
				( 110," . $adminThemeId . "),
				( 111," . $adminThemeId . "),
				( 201," . $adminThemeId . "),
				( 202," . $adminThemeId . "),
				( 203," . $adminThemeId . "),
				( 301," . $adminThemeId . "),
				( 302," . $adminThemeId . "),
				( 303," . $adminThemeId . "),
				( 304," . $adminThemeId . "),
				( 305," . $adminThemeId . "),
				( 306," . $adminThemeId . "),
				( 307," . $adminThemeId . "),
				( 308," . $adminThemeId . "),
				( 309," . $adminThemeId . "),
				( 310," . $adminThemeId . "),
				( 311," . $adminThemeId . "),
				( 401," . $adminThemeId . "),
				( 402," . $adminThemeId . "),
				( 403," . $adminThemeId . "),
				( 404," . $adminThemeId . "),
				( 405," . $adminThemeId . "),
				( 406," . $adminThemeId . "),
				( 501," . $adminThemeId . "),
				( 502," . $adminThemeId . "),
				( 503," . $adminThemeId . "),
				( 601," . $adminThemeId . "),
				( 701," . $adminThemeId . "),
				( 702," . $adminThemeId . "),
				( 703," . $adminThemeId . "),
				( 704," . $adminThemeId . "),
				( 1001," . $adminThemeId . "),
				( 1003," . $adminThemeId . "),
				( 1101," . $adminThemeId . "),
				( 1102," . $adminThemeId . "),
				( 1104," . $adminThemeId . "),
				( 1901," . $adminThemeId . "),
				( 1902," . $adminThemeId . "),
				( 1903," . $adminThemeId . "),
				( 1904," . $adminThemeId . "),
				( 1905," . $adminThemeId . "),
				( 2001," . $adminThemeId . "),
				( 2002," . $adminThemeId . "),
				( 2003," . $adminThemeId . "),
				( 2004," . $adminThemeId . "),
				( 2101," . $adminThemeId . "),
				( 2102," . $adminThemeId . "),
				( 2103," . $adminThemeId . "),
				( 2104," . $adminThemeId . "),
				( 2201," . $adminThemeId . "),
				( 2202," . $adminThemeId . "),
				( 2203," . $adminThemeId . "),
				( 2204," . $adminThemeId . "),
				( 2205," . $adminThemeId . "),
				( 2301," . $adminThemeId . "),
				( 2401," . $adminThemeId . "),
				( 2402," . $adminThemeId . "),
				( 2403," . $adminThemeId . "),
				( 2501," . $adminThemeId . "),
				( 2502," . $adminThemeId . "),
				( 2503," . $adminThemeId . "),
				( 2504," . $adminThemeId . "),
				( 2601," . $adminThemeId . "),
				( 2602," . $adminThemeId . "),
				( 2603," . $adminThemeId . "),
				( 2604," . $adminThemeId . "),
				( 2605," . $adminThemeId . "),
				( 2606," . $adminThemeId . "),
				( 2701," . $adminThemeId . "),
				( 2702," . $adminThemeId . "),
				( 2703," . $adminThemeId . "),
				( 2704," . $adminThemeId . "),
				( 2801," . $adminThemeId . "),
				( 2802," . $adminThemeId . "),
				( 2803," . $adminThemeId . "),
				( 2804," . $adminThemeId . "),
				( 2901," . $adminThemeId . "),
				( 2902," . $adminThemeId . "),
				( 2904," . $adminThemeId . "),
				( 3001," . $adminThemeId . "),
				( 3002," . $adminThemeId . "),
				( 3003," . $adminThemeId . "),
				( 3004," . $adminThemeId . "),
				( 3101," . $adminThemeId . "),
				( 3102," . $adminThemeId . "),
				( 3103," . $adminThemeId . "),
				( 3104," . $adminThemeId . "),
				( 3201," . $adminThemeId . "),
				( 3202," . $adminThemeId . "),
				( 3203," . $adminThemeId . "),
				( 3204," . $adminThemeId . "),
				( 3301," . $adminThemeId . "),
				( 3302," . $adminThemeId . "),
				( 3303," . $adminThemeId . "),
				( 3304," . $adminThemeId . "),
				( 3401," . $adminThemeId . "),
				( 3402," . $adminThemeId . "),
				( 3403," . $adminThemeId . "),
				( 3404," . $adminThemeId . "),
				( 3601," . $adminThemeId . "),
				( 3602," . $adminThemeId . "),
				( 3603," . $adminThemeId . "),
				( 3604," . $adminThemeId . "),
				( 3701," . $adminThemeId . "),
				( 3801," . $adminThemeId . "),
				( 3802," . $adminThemeId . "),
				( 3901," . $adminThemeId . "),
				( 4001," . $adminThemeId . "),
				( 4002," . $adminThemeId . "),
				( 4101," . $adminThemeId . "),
				( 4102," . $adminThemeId . "),
				( 4104," . $adminThemeId . ")
            ");
            /*} else {
                $adminThemeId = $this->fetchRow("SELECT id FROM `theme` WHERE name = 'admin'");
                $adminThemeId = $adminThemeId[0];
            }*/

            //if($this->ask('create default-project "General"', array('y', 'n'), 'n') === 'y') {
            $this->execute("INSERT INTO `project` 
				(`name`, `description`, `note`, `order_number`, `is_active`, `user_id`, `valid_from`) 
			VALUES ('General', 'General', 'General', 10, '1', '1', now())");
            //}
            $defaultProjectId = $this->fetchRow("SELECT id FROM `project` WHERE name = 'General'");
            $defaultProjectId = $defaultProjectId[0];


            //if($this->ask('create default-user "admin"', array('y', 'n'), 'n') === 'y') {
            $this->execute("
            INSERT INTO `user` (`username`, `password`, `email`, `firstname`, `lastname`, `is_active`, `description`, `note`, `theme_id`, `is_ci_delete_enabled`, `is_relation_edit_enabled`, `is_ldap_auth`, `language`, `layout`, `is_root`, `user_id`, `valid_from`) 
			VALUES
            ('admin', 'admin', 'infocmdb@localhost', 'Admin', 'Admin', '1', 'Admin', 'Admin', " . $adminThemeId . ", '1', '1', '0', 'de', 'admin', '1', '1', now())
            ");
            echo "Created new User 'admin' with password 'admin'\n";

            $adminUserId = $this->fetchRow("SELECT id FROM `user` WHERE username = 'admin'");
            $adminUserId = $adminUserId[0];

            $this->execute("INSERT INTO `user_role` (`user_id`, `role_id`) VALUES (" . $adminUserId . ", " . $adminRoleId . ")");

            $this->execute("INSERT INTO `user_project` (`user_id`,`project_id`) VALUES (" . $adminUserId . ", " . $defaultProjectId . ")");
            //}

            //if($this->ask('create default-attribute-group "General"', array('y', 'n'), 'n') === 'y') {
            $this->execute("INSERT INTO `attribute_group` (`name`,`description`,`note`, `order_number`, `is_active`, `user_id`, `valid_from`) VALUES ('General','General','', 10, '1', '1', now());");
            //}


            echo "\n";
        } else {
			print "SKIPPED migration";
		}
    }

    /**
     * Migrate Down.
     */
    public function down()
    {

    }
}
