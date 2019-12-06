<?php

use PhinxExtend\AbstractPhinxMigration;

class Announcement extends AbstractPhinxMigration
{
    /**
     * Menue_id must be 41 because of hardcoded array index in MenuResources
     * @see MenuResources::getResourceIds()
     *
     * admin theme_id is '1'
     */
    public function up()
    {
        $this->down();

        /*
         * Add Announcements to Menu
         */
        $this->execute("
                    INSERT INTO `" . $this->dbName . "` . `menu` (id, name, description, note, function, order_number, is_active)
                    VALUES('41', 'announcement', 'Ankündigungen', null, 'announcement/index', '44', '1')
        ");

        $this->execute("
                    INSERT INTO `" . $this->dbName . "` . `theme_privilege` (resource_id, theme_id)
                    VALUES
                        ('1501', '1'),
                        ('1502', '1'),
                        ('1503', '1'),
                        ('1504', '1')
        ");

        $this->execute("
                    INSERT INTO `" . $this->dbName . "` . `theme_menu` (theme_id, menue_id)
                    VALUES ('1', '41')
        ");

        /*
         * Announcement
         * History
         */
        $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`announcement` (
                      `id` int(10) unsigned NOT NULL,
                      `name` varchar(255) NOT NULL COMMENT 'intern description',
                      `show_from_date` datetime NOT NULL COMMENT 'starting date for displaying announcement',
                      `show_to_date` datetime NOT NULL COMMENT 'ending date for displaying announcement',
                      `type` enum('information','question','agreement') NOT NULL DEFAULT 'information',
                      `is_active` enum('0','1') NOT NULL DEFAULT '0'  COMMENT 'only active annoucements are shown',
                      `user_id` int(10) unsigned NOT NULL COMMENT 'created/updated this announcement',
                      `user_id_delete` int(10) unsigned NOT NULL COMMENT 'created/updated this entry',
                      `valid_from` datetime NOT NULL COMMENT 'last create/update datetime',
                      `valid_to` datetime NOT NULL COMMENT 'last create/update datetime'
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='announcement definition' AUTO_INCREMENT=1
                ");

        /*
         * Announcement
         * Table
         */
        $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `announcement` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `name` varchar(255) NOT NULL COMMENT 'intern description',
                      `show_from_date` datetime NOT NULL COMMENT 'starting date for displaying announcement',
                      `show_to_date` datetime NOT NULL COMMENT 'ending date for displaying announcement',
                      `type` enum('information','question','agreement') NOT NULL DEFAULT 'information',
                      `is_active` enum('0','1') NOT NULL DEFAULT '0',
                      `user_id` int(10) unsigned NOT NULL COMMENT 'created this announcement',
                      `valid_from` datetime NOT NULL COMMENT 'last create/update datetime',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='announcement definition' AUTO_INCREMENT=1
                ");

        /*
         * Announcement Message
         * History
         */
        $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_history`.`announcement_message` (
                      `id` int(10) unsigned NOT NULL,
                      `announcement_id` int(10) unsigned NOT NULL COMMENT 'anouncement_id from main table',
                      `language` varchar(255) NOT NULL COMMENT 'language of message',
                      `title` varchar(255) NOT NULL COMMENT 'announcement title',
                      `message` text NOT NULL DEFAULT '' COMMENT 'announcement main text',
                      `user_id` int(10) unsigned NOT NULL COMMENT 'created/updated this announcement',
                      `user_id_delete` int(10) unsigned NOT NULL COMMENT 'created/updated this entry',
                      `valid_from` datetime NOT NULL COMMENT 'last create/update datetime',
                      `valid_to` datetime NOT NULL COMMENT 'last create/update datetime'
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='announcement message definition' AUTO_INCREMENT=1
                ");

        /*
         * Announcement Message
         *  Table
         */
        $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `announcement_message` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `announcement_id` int(10) unsigned NOT NULL COMMENT 'anouncement_id from main table',
                      `language` varchar(255) NOT NULL COMMENT 'language of message',
                      `title` varchar(255) NOT NULL COMMENT 'announcement title',
                      `message` text NOT NULL DEFAULT '' COMMENT 'announcement main text',
                      `user_id` int(10) unsigned NOT NULL COMMENT 'created this announcement',
                      `valid_from` datetime NOT NULL COMMENT 'last create/update datetime',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='announcement message definition' AUTO_INCREMENT=1
                ");

        /*
        * Announcements for User
        *  Table
        */
        $this->execute("
                    CREATE TABLE IF NOT EXISTS `" . $this->dbName . "_tables` . `announcement_user` (
                      `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                      `announcement_id` int(10) unsigned NOT NULL COMMENT 'anouncement_id from main table',
                      `user_id` int(10) unsigned NOT NULL COMMENT 'read this announcement',
                      `accept` enum('0','1', 'NULL') DEFAULT 'NULL',
                      `valid_from` datetime NOT NULL COMMENT 'datetime when user accepted/declined',
                      PRIMARY KEY (`id`)
                    ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='announcement message definition' AUTO_INCREMENT=1
                ");

        /*
         * create view for Announcement
         */
        $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".announcement AS
                select *
                from " . $this->dbName . "_tables.announcement
                WITH CASCADED CHECK OPTION
            ");

        /*
        * create view for Announcement Message
        */
        $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".announcement_message AS
                select *
                from " . $this->dbName . "_tables.announcement_message
                WITH CASCADED CHECK OPTION
            ");

        /*
        * create view for Announcements for User
        */
        $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER

                VIEW " . $this->dbName . ".announcement_user AS
                select *
                from " . $this->dbName . "_tables.announcement_user
                WITH CASCADED CHECK OPTION
            ");

        /*
        * Constraint
        */
        $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables` . `announcement_message` ADD CONSTRAINT `FK_announcement_1` FOREIGN KEY `FK_announcement_1` (`announcement_id`)
                    REFERENCES `announcement` (`id`)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE
                    ");

        $this->execute("
                    ALTER TABLE `" . $this->dbName . "_tables` . `announcement_user` ADD CONSTRAINT `FK_announcement_2` FOREIGN KEY `FK_announcement_2` (`announcement_id`)
                    REFERENCES `announcement` (`id`)
                    ON DELETE CASCADE
                    ");


        /*
         * Trigger for Announcement
         */
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`announcement_insert` BEFORE INSERT ON `announcement`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.announcement_update BEFORE UPDATE ON announcement
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.announcement(id, name, show_from_date, show_to_date, type, is_active, user_id, user_id_delete, valid_from, valid_to)
            VALUES(OLD.id, OLD.name, OLD.show_from_date, OLD.show_to_date, OLD.type, OLD.is_active, OLD.user_id,  NEW.user_id, OLD.valid_from, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.announcement_delete BEFORE DELETE ON announcement
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.announcement(id, name, show_from_date, show_to_date, type, is_active, user_id, user_id_delete, valid_from, valid_to)
            VALUES(OLD.id, OLD.name, OLD.show_from_date, OLD.show_to_date, OLD.type, OLD.is_active, OLD.user_id, '0', OLD.valid_from, NOW());
            END;
            ");

        /*
        * Trigger for Announcement Message
        */
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`announcement_message_insert` BEFORE INSERT ON `announcement_message`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.announcement_message_update BEFORE UPDATE ON announcement_message
            FOR EACH ROW BEGIN
            IF NEW.user_id IS NULL THEN
            SET NEW.user_id = 0;
            END IF;

            INSERT INTO " . $this->dbName . "_history.announcement_message(id, announcement_id, language, title, message, user_id, user_id_delete, valid_from, valid_to)
            VALUES(OLD.id, OLD.announcement_id, OLD.language, OLD.title, OLD.message, OLD.user_id, NEW.user_id, OLD.valid_from, NOW());

            SET NEW.valid_from = NOW();
            END;
            ");

        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.announcement_message_delete BEFORE DELETE ON announcement_message
            FOR EACH ROW BEGIN
            INSERT INTO " . $this->dbName . "_history.announcement_message(id, announcement_id, language, title, message, user_id, user_id_delete, valid_from, valid_to)
            VALUES(OLD.id, OLD.announcement_id, OLD.language, OLD.title, OLD.message, OLD.user_id, '0', OLD.valid_from, NOW());
            END;
            ");

        /*
        * Trigger for Announcement User
        */
        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`announcement_user_insert` BEFORE INSERT ON `announcement_user`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");

        $this->execute("
            CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.`announcement_user_update` BEFORE UPDATE ON `announcement_user`
            FOR EACH ROW SET NEW.valid_from = NOW()
            ");
    }

    /*
     * Migrate Down.
     */
    public function down()
    {
        $this->execute("SET FOREIGN_KEY_CHECKS=0");

        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`announcement_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`announcement_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`announcement_delete`");

        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`announcement_message_insert`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`announcement_message_update`");
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`announcement_message_delete`");

        $this->execute("DROP TABLE IF EXISTS " . $this->dbName . "_tables.announcement");
        $this->execute("DROP TABLE IF EXISTS " . $this->dbName . "_history.announcement");
        $this->execute("DROP VIEW IF EXISTS " . $this->dbName . ".announcement");

        $this->execute("DROP TABLE IF EXISTS " . $this->dbName . "_tables.announcement_message");
        $this->execute("DROP TABLE IF EXISTS " . $this->dbName . "_history.announcement_message");
        $this->execute("DROP VIEW IF EXISTS " . $this->dbName . ".announcement_message");

        $this->execute("DROP TABLE IF EXISTS " . $this->dbName . "_tables.announcement_user");
        $this->execute("DROP VIEW IF EXISTS " . $this->dbName . ".announcement_user");

        $this->execute("DELETE FROM " . $this->dbName . ".theme_privilege WHERE resource_id IN ('1501', '1502', '1503', '1504')");
        $this->execute("DELETE FROM " . $this->dbName . ".theme_menu WHERE menue_id = '41'");
        $this->execute("DELETE FROM " . $this->dbName . ".menu WHERE id = '41'");

        $this->execute("SET FOREIGN_KEY_CHECKS=1");
    }
}
