<?php

use PhinxExtend\AbstractPhinxMigration;

class AttributeWorkflow extends AbstractPhinxMigration
{

    /**
     * Migrate Up.
     */
    public function up()
    {
        $attributeTable = $this->table($this->dbName . '_tables.attribute');

        if (!$attributeTable->hasColumn('workflow_id')) {
            $this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` ADD COLUMN `workflow_id` INT NULL AFTER `regex`");
            $this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` ADD COLUMN `workflow_id` INT NULL AFTER `regex`");

            //recreate view
            $this->execute("
                CREATE OR REPLACE ALGORITHM=MERGE
                DEFINER=`root`@`localhost`
                SQL SECURITY DEFINER
                VIEW " . $this->dbName . ".attribute AS
                select *
                from " . $this->dbName . "_tables.attribute
                WITH CASCADED CHECK OPTION
            ");
        }
        $basePath      = dirname(__FILE__) . "/../../public/_uploads/executeable/";
        $migrationPath = $basePath . 'migrated/';
        if (!is_dir($migrationPath)) {
            mkdir($migrationPath, true);
            chmod($migrationPath, 0777);
        }

        $attributesToMigrate = $this->fetchAll("SELECT * FROM attribute WHERE attribute_type_id = 14 AND workflow_id IS NULL");
        foreach ($attributesToMigrate as $attribute) {
            echo "    Handle Attribute-Migration: " . $attribute['name'] . "\n";
            if ($attribute['script_name'] == '') {
                $attribute['script_name'] = $attribute['name'] . '.pl';
            }
            $workflowName    = 'attr_' . $attribute['name'];
            $workflowDesc    = 'Script for attribute ' . $attribute['name'];
            $workflowScript  = $workflowName . '.pl';
            $workflowIsAsync = ($attribute['is_autocomplete'] == '1');

            $executablePath = $basePath . $attribute['script_name'];
            if(is_file($executablePath)) {
                $scriptContent = file_get_contents($executablePath);

                // remove shebang line
                $scriptContent = preg_replace('/^#!.*$/m', '', $scriptContent);

                // make code parameter compatible with new workflow parameters
                $scriptMigrationCode = <<<'HEREDOC'
#!/usr/bin/perl

############ EXECUTABLE MIGRATION ############
use JSON;

my @_ORIG_ARGV = @ARGV;
my $_JSON_ARGS = decode_json($_ORIG_ARGV[0]);
$ARGV[0] = $_JSON_ARGS->{'ciid'};
$ARGV[1] = $_JSON_ARGS->{'userId'};
$ARGV[2] = $_JSON_ARGS->{'apikey'};
############ /EXECUTABLE MIGRATION ############

HEREDOC;
                $scriptContent       = $scriptMigrationCode . $scriptContent;

                $workflowPath     = dirname(__FILE__) . "/../../public/_uploads/workflow/" . $workflowScript;
                $saveScriptResult = file_put_contents($workflowPath, $scriptContent);
                chmod($workflowPath, 0777);

                if ($saveScriptResult !== false) {
                    // flag as processed
                    rename($executablePath, $migrationPath . $attribute['script_name']);

                    $workflow = array(
                        'name'            => $workflowName,
                        'description'     => $workflowDesc,
                        'note'            => '',
                        'execute_user_id' => 1,
                        'is_async'        => $workflowIsAsync,
                        'user_id'         => 0,
                        'response_format' => 'json',
                    );
                    $this->insert('workflow', $workflow);
                    $workflowId = $this->getAdapter()->getConnection()->lastInsertId();

                    if (!$workflowId) {
                        die('Failed to insert workflow: ' . print_r($workflow, true));
                    }

                    $workflowTask = array(
                        'name'       => $workflowScript,
                        'note'       => null,
                        'script'     => $workflowScript,
                        'scriptname' => $workflowScript,
                        'is_async'   => '0',
                    );
                    $this->insert('workflow_task', $workflowTask);
                    $workflowTaskId = $this->getAdapter()->getConnection()->lastInsertId();

                    if (!$workflowTaskId) {
                        die('Failed to insert workflow_task: ' . print_r($workflowTask, true));
                    }

                    $workflowTransition = array(
                        'workflow_id'      => $workflowId,
                        'name'             => $workflowScript,
                        'description'      => $workflowScript,
                        'note'             => '',
                        'trigger'          => 'AUTO',
                        'trigger_time'     => null,
                        'workflow_task_id' => $workflowTaskId,
                        'role_id'          => null,
                    );
                    $this->insert('workflow_transition', $workflowTransition);
                    $workflowTransitionId = $this->getAdapter()->getConnection()->lastInsertId();

                    if (!$workflowTransitionId) {
                        die('Failed to insert workflow_transition: ' . print_r($workflowTransition, true));
                    }


                    $workflowPlaceStart = array(
                        'workflow_id' => $workflowId,
                        'type'        => 1,
                        'name'        => 'start',
                        'description' => 'Start',
                        'note'        => null,
                    );
                    $this->insert('workflow_place', $workflowPlaceStart);
                    $workflowPlaceStartId = $this->getAdapter()->getConnection()->lastInsertId();

                    if (!$workflowPlaceStartId) {
                        die('Failed to insert workflow_place: ' . print_r($workflowPlaceStart, true));
                    }


                    $workflowPlaceEnd = array(
                        'workflow_id' => $workflowId,
                        'type'        => 9,
                        'name'        => 'end',
                        'description' => 'End',
                        'note'        => null,
                    );
                    $this->insert('workflow_place', $workflowPlaceEnd);
                    $workflowPlaceEndId = $this->getAdapter()->getConnection()->lastInsertId();

                    if (!$workflowPlaceEndId) {
                        die('Failed to insert workflow_place: ' . print_r($workflowPlaceEnd, true));
                    }


                    $workflowArcStart = array(
                        'workflow_id'            => $workflowId,
                        'workflow_transition_id' => $workflowTransitionId,
                        'workflow_place_id'      => $workflowPlaceStartId,
                        'direction'              => 'IN',
                        'type'                   => 'SEQ',
                        'condition'              => null,
                    );
                    $this->insert('workflow_arc', $workflowArcStart);
                    $workflowArcStartId = $this->getAdapter()->getConnection()->lastInsertId();

                    if (!$workflowArcStartId) {
                        die('Failed to insert workflow_arc: ' . print_r($workflowArcStart, true));
                    }

                    $workflowArcEnd = array(
                        'workflow_id'            => $workflowId,
                        'workflow_transition_id' => $workflowTransitionId,
                        'workflow_place_id'      => $workflowPlaceEndId,
                        'direction'              => 'OUT',
                        'type'                   => 'SEQ',
                        'condition'              => null,
                    );
                    $this->insert('workflow_arc', $workflowArcEnd);
                    $workflowArcEndId = $this->getAdapter()->getConnection()->lastInsertId();

                    if (!$workflowArcEndId) {
                        die('Failed to insert workflow_arc: ' . print_r($workflowArcEnd, true));
                    }

                    // set attribute workflow
                    $this->execute('UPDATE attribute SET workflow_id = ' . $workflowId . " WHERE id = " . $attribute['id']);
                } else {
                    die('Failed to save script: ' . $workflowPath);
                }
            } else {
                echo "      WARNING: Script not found: " . $executablePath . "\n";
            }

        }

        //$this->execute("ALTER TABLE `" . $this->dbName . "_tables`.`attribute` DROP COLUMN IF EXISTS `script_name`");
        //$this->execute("ALTER TABLE `" . $this->dbName . "_history`.`attribute` DROP COLUMN IF EXISTS `script_name`");

        //attribute - on delete
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_delete`");
        $this->execute("
                CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_delete BEFORE DELETE ON attribute
                    FOR EACH ROW BEGIN
                        INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, workflow_id, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize, display_style)
                        VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.workflow_id, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, '0', NOW(), OLD.historicize, OLD.display_style);
                    END
            ");

        //attribute - on update
        $this->execute("DROP TRIGGER IF EXISTS `" . $this->dbName . "_tables`.`attribute_update`");
        $this->execute("
                CREATE DEFINER = 'root'@localhost TRIGGER `" . $this->dbName . "_tables`.attribute_update BEFORE UPDATE ON attribute
                  FOR EACH ROW BEGIN
                    IF NEW.user_id IS NULL THEN
                      SET NEW.user_id = 0;
                    END IF;
    
                    INSERT INTO " . $this->dbName . "_history.attribute(id, name, description, note, hint, `column`, attribute_type_id, attribute_group_id, order_number, is_unique, is_numeric, is_bold, is_event, is_unique_check, is_autocomplete, is_multiselect, regex, workflow_id, tag, input_maxlength, textarea_cols, textarea_rows, is_active, user_id, valid_from, user_id_delete, valid_to, historicize, display_style)
                    VALUES(OLD.id, OLD.name, OLD.description, OLD.note, OLD.hint, OLD.column, OLD.attribute_type_id, OLD.attribute_group_id, OLD.order_number, OLD.is_unique, OLD.is_numeric, OLD.is_bold, OLD.is_event, OLD.is_unique_check, OLD.is_autocomplete, OLD.is_multiselect, OLD.regex, OLD.workflow_id, OLD.tag, OLD.input_maxlength, OLD.textarea_cols, OLD.textarea_rows, OLD.is_active, OLD.user_id, OLD.valid_from, NEW.user_id, NOW(), OLD.historicize, OLD.display_style);
                    SET NEW.valid_from = NOW();
                  END
            ");

        echo "Validating Migration...\n";
        $valid = true;

        $remainingFiles = scandir($basePath);
        $ignoredFiles   = array(
            '.',
            '..',
            'archive',
            'migrated',
            '.gitkeep',
        );
        foreach ($remainingFiles as $key => $element) {
            if (in_array($element, $ignoredFiles)) {
                unset($remainingFiles[$key]);
            }
        }
        if (count($remainingFiles) > 0) {
            $valid = false;
            echo "  WARNING: Following dirs/files may be used by scripts - Maybe you should move it from executable to the workflow folder?!\n\n";
            echo "    --> " . implode("\n    --> ", $remainingFiles) . "\n\n";
        }

        if ($valid) {
            echo "Validation OK - No impact expected - check workflow scripts to be 100% sure\n";
        } else {
            echo "Validation FAILED - Solve Errors to avoid problems with executables!\n";
        }
    }
}
