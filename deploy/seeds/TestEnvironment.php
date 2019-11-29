<?php

require_once dirname(__FILE__)."/../../application/services/Menu/MenuResources.php";
require_once dirname(__FILE__)."/../../library/composer/autoload.php";

use Phinx\Seed\AbstractSeed;

class TestEnvironment extends AbstractSeed
{

    protected $store        = array();
    protected $fakers       = array();
    protected $generalFaker;

    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $this->generalFaker = Faker\Factory::create();
        $this->fakers['en'] = Faker\Factory::create('en_US');
        $this->fakers['de'] = Faker\Factory::create('de_DE');
        $this->fakers['it'] = Faker\Factory::create('it_IT');
        $this->fakers['fr'] = Faker\Factory::create('fr_FR');
        $this->fakers['es'] = Faker\Factory::create('es_ES');
        $this->fakers['ro'] = Faker\Factory::create('ro_RO');
        $this->fakers['ru'] = Faker\Factory::create('ru_RU');
        $this->fakers['zh'] = Faker\Factory::create('zh_CN');

        // generate the same data on every run
        $seed = 1234;
        $this->generalFaker->seed($seed);
        foreach ($this->fakers as $faker) {
            /** @var $faker Faker\Generator */
            $faker->seed($seed);
        }

         // Permissions
         $this->seedThemes();
         $this->seedProjects();
         $this->seedRoles();
         $this->seedUsers();

         // Administration
         $this->seedMailTemplates();
         $this->seedWorkflowsPerl();
         $this->seedWorkflowsGo();
         $this->seedWorkflowsPerlLongRunningWorkflow();
         $this->seedAttributeGroups();
         $this->seedAttributes();
         $this->seedAnnouncements();
         $this->seedAnnouncementMessagesDe();
         $this->seedAnnouncementMessagesEn();
         $this->seedCiTypes();
         $this->seedCiRelationTypes();
         $this->seedAttributeDetails();
         $this->seedCis();
    }


    public function insertRow($tableName, $row, $rowIdentifier = 'name') {

        $table = $this->table($tableName);
        try {
            $table->insert($row)
                  ->save()
            ;
        } catch (PDOException $e) {
            $message = "TestEnvironment::insertRow failed: \n" .
                "  Message: \n" . $e->getMessage() . "\n\n" .
                "  Parameters: \n  " . print_r(func_get_args(), 1);

            throw new Exception($message);
        }

        $idResult   = $this->fetchRow('SELECT LAST_INSERT_ID()');
        $id         = $idResult[0];

        $this->store[$tableName]['ids'][] = $id;

        if(isset($row[ $rowIdentifier ])) {
            $identifierResult   = $this->fetchRow("SELECT " . $rowIdentifier . " FROM `" . $tableName . "` WHERE id = ".$id);
            $idAlt              = $identifierResult[0];

            $this->store[$tableName][$id]       = $idAlt;
            $this->store[$tableName][$idAlt]    = $id;
        }

        return $id;
    }


    protected function createCiType($entry) {
        $ciTypeId       = $this->insertRow('ci_type', $entry['row']);
        $searchListId   = $this->insertRow('search_list', array('ci_type_id' => $ciTypeId, 'is_active' => '1'), 'ci_type_id');

        foreach($entry['attributes'] as $attributeId => $options) {
            $this->insertRow('ci_type_attribute', array('ci_type_id' => $ciTypeId, 'attribute_id' => $attributeId, 'is_mandatory' => $options['is_mandatory']));
        }

        $order = 1;
        foreach($entry['search_list_attributes'] as $attributeId => $options) {
            $this->insertRow('search_list_attribute', array('search_list_id' => $searchListId, 'attribute_id' => $attributeId, 'order_number' => $order));
            $order++;
        }

        return $ciTypeId;
    }

    protected function createCi($entry) {
        $ciId = $this->insertRow('ci', array('ci_type_id' => $entry['ci_type_id']), null);

        foreach($entry['projects'] as $projectId) {
            $this->insertRow('ci_project', array('ci_id' => $ciId, 'project_id' => $projectId));
        }

        foreach($entry['attributes'] as $attributeId => $options) {
            $row                    = $options;
            $row['ci_id']           = $ciId;
            $row['attribute_id']    = $attributeId;

            $this->insertRow('ci_attribute', $row);
        }

        return $ciId;
    }

    protected function seedThemes() {
        print "Seed Themes\n";

        $themes = array(
            array(
                'row' => array(
                    'name'          => 'author',
                    'description'   => 'Author',
                    'note'          => 'Author Theme',
                    'menu_id'       => 0,
                    'is_active'     => 1,
                ),

                'menu_items'    => array(
                    1, // new_ci
                    2, // search
                    18, // history
                    19, // browse_ci
                )
            ),

            array(
                'row' => array(
                    'name'          => 'reader',
                    'description'   => 'Reader',
                    'note'          => 'Reader Theme',
                    'menu_id'       => 0,
                    'is_active'     => 1,
                ),

                'menu_items'    => array(
                    2, // search
                    19, // browse_ci
                )
            ),
        );

        foreach ($themes as $theme) {
            $themeId = $this->insertRow('theme', $theme['row']);

            foreach ($theme['menu_items'] as $menuId) {
                $this->insertRow('theme_menu', array('theme_id' => $themeId, 'menue_id' => $menuId));

                $resourceIds = MenuResources::getResourceIds($menuId);
                foreach ($resourceIds as $resourceId) {
                    $this->insertRow('theme_privilege', array('theme_id' => $themeId, 'resource_id' => $resourceId));
                }
            }
        }

    }

    protected function seedProjects() {
        print "Seed Projects\n";

        $projects = array(
            array(
                'row' => array(
                    'name'          => 'firestorm',
                    'description'   => 'Firestorm',
                    'note'          => 'Hot data!',
                    'order_number'  => 10,
                    'is_active'     => 1,
                    'user_id'       => 1,
                ),
            ),

            array(
                'row' => array(
                    'name'          => 'hydra',
                    'description'   => 'Hydra Care',
                    'note'          => 'three-way data sync',
                    'order_number'  => 20,
                    'is_active'     => 1,
                    'user_id'       => 1,
                ),
            ),

            array(
                'row' => array(
                    'name'          => 'springfield',
                    'description'   => 'Springfield',
                    'note'          => 'training data',
                    'order_number'  => 30,
                    'is_active'     => 1,
                    'user_id'       => 1,
                ),
            ),

            array(
                'row' => array(
                    'name'          => 'uncopyrightable_cat',
                    'description'   => 'Uncopyrightable Cat',
                    'note'          => 'cat stuff',
                    'order_number'  => 40,
                    'is_active'     => 1,
                    'user_id'       => 1,
                ),
            ),

            array(
                'row' => array(
                    'name'          => 'inactive',
                    'description'   => 'Inactive Project',
                    'note'          => 'Project which is deactivated',
                    'order_number'  => 50,
                    'is_active'     => 0,
                    'user_id'       => 1,
                ),
            ),
        );

        foreach ($projects as $entry) {
            $id = $this->insertRow('project', $entry['row']);
            $this->insertRow('user_project', array('user_id' => 1, 'project_id' => $id));
        }
    }

    protected function seedRoles() {
        print "Seed Roles\n";

        $roles = array(
            array(
                'row' => array(
                    'name'          => 'author',
                    'description'   => 'Author',
                    'note'          => 'can read and edit attributes',
                    'is_active'     => 1,
                    'user_id'       => 1,
                ),
            ),

            array(
                'row' => array(
                    'name'          => 'reader',
                    'description'   => 'Reader',
                    'note'          => 'can read attributes',
                    'is_active'     => 1,
                    'user_id'       => 1,
                ),
            ),
        );

        foreach ($roles as $entry) {
            $id = $this->insertRow('role', $entry['row']);
        }
    }

    protected function seedUsers() {
        print "Seed Users\n";

        $users = array(
            array(
                'row' => array(
                    'username'                      => 'reader',
                    'password'                      => 'reader',
                    'email'                         => 'reader@example.com',
                    'firstname'                     => 'John',
                    'lastname'                      => 'Reader',
                    'description'                   => 'access-r',
                    'note'                          => 'Test Account (r)',
                    'theme_id'                      => $this->store['theme']['reader'],
                    'language'                      => 'de',
                    'layout'                        => 'default',
                    'is_root'                       => '1',
                    'is_ci_delete_enabled'          => '0',
                    'is_relation_edit_enabled'      => '0',
                    'is_ldap_auth'                  => '0',
                    'is_active'                     => '1',
                    'user_id'                       => 1,
                    'is_two_factor_auth'            => '0',
                ),
                'projects' => array(
                    $this->store['project']['hydra'],
                    $this->store['project']['springfield'],
                    $this->store['project']['uncopyrightable_cat'],
                    $this->store['project']['inactive'],
                ),
                'roles' => array(
                    $this->store['role']['reader'],
                )
            ),

            array(
                'row' => array(
                    'username'                      => 'author',
                    'password'                      => 'author',
                    'email'                         => 'author@example.com',
                    'firstname'                     => 'David',
                    'lastname'                      => 'Author',
                    'description'                   => 'access-w',
                    'note'                          => 'Test Account (rw)',
                    'theme_id'                      => $this->store['theme']['author'],
                    'language'                      => 'de',
                    'layout'                        => 'default',
                    'is_root'                       => '1',
                    'is_ci_delete_enabled'          => '1',
                    'is_relation_edit_enabled'      => '1',
                    'is_ldap_auth'                  => '0',
                    'is_active'                     => '1',
                    'user_id'                       => 1,
                    'is_two_factor_auth'            => '0',
                ),
                'projects' => array(
                    $this->store['project']['firestorm'],
                    $this->store['project']['hydra'],
                    $this->store['project']['springfield'],
                    $this->store['project']['uncopyrightable_cat'],
                    $this->store['project']['inactive'],
                ),
                'roles' => array(
                    $this->store['role']['author'],
                )
            ),

            array(
                'row' => array(
                    'username'                      => 'single_project_reader',
                    'password'                      => 'single_project_reader',
                    'email'                         => 'single_project_reader@example.com',
                    'firstname'                     => 'Sinclair',
                    'lastname'                      => 'Reader',
                    'description'                   => 'access-r (1P)',
                    'note'                          => 'Test Account for 1 project (r)',
                    'theme_id'                      => $this->store['theme']['reader'],
                    'language'                      => 'de',
                    'layout'                        => 'default',
                    'is_root'                       => '0',
                    'is_ci_delete_enabled'          => '0',
                    'is_relation_edit_enabled'      => '0',
                    'is_ldap_auth'                  => '0',
                    'is_active'                     => '1',
                    'user_id'                       => 1,
                    'is_two_factor_auth'            => '0',
                ),
                'projects' => array(
                    $this->store['project']['firestorm'],
                ),
                'roles' => array(
                    $this->store['role']['reader'],
                )
            ),

            array(
                'row' => array(
                    'username'                      => 'single_project_author',
                    'password'                      => 'single_project_author',
                    'email'                         => 'single_project_author@example.com',
                    'firstname'                     => 'Sinclair',
                    'lastname'                      => 'Author',
                    'description'                   => 'access-w (1P)',
                    'note'                          => 'Test Account for 1 project (rw)',
                    'theme_id'                      => $this->store['theme']['author'],
                    'language'                      => 'de',
                    'layout'                        => 'default',
                    'is_root'                       => '1',
                    'is_ci_delete_enabled'          => '1',
                    'is_relation_edit_enabled'      => '1',
                    'is_ldap_auth'                  => '0',
                    'is_active'                     => '1',
                    'user_id'                       => 1,
                    'is_two_factor_auth'            => '0',
                ),
                'projects' => array(
                    $this->store['project']['firestorm'],
                ),
                'roles' => array(
                    $this->store['role']['author'],
                )
            ),
        );

        foreach ($users as $entry) {
            $id = $this->insertRow('user', $entry['row'], 'username');

            foreach ($entry['projects'] as $projectId) {
                $this->insertRow('user_project', array('user_id' => $id, 'project_id' => $projectId));
            }

            foreach ($entry['roles'] as $roleId) {
                $this->insertRow('user_role', array('user_id' => $id, 'role_id' => $roleId));
            }
        }

    }

    protected function seedMailTemplates() {
        print "Seed Mail Templates\n";

        $row = array(
            'name'          => 'default',
            'description'   => 'Default Mail Template',
            'note'          => '',
            'subject'       => 'Default',
            'body'          => '<div>:body:</div>',
            'template'      => null,
            'user_id'       => 1,
            'valid_from'    => '2018-03-12 11:02:06',
        );
        $this->insertRow('mail', $row);
    }

    protected function getWorkflowPath() {
        return dirname(__FILE__) . "/../../public/_uploads/workflow/";
    }

    protected function getWorkflowPathGo($WorkflowName) {
		$Path = dirname(__FILE__) .  '/../../data/workflows/golang/'. $WorkflowName . '/';
		if(!is_dir($Path)) {
		    mkdir($Path, 0775);
		}

        return $Path;
    }


    protected function seedWorkflowsPerl() {
        print "Seed a Perl-Workflows\n";

        // workflow
        $row = array(
            'name'              => 'test',
            'description'       => 'Test',
            'note'              => '',
            'execute_user_id'   => 1,
            'is_async'          => '0',
            'user_id'           => 1,
        );

        $workflowId = $this->insertRow('workflow', $row);

        // workflow_task
        $row = array(
            'name'          => 'test.pl',
            'note'          => null,
            'script'        => 'test.pl',
            'scriptname'    => 'test.pl',
            'is_async'      => '0',
        );

        $workflowTaskId = $this->insertRow('workflow_task', $row);

        // workflow_transition
        $row = array(
            'workflow_id'       => $workflowId,
            'name'              => 'test.pl',
            'description'       => 'test.pl',
            'note'              => '',
            'trigger'           => 'AUTO',
            'trigger_time'      => null,
            'workflow_task_id'  => $workflowTaskId,
            'role_id'           => null,
        );

        $workflowTransitionId = $this->insertRow('workflow_transition', $row);

        // workflow place
        $row = array(
            'workflow_id'   => $workflowId,
            'type'          => 1,
            'name'          => 'start',
            'description'   => 'Start',
            'note'          => null,
        );

        $workflowPlaceStartId = $this->insertRow('workflow_place', $row);

        $row = array(
            'workflow_id'   => $workflowId,
            'type'          => 9,
            'name'          => 'end',
            'description'   => 'End',
            'note'          => null,
        );

        $workflowPlaceEndId = $this->insertRow('workflow_place', $row);

        // workflow_arc
        $row = array(
            'workflow_id'               => $workflowId,
            'workflow_transition_id'    => $workflowTransitionId,
            'workflow_place_id'         => $workflowPlaceStartId,
            'direction'                 => 'IN',
            'type'                      => 'SEQ',
            'condition'                 => null,
        );

        $this->insertRow('workflow_arc', $row);

        $row = array(
            'workflow_id'               => $workflowId,
            'workflow_transition_id'    => $workflowTransitionId,
            'workflow_place_id'         => $workflowPlaceEndId,
            'direction'                 => 'OUT',
            'type'                      => 'SEQ',
            'condition'                 => null,
        );

        $this->insertRow('workflow_arc', $row);



        $workflowScript = "
#!/usr/bin/perl

use Data::Dumper;


print \"Hello World!\\n\";

print Dumper \@ARGV;
        ";

        $path = $this->getWorkflowPath() . 'test.pl';
        file_put_contents($path, $workflowScript);
    }

    /*
        This workflow is used to testing workflow log/execution of workflows with a lot output and errors
    */
    protected function seedWorkflowsPerlLongRunningWorkflow() {
        print "Seed a Perl-Workflows for testing a lot of output and errors\n";

        // workflow
        $row = array(
            'name'              => 'test_long_output_workflow',
            'description'       => 'Test with long output and errors',
            'note'              => '',
            'execute_user_id'   => 1,
            'is_async'          => '0',
            'user_id'           => 1,
        );

        $workflowId = $this->insertRow('workflow', $row);

        // workflow_task
        $row = array(
            'name'          => 'test_long_output_workflow.pl',
            'note'          => null,
            'script'        => 'test_long_output_workflow.pl',
            'scriptname'    => 'test_long_output_workflow.pl',
            'is_async'      => '0',
        );

        $workflowTaskId = $this->insertRow('workflow_task', $row);

        // workflow_transition
        $row = array(
            'workflow_id'       => $workflowId,
            'name'              => 'test_long_output_workflow.pl',
            'description'       => 'test_long_output_workflow.pl',
            'note'              => '',
            'trigger'           => 'AUTO',
            'trigger_time'      => null,
            'workflow_task_id'  => $workflowTaskId,
            'role_id'           => null,
        );

        $workflowTransitionId = $this->insertRow('workflow_transition', $row);

        // workflow place
        $row = array(
            'workflow_id'   => $workflowId,
            'type'          => 1,
            'name'          => 'start',
            'description'   => 'Start',
            'note'          => null,
        );

        $workflowPlaceStartId = $this->insertRow('workflow_place', $row);

        $row = array(
            'workflow_id'   => $workflowId,
            'type'          => 9,
            'name'          => 'end',
            'description'   => 'End',
            'note'          => null,
        );

        $workflowPlaceEndId = $this->insertRow('workflow_place', $row);

        // workflow_arc
        $row = array(
            'workflow_id'               => $workflowId,
            'workflow_transition_id'    => $workflowTransitionId,
            'workflow_place_id'         => $workflowPlaceStartId,
            'direction'                 => 'IN',
            'type'                      => 'SEQ',
            'condition'                 => null,
        );

        $this->insertRow('workflow_arc', $row);

        $row = array(
            'workflow_id'               => $workflowId,
            'workflow_transition_id'    => $workflowTransitionId,
            'workflow_place_id'         => $workflowPlaceEndId,
            'direction'                 => 'OUT',
            'type'                      => 'SEQ',
            'condition'                 => null,
        );

        $this->insertRow('workflow_arc', $row);

        $workflowScript = <<<"WORKFLOWSCRIPT"
#!/usr/bin/perl
sub getMicroTime() {
	my (\$time, \$microsec) = gettimeofday();
	return \$time + \$microsec;
}
use Time::HiRes qw(gettimeofday tv_interval);
my \$starttime = [gettimeofday];
printf "[%f] Starting Test", gettimeofday();
printf STDERR "[%f] STDERR: Starting Test", gettimeofday();

use Data::Dumper;
my \$lines = 10000; # lines to print

NEXTLINE:
for(my \$i = 0 ; \$i < \$lines ; \$i++ ) {
    if((\$i % 10) == 0) {
        printf STDERR "[%f] STDERR: Output on (count: %d)\\n", getMicroTime(), \$i;
        next NEXTLINE;
    }
    
    printf "[%f] Output on (count: %d)\\n", getMicroTime(), \$i;
}

printf "[%f] done Test (Duration: %f)\\n", getMicroTime(), tv_interval(\$starttime);
printf STDERR "[%f] STDERR: done Test (Duration: %f)\\n", getMicroTime(), tv_interval(\$starttime);
WORKFLOWSCRIPT;

        $path = $this->getWorkflowPath() . 'test_long_output_workflow.pl';
        file_put_contents($path, $workflowScript);
    }

    protected function seedWorkflowsGo() {
        print "Seed a Go-Workflows\n";

        // workflow
        $workflow = array(
            'name'              => 'test-go',
            'description'       => 'Test-go',
            'note'              => '',
            'execute_user_id'   => 1,
            'is_async'          => '0',
			'script_lang'		=> 'golang',
            'user_id'           => 1,
        );

        $workflowId = $this->insertRow('workflow', $workflow);

        // workflow_task
        $row = array(
            'name'          => 'test.go',
            'note'          => null,
            'script'        => 'test.go',
            'scriptname'    => 'test.go',
            'is_async'      => '0',
        );

        $workflowTaskId = $this->insertRow('workflow_task', $row);

        // workflow_transition
        $row = array(
            'workflow_id'       => $workflowId,
			'name'              => 'test.go',
            'description'       => 'test.go',
            'note'              => '',
            'trigger'           => 'AUTO',
            'trigger_time'      => null,
            'workflow_task_id'  => $workflowTaskId,
            'role_id'           => null,
        );

        $workflowTransitionId = $this->insertRow('workflow_transition', $row);

        // workflow place
        $row = array(
            'workflow_id'   => $workflowId,
            'type'          => 1,
            'name'          => 'start',
            'description'   => 'Start',
            'note'          => null,
        );

        $workflowPlaceStartId = $this->insertRow('workflow_place', $row);

        $row = array(
            'workflow_id'   => $workflowId,
            'type'          => 9,
            'name'          => 'end',
            'description'   => 'End',
            'note'          => null,
        );

        $workflowPlaceEndId = $this->insertRow('workflow_place', $row);

        // workflow_arc
        $row = array(
            'workflow_id'               => $workflowId,
            'workflow_transition_id'    => $workflowTransitionId,
            'workflow_place_id'         => $workflowPlaceStartId,
            'direction'                 => 'IN',
            'type'                      => 'SEQ',
            'condition'                 => null,
        );

        $this->insertRow('workflow_arc', $row);

        $row = array(
            'workflow_id'               => $workflowId,
            'workflow_transition_id'    => $workflowTransitionId,
            'workflow_place_id'         => $workflowPlaceEndId,
            'direction'                 => 'OUT',
            'type'                      => 'SEQ',
            'condition'                 => null,
        );

        $this->insertRow('workflow_arc', $row);



        $workflowScript = <<<"GOLANG"
package main

////////////////////////////// DOCHEAD //////////////////////////////
// DO NOT EDIT - This part will be set automatically
//
// Workflow-ID:       2
// Workflow-Name:     test-go
// Trigger-Type:      manual
// Response-Format:   json
// Sync/Async:        sync
// Last Updated At:   2019-01-28 14:35:00
// Last Author:       Admin Admin (admin)
// Description:       Test-go
//
////////////////////////////// /DOCHEAD //////////////////////////////

import "os"
import "encoding/json"
import "log"
import "fmt"

type WorkflowParams struct {
	Apikey              string `json:"apikey"`
	TriggerType         string `json:"triggerType"`
	WorkflowItemId      int    `json:"workflow_item_id,string"`
	WorkflowInstanceId  int    `json:"workflow_instance_id,string"`
	CiId                int    `json:"ciid,string"`
	CiAttributeId       int    `json:"ciAttributeId,string"`
	CiRelationId        int    `json:"ciRelationId,string"`
	CiProjectId         int    `json:"ciProjectId,string"`
	FileImportHistoryId int    `json:"fileImportHistoryId,string"`
}

func main() {
	params, parseErr := parseParams()
	if parseErr != nil {
		log.Fatal(parseErr)
	}

	err := Run(params)
	if err != nil {
		log.Fatal(err)
	}
}

func parseParams() (WorkflowParams, error) {
	jsonParam := os.Args[1]
	var params WorkflowParams
	jsonErr := json.Unmarshal([]byte(jsonParam), &params)
	if jsonErr != nil {
		return params, jsonErr
	}

	return params, nil
}

func Run(p WorkflowParams) error {
	fmt.Printf("%+v", p)
	return nil
}
GOLANG;

        $path = $this->getWorkflowPathGo($workflow["name"]) . 'test.go';
        file_put_contents($path, $workflowScript);
		$workflowTestScript = <<<"GOLANG"
package main

import "testing"

func TestRun(t *testing.T) {

}
GOLANG;

        $path = $this->getWorkflowPathGo($workflow["name"]) . 'test_test.go';
        file_put_contents($path, $workflowTestScript);
    }

    protected function seedAttributeGroups() {
        print "Seed Attribute Groups\n";

        $data = array(
            array(
                'row' => array(
                    'name'          => 'personal_information',
                    'description'   => 'Personal',
                    'note'          => 'Personal Information',
                    'order_number'  => '5',
                    'is_active'     => '1',
                    'user_id'       => '1',
                    'valid_from'    => date('Y-m-d H:i:s'),
                ),
            ),
            array(
                'row' => array(
                    'name'          => 'technical',
                    'description'   => 'Technical',
                    'note'          => 'Technical Information',
                    'order_number'  => '20',
                    'is_active'     => '1',
                    'user_id'       => '1',
                    'valid_from'    => date('Y-m-d H:i:s'),
                ),
            ),
            array(
                'row' => array(
                    'name'          => 'accounting_information',
                    'description'   => 'Accounting',
                    'note'          => 'Accounting Information',
                    'order_number'  => '30',
                    'is_active'     => '1',
                    'user_id'       => '1',
                    'valid_from'    => date('Y-m-d H:i:s'),
                ),
            ),
            array(
                'row' => array(
                    'name'          => 'provisioning',
                    'description'   => 'Provisioning',
                    'note'          => 'Provisioning Information',
                    'order_number'  => '200',
                    'is_active'     => '1',
                    'user_id'       => '1',
                    'valid_from'    => date('Y-m-d H:i:s'),
                ),
            ),
            array(
                'row' => array(
                    'name'          => 'inactive',
                    'description'   => 'Inactive',
                    'note'          => 'Inactive Attribute Group',
                    'order_number'  => '250',
                    'is_active'     => '0',
                    'user_id'       => '1',
                    'valid_from'    => date('Y-m-d H:i:s'),
                ),
            ),
        );

        foreach($data as $entry) {
            $this->insertRow('attribute_group', $entry['row']);
        }

        $data = array(
            array(
                'row' => array(
                    'name'                      => 'technical_basic',
                    'description'               => 'Basic',
                    'note'                      => 'Basic Information',
                    'order_number'              => '10',
                    'parent_attribute_group_id' => $this->store['attribute_group']['technical'],
                    'is_active'                 => '1',
                    'user_id'                   => '1',
                    'valid_from'                => date('Y-m-d H:i:s'),
                ),
            ),
            array(
                'row' => array(
                    'name'                      => 'technical_detail',
                    'description'               => 'Technical Detail',
                    'note'                      => 'Detail Information',
                    'order_number'              => '20',
                    'parent_attribute_group_id' => $this->store['attribute_group']['technical'],
                    'is_active'                 => '1',
                    'user_id'                   => '1',
                    'valid_from'                => date('Y-m-d H:i:s'),
                ),
            ),
            array(
                'row' => array(
                    'name'                      => 'accounting_information_detail',
                    'description'               => 'Technical Detail',
                    'note'                      => 'Detail Information',
                    'order_number'              => '10',
                    'parent_attribute_group_id' => $this->store['attribute_group']['accounting_information'],
                    'is_active'                 => '1',
                    'user_id'                   => '1',
                    'valid_from'                => date('Y-m-d H:i:s'),
                ),
            ),
        );

        foreach($data as $entry) {
            $this->insertRow('attribute_group', $entry['row']);
        }

        $data = array(
            array(
                'row' => array(
                    'name'                      => 'technical_detail_windows',
                    'description'               => 'Windows',
                    'note'                      => 'Windows OS Information',
                    'order_number'              => '10',
                    'parent_attribute_group_id' => $this->store['attribute_group']['technical_detail'],
                    'is_active'                 => '1',
                    'user_id'                   => '1',
                    'valid_from'                => date('Y-m-d H:i:s'),
                ),
            ),
            array(
                'row' => array(
                    'name'                      => 'technical_detail_linux',
                    'description'               => 'Linux',
                    'note'                      => 'Linux OS Information',
                    'order_number'              => '10',
                    'parent_attribute_group_id' => $this->store['attribute_group']['technical_detail'],
                    'is_active'                 => '1',
                    'user_id'                   => '1',
                    'valid_from'                => date('Y-m-d H:i:s'),
                ),
            ),
        );

        foreach($data as $entry) {
            $this->insertRow('attribute_group', $entry['row']);
        }
    }

    protected function seedAttributes() {
        print "Seed Attributes\n";

        $attributeTemplate = array(
            'name'                  => '',
            'description'           => '',
            'note'                  => '',
            'hint'                  => '',
            'attribute_type_id'     => 1,
            'attribute_group_id'    => 1,
            'order_number'          => 10,
            'column'                => 1,
            'is_unique'             => '0',
            'is_numeric'            => '0',
            'is_bold'               => '0',
            'is_event'              => '0',
            'is_unique_check'       => '0',
            'is_autocomplete'       => '0',
            'is_multiselect'        => '0',
            'is_project_restricted' => '0',
            'regex'                 => null,
            'script_name'           => null,
            'tag'                   => '',
            'input_maxlength'       => null,
            'textarea_cols'         => null,
            'textarea_rows'         => null,
            'is_active'             => '1',
            'user_id'               => 1,
            'valid_from'            => date('Y-m-d H:i:s'),
            'historicize'           => '1',
            'display_style'         => null
        );


        $data = array();
        $attributeOrderNr = 1;

        // Demo-Attributes: add one attribute of each type

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_unique_input';
        $attribute['description']           = 'Unique Input';
        $attribute['note']                  = 'Input Note';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_unique']             = '1';
        $attribute['is_unique_check']       = '1';
        $attribute['is_bold']               = '1';

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'no_hint_icon';
        $attribute['description']           = 'No Hint Icon';
        $attribute['hint']                  = '';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_regular_input';
        $attribute['description']           = 'Regular Input';
        $attribute['note']                  = 'Input Note';
        $attribute['hint']                  = '<div><b>Input Hint</b></div>';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_numeric_input';
        $attribute['description']           = 'Numeric Input';
        $attribute['note']                  = 'Input Note';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_numeric']            = '1';

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_textarea';
        $attribute['description']           = 'Textarea';
        $attribute['note']                  = 'Textarea Note';
        $attribute['hint']                  = '<div><b>Textarea Hint</b></div>';
        $attribute['attribute_type_id']     = 2;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_textedit';
        $attribute['description']           = 'Editor Area';
        $attribute['note']                  = 'Editor Note';
        $attribute['hint']                  = '<div><b>Editor Hint</b></div>';
        $attribute['attribute_type_id']     = 3;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_static';
        $attribute['description']           = 'Dropdown (static)';
        $attribute['note']                  = 'Dropdown Note';
        $attribute['hint']                  = '<div><b>Dropdown Hint</b></div>';
        $attribute['attribute_type_id']     = 4;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_static_ampel';
        $attribute['description']           = 'Ampel';
        $attribute['note']                  = 'colorful visiualization of dropdown_static';
        $attribute['hint']                  = '<div><b>Ampel hint</b></div>';
        $attribute['attribute_type_id']     = 15;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_event']              = 1;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_checkbox';
        $attribute['description']           = 'Checkbox';
        $attribute['note']                  = 'Checkbox Note';
        $attribute['hint']                  = '<div><b>Checkbox Hint</b></div>';
        $attribute['attribute_type_id']     = 5;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_radio';
        $attribute['description']           = 'Radio';
        $attribute['note']                  = 'Radio Note';
        $attribute['hint']                  = '<div><b>Radio Hint</b></div>';
        $attribute['attribute_type_id']     = 6;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_date';
        $attribute['description']           = 'Date';
        $attribute['note']                  = 'Date Note';
        $attribute['hint']                  = '<div><b>Date Hint</b></div>';
        $attribute['attribute_type_id']     = 7;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_datetime';
        $attribute['description']           = 'Datetime';
        $attribute['note']                  = 'Datetime Note';
        $attribute['hint']                  = '<div><b>Datetime Hint</b></div>';
        $attribute['attribute_type_id']     = 8;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_currency';
        $attribute['description']           = 'Currency';
        $attribute['note']                  = 'Currency Note';
        $attribute['hint']                  = '<div><b>Currency Hint</b></div>';
        $attribute['attribute_type_id']     = 9;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_password';
        $attribute['description']           = 'Password';
        $attribute['note']                  = 'Password Note';
        $attribute['hint']                  = '<div><b>Password Hint</b></div>';
        $attribute['attribute_type_id']     = 10;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_hyperlink';
        $attribute['description']           = 'Hyperlink';
        $attribute['note']                  = 'Link Note';
        $attribute['hint']                  = '<div><b>Link Hint</b></div>';
        $attribute['attribute_type_id']     = 11;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_attachment';
        $attribute['description']           = 'Attachment';
        $attribute['note']                  = 'Attachment Note';
        $attribute['hint']                  = '<div><b>Attachment Hint</b></div>';
        $attribute['attribute_type_id']     = 12;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        # TODO: maybe add attribute-type "script" later


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_regular_executable';
        $attribute['description']           = 'Executable Script';
        $attribute['note']                  = 'Executable Script Note';
        $attribute['hint']                  = '<div><b>Executable Script Hint</b></div>';
        $attribute['attribute_type_id']     = 14;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_event']              = '0';
        $attribute['workflow_id']           = 1;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_event_executable';
        $attribute['description']           = 'Event Executable';
        $attribute['note']                  = 'Event Executable Note';
        $attribute['hint']                  = '<div><b>Event Executable Hint</b></div>';
        $attribute['attribute_type_id']     = 14;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_event']              = '1';
        $attribute['workflow_id']           = 1;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_query';
        $attribute['description']           = 'Query';
        $attribute['note']                  = 'Query Note';
        $attribute['hint']                  = '<div><b>Query Hint</b></div>';
        $attribute['attribute_type_id']     = 15;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_citype';
        $attribute['description']           = 'Dropdown (CI Type)';
        $attribute['note']                  = 'Dropdown Note';
        $attribute['hint']                  = '<div><b>Dropdown Hint</b></div>';
        $attribute['attribute_type_id']     = 16;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_fixed_info';
        $attribute['description']           = 'Fixed Info';
        $attribute['note']                  = 'Info Note';
        $attribute['hint']                  = '<div><b>Info Hint</b></div>';
        $attribute['attribute_type_id']     = 17;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_query_persistent';
        $attribute['description']           = 'Query (persistent)';
        $attribute['note']                  = 'Query Note';
        $attribute['hint']                  = '<div><b>Query Hint</b></div>';
        $attribute['attribute_type_id']     = 18;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_citype_persistent';
        $attribute['description']           = 'Dropdown (CI Type persistent)';
        $attribute['note']                  = 'Dropdown Note';
        $attribute['hint']                  = '<div><b>Dropdown Hint</b></div>';
        $attribute['attribute_type_id']     = 19;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_sql_filled_select';
        $attribute['description']           = 'Dropdown (SQL filled) - Regular Input';
        $attribute['note']                  = 'Dropdown Note';
        $attribute['hint']                  = '<div><b>Dropdown Hint</b></div>';
        $attribute['attribute_type_id']     = 21;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_autocomplete']       = '0';
        $attribute['is_multiselect']        = '0';
        $attribute['is_project_restricted'] = '0';
        $attribute['display_style']         = 'optionList';

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_sql_filled_autocomplete';
        $attribute['description']           = 'Dropdown (SQL filled) - Autocomplete';
        $attribute['note']                  = 'Dropdown Note';
        $attribute['hint']                  = '<div><b>Dropdown Hint</b></div>';
        $attribute['attribute_type_id']     = 21;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_autocomplete']       = '1';
        $attribute['is_multiselect']        = '0';
        $attribute['is_project_restricted'] = '0';
        $attribute['display_style']         = 'optionList';

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_sql_filled_multiselect';
        $attribute['description']           = 'Dropdown (SQL filled) - Multiselect';
        $attribute['note']                  = 'Dropdown Note';
        $attribute['hint']                  = '<div><b>Dropdown Hint</b></div>';
        $attribute['attribute_type_id']     = 21;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_autocomplete']       = '0';
        $attribute['is_multiselect']        = '1';
        $attribute['is_project_restricted'] = '0';
        $attribute['display_style']         = 'optionList';

        $data[] = $attribute;
        $attributeOrderNr++;


        $attribute = $attributeTemplate;
        $attribute['name']                  = 'general_dropdown_sql_filled_multiselect_counter';
        $attribute['description']           = 'Dropdown (SQL filled) - Multiselect Counter';
        $attribute['note']                  = 'Dropdown Note';
        $attribute['hint']                  = '<div><b>Dropdown Hint</b></div>';
        $attribute['attribute_type_id']     = 21;
        $attribute['order_number']          = $attributeOrderNr;
        $attribute['is_autocomplete']       = '0';
        $attribute['is_multiselect']        = '2';
        $attribute['is_project_restricted'] = '0';
        $attribute['display_style']         = 'optionList';

        $data[] = $attribute;
        $attributeOrderNr++;




        // Employee-Attributes
        $attributeOrderNr                           = 1;
        $attributeTemplate['attribute_group_id']    = $this->store['attribute_group']['personal_information'];

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_staff_number';
        $attribute['description']           = 'Staff Number';
        $attribute['note']                  = 'Personal Number';
        $attribute['attribute_type_id']     = 1;
        $attribute['is_numeric']            = '1';
        $attribute['is_unique']             = '1';
        $attribute['is_unique_check']       = '1';
        $attribute['is_bold']               = '1';
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_email_address';
        $attribute['description']           = 'Email Address';
        $attribute['note']                  = '';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_firstname';
        $attribute['description']           = 'Firstname';
        $attribute['note']                  = 'Firstname of Employee';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_lastname';
        $attribute['description']           = 'Lastname';
        $attribute['note']                  = 'Lastname of Employee';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_location_code';
        $attribute['description']           = 'Location Code';
        $attribute['note']                  = 'Location Code of Employee';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attributeOrderNr                           = 1;
        $attributeTemplate['attribute_group_id']    = $this->store['attribute_group']['technical_basic'];

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_ad_username';
        $attribute['description']           = 'Active Directory Username';
        $attribute['note']                  = 'Samaccountname';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_client_os_family';
        $attribute['description']           = 'OS Family';
        $attribute['note']                  = 'Which operating system family is installed on client?';
        $attribute['attribute_type_id']     = 4;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attributeOrderNr                           = 1;
        $attributeTemplate['attribute_group_id']    = $this->store['attribute_group']['technical_detail_windows'];

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_client_win_version';
        $attribute['description']           = 'Windows Version';
        $attribute['note']                  = '';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attributeOrderNr                           = 1;
        $attributeTemplate['attribute_group_id']    = $this->store['attribute_group']['technical_detail_linux'];

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'emp_client_linux_distro';
        $attribute['description']           = 'Linux Distro';
        $attribute['note']                  = 'Linux Distribution';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;


        $attributeOrderNr                           = 1;
        $attributeTemplate['attribute_group_id']    = 1; // General

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'site_name';
        $attribute['description']           = 'Name';
        $attribute['note']                  = '';
        $attribute['attribute_type_id']     = 1;
        $attribute['is_numeric']            = '1';
        $attribute['is_unique']             = '1';
        $attribute['is_unique_check']       = '1';
        $attribute['is_bold']               = '1';
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'site_country';
        $attribute['description']           = 'Country';
        $attribute['note']                  = 'Country';
        $attribute['attribute_type_id']     = 1;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'site_address';
        $attribute['description']           = 'Address';
        $attribute['note']                  = '';
        $attribute['attribute_type_id']     = 2;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'site_location_code';
        $attribute['description']           = 'Location Code';
        $attribute['note']                  = '';
        $attribute['attribute_type_id']     = 2;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'site_headcount';
        $attribute['description']           = 'Headcount';
        $attribute['note']                  = 'No. of employees on site';
        $attribute['attribute_type_id']     = 15;
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;

        $attribute = $attributeTemplate;
        $attribute['name']                  = 'site_identifier';
        $attribute['description']           = 'Identifier';
        $attribute['note']                  = 'Site Identifier with regex';
        $attribute['attribute_type_id']     = 1;
        $attribute['regex']                 = '/^sn.*?$/';
        $attribute['order_number']          = $attributeOrderNr;

        $data[] = $attribute;
        $attributeOrderNr++;



        foreach($data as $row) {
            $id = $this->insertRow('attribute', $row);

            $this->insertRow('attribute_role', array('attribute_id' => $id, 'role_id' => $this->store['role']['reader'], 'permission_read' => 1, 'permission_write' => 0));
            $this->insertRow('attribute_role', array('attribute_id' => $id, 'role_id' => $this->store['role']['author'], 'permission_read' => 1, 'permission_write' => 1));
            $this->insertRow('attribute_role', array('attribute_id' => $id, 'role_id' => 1, 'permission_read' => 1, 'permission_write' => 1));
        }

    }

    protected function seedAnnouncements(){
        print "Seed Announcements\n";

        //  predefined announcements
        $data1 = array(
            array(
                'row' => array(
                    'name'            => 'announcement1_forfiltering',
                    'show_from_date'  => '1900-06-30 03:20:15',
                    'show_to_date'    => '3000-08-10 05:10:30',
                    'type'            => 'information',
                    'is_active'       => 0,
                    'user_id'         => 1,
                ),
            ),
            array(
                'row' => array(
                    'name'            => 'announcement2_inthefuture',
                    'show_from_date'  => '2900-06-30 03:20:15',
                    'show_to_date'    => '3000-08-10 05:10:30',
                    'type'            => 'question',
                    'is_active'       => 1,
                    'user_id'         => 1,
                ),
            ),
            array(
                'row' => array(
                    'name'            => 'announcement3_inthepast',
                    'show_from_date'  => '1900-06-30 03:20:15',
                    'show_to_date'    => '1910-08-10 05:10:30',
                    'type'            => 'agreement',
                    'is_active'       => 1,
                    'user_id'         => 1,
                ),
            ),
        );

        foreach($data1 as $entry) {
            $this->insertRow('announcement', $entry['row']);
        }

        //  random announcements
        $data2 = array();
        for ($i=0; $i < 30; $i++) {
            $faker = $this->generalFaker->randomElement($this->fakers);
            $row = array(
                'row' => array(
                    'name'           => $this->generalFaker->word,
                    'show_from_date'  => $faker->time('Y-m-d H:i:s'),
                    'show_to_date'    => $faker->time('Y-m-d H:i:s'),
                    'is_active'       => 0,
                    'user_id'         => 1,
                ),
            );

            $data2[] = $row;
        }

          foreach($data2 as $entry) {
              $this->insertRow('announcement', $entry['row'], 'id');
          }
    }

    protected function seedAnnouncementMessagesDe(){
        print "Seed Announcement German Messages\n";

        $id=1;

        //  predefined announcements
        $data1 = array(
            array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => 'aaa_Headline1_de',
                    'message'         => '<div>Text1_de</div>',
                    'language'        => 'de',
                    'user_id'         => 1,
                ),
            ),
            array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => 'Headline2_de',
                    'message'         => '<div>Text2_de</div>',
                    'language'        => 'de',
                    'user_id'         => 1,
                ),
            ),
            array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => 'Headline3_de',
                    'message'         => '<div>Text3_de</div>',
                    'language'        => 'de',
                    'user_id'         => 1,
                ),
            ),
        );

        foreach($data1 as $entry) {
            $this->insertRow('announcement_message', $entry['row'], 'id');
        }

        //  random announcements
        $data2 = array();
        for ($i=0; $i < 30; $i++) {
            $faker = $this->generalFaker->randomElement($this->fakers);
            $row = array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => $this->generalFaker->word,
                    'message'         => '<div>' . $faker->sentence($nbWords = 100, $variableNbWords = true) . '</div>',
                    'language'        => 'de',
                    'user_id'         => 1,
                ),
            );

            $data2[] = $row;
        }

        foreach($data2 as $entry) {
            $this->insertRow('announcement_message', $entry['row'], 'id');
        }
    }

    protected function seedAnnouncementMessagesEn(){
        print "Seed Announcement English Messages\n";

        $id=1;

        //  predefined announcements
        $data1 = array(
            array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => 'zzz_Headline1_en',
                    'message'         => '<div>Text1_en</div>',
                    'language'        => 'en',
                    'user_id'         => 1,
                ),
            ),

            array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => 'Headline2_en',
                    'message'         => '<div>Text2_en</div>',
                    'language'        => 'en',
                    'user_id'         => 1,
                ),
            ),

            array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => 'Headline3_en',
                    'message'         => '<div>Text3_en</div>',
                    'language'        => 'en',
                    'user_id'         => 1,
                ),
            ),
        );

        foreach($data1 as $entry) {
            $this->insertRow('announcement_message', $entry['row'], 'id');
        }

        //  random announcements
        $data2 = array();
        for ($i=0; $i < 30; $i++) {
            $faker = $this->generalFaker->randomElement($this->fakers);
            $row = array(
                'row' => array(
                    'announcement_id' => $id++,
                    'title'           => $this->generalFaker->word,
                    'message'         => '<div>' . $faker->sentence($nbWords = 100, $variableNbWords = true) . '</div>',
                    'language'        => 'en',
                    'user_id'         => 1,
                ),
            );

            $data2[] = $row;
        }

        foreach($data2 as $entry) {
            $this->insertRow('announcement_message', $entry['row'], 'id');
        }
    }

    protected function seedCiTypes() {
        print "Seed Ci-Types\n";

        $ciTypes = array(
            array(
                'row' => array(
                    'name'                      => 'demo',
                    'description'               => 'Demo',
                    'note'                      => 'One attribute of every type',
                    'parent_ci_type_id'         => 0,
                    'order_number'              => '10',
                    'default_project_id'        => null,
                    'default_attribute_id'      => null,
                    'default_sort_attribute_id' => null,
                    'is_default_sort_asc'       => '1',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(
                    $this->store['attribute']['general_unique_input']                               => array('is_mandatory' => 1),
                    $this->store['attribute']['no_hint_icon']                                       => array('is_mandatory' => 0),
                    $this->store['attribute']['general_regular_input']                              => array('is_mandatory' => 0),
                    $this->store['attribute']['general_numeric_input']                              => array('is_mandatory' => 0),
                    $this->store['attribute']['general_textarea']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_textedit']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_static']                            => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_static_ampel']                      => array('is_mandatory' => 0),
                    $this->store['attribute']['general_checkbox']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_radio']                                      => array('is_mandatory' => 0),
                    $this->store['attribute']['general_date']                                       => array('is_mandatory' => 0),
                    $this->store['attribute']['general_datetime']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_currency']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_password']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_hyperlink']                                  => array('is_mandatory' => 0),
                    $this->store['attribute']['general_attachment']                                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_regular_executable']                         => array('is_mandatory' => 0),
                    $this->store['attribute']['general_event_executable']                           => array('is_mandatory' => 0),
                    $this->store['attribute']['general_query']                                      => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_citype']                            => array('is_mandatory' => 0),
                    $this->store['attribute']['general_fixed_info']                                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_query_persistent']                           => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_citype_persistent']                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_autocomplete']           => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('is_mandatory' => 0),
                ),

                'search_list_attributes' => array(
                    $this->store['attribute']['general_unique_input']                               => array('order_number' => 1),
                    $this->store['attribute']['no_hint_icon']                                       => array('order_number' => 2),
                    $this->store['attribute']['general_regular_input']                              => array('order_number' => 3),
                    $this->store['attribute']['general_numeric_input']                              => array('order_number' => 4),
                    $this->store['attribute']['general_textarea']                                   => array('order_number' => 5),
                    $this->store['attribute']['general_textedit']                                   => array('order_number' => 6),
                    $this->store['attribute']['general_dropdown_static']                            => array('order_number' => 7),
                    $this->store['attribute']['general_dropdown_static_ampel']                      => array('order_number' => 8),
                    $this->store['attribute']['general_checkbox']                                   => array('order_number' => 9),
                    $this->store['attribute']['general_radio']                                      => array('order_number' => 10),
                    $this->store['attribute']['general_date']                                       => array('order_number' => 11),
                    $this->store['attribute']['general_datetime']                                   => array('order_number' => 12),
                    $this->store['attribute']['general_currency']                                   => array('order_number' => 13),
                    $this->store['attribute']['general_password']                                   => array('order_number' => 14),
                    $this->store['attribute']['general_hyperlink']                                  => array('order_number' => 15),
                    $this->store['attribute']['general_attachment']                                 => array('order_number' => 16),
                    $this->store['attribute']['general_regular_executable']                         => array('order_number' => 17),
                    $this->store['attribute']['general_event_executable']                           => array('order_number' => 18),
                    $this->store['attribute']['general_query']                                      => array('order_number' => 19),
                    $this->store['attribute']['general_dropdown_citype']                            => array('order_number' => 20),
                    $this->store['attribute']['general_fixed_info']                                 => array('order_number' => 21),
                    $this->store['attribute']['general_query_persistent']                           => array('order_number' => 22),
                    $this->store['attribute']['general_dropdown_citype_persistent']                 => array('order_number' => 23),
                    $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('order_number' => 24),
                    $this->store['attribute']['general_dropdown_sql_filled_autocomplete']           => array('order_number' => 25),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('order_number' => 26),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('order_number' => 27),
                ),
            ),

            array(
                'row' => array(
                    'name'                      => 'demo_tab',
                    'description'               => 'Demo (Tab)',
                    'note'                      => 'One attribute of every type (in Tab-View)',
                    'parent_ci_type_id'         => 0,
                    'order_number'              => '11',
                    'default_project_id'        => null,
                    'default_attribute_id'      => null,
                    'default_sort_attribute_id' => null,
                    'is_default_sort_asc'       => '1',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '1',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(
                    $this->store['attribute']['general_unique_input']                               => array('is_mandatory' => 1),
                    $this->store['attribute']['no_hint_icon']                                       => array('is_mandatory' => 0),
                    $this->store['attribute']['general_regular_input']                              => array('is_mandatory' => 0),
                    $this->store['attribute']['general_numeric_input']                              => array('is_mandatory' => 0),
                    $this->store['attribute']['general_textarea']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_textedit']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_static']                            => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_static_ampel']                      => array('is_mandatory' => 0),
                    $this->store['attribute']['general_checkbox']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_radio']                                      => array('is_mandatory' => 0),
                    $this->store['attribute']['general_date']                                       => array('is_mandatory' => 0),
                    $this->store['attribute']['general_datetime']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_currency']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_password']                                   => array('is_mandatory' => 0),
                    $this->store['attribute']['general_hyperlink']                                  => array('is_mandatory' => 0),
                    $this->store['attribute']['general_attachment']                                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_regular_executable']                         => array('is_mandatory' => 0),
                    $this->store['attribute']['general_event_executable']                           => array('is_mandatory' => 0),
                    $this->store['attribute']['general_query']                                      => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_citype']                            => array('is_mandatory' => 0),
                    $this->store['attribute']['general_fixed_info']                                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_query_persistent']                           => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_citype_persistent']                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_autocomplete']           => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('is_mandatory' => 0),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('is_mandatory' => 0),
                ),

                'search_list_attributes' => array(
                    $this->store['attribute']['general_unique_input']                               => array('order_number' => 1),
                    $this->store['attribute']['no_hint_icon']                                       => array('order_number' => 2),
                    $this->store['attribute']['general_regular_input']                              => array('order_number' => 3),
                    $this->store['attribute']['general_numeric_input']                              => array('order_number' => 4),
                    $this->store['attribute']['general_textarea']                                   => array('order_number' => 5),
                    $this->store['attribute']['general_textedit']                                   => array('order_number' => 6),
                    $this->store['attribute']['general_dropdown_static']                            => array('order_number' => 7),
                    $this->store['attribute']['general_dropdown_static_ampel']                      => array('order_number' => 8),
                    $this->store['attribute']['general_checkbox']                                   => array('order_number' => 9),
                    $this->store['attribute']['general_radio']                                      => array('order_number' => 10),
                    $this->store['attribute']['general_date']                                       => array('order_number' => 11),
                    $this->store['attribute']['general_datetime']                                   => array('order_number' => 12),
                    $this->store['attribute']['general_currency']                                   => array('order_number' => 13),
                    $this->store['attribute']['general_password']                                   => array('order_number' => 14),
                    $this->store['attribute']['general_hyperlink']                                  => array('order_number' => 15),
                    $this->store['attribute']['general_attachment']                                 => array('order_number' => 16),
                    $this->store['attribute']['general_regular_executable']                         => array('order_number' => 17),
                    $this->store['attribute']['general_event_executable']                           => array('order_number' => 18),
                    $this->store['attribute']['general_query']                                      => array('order_number' => 19),
                    $this->store['attribute']['general_dropdown_citype']                            => array('order_number' => 20),
                    $this->store['attribute']['general_fixed_info']                                 => array('order_number' => 21),
                    $this->store['attribute']['general_query_persistent']                           => array('order_number' => 22),
                    $this->store['attribute']['general_dropdown_citype_persistent']                 => array('order_number' => 23),
                    $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('order_number' => 24),
                    $this->store['attribute']['general_dropdown_sql_filled_autocomplete']           => array('order_number' => 25),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('order_number' => 26),
                    $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('order_number' => 27),
                ),
            ),

            array(
                'row' => array(
                    'name'                      => 'site',
                    'description'               => 'Sites',
                    'note'                      => '',
                    'parent_ci_type_id'         => 0,
                    'order_number'              => '19',
                    'default_project_id'        => $this->store['project']['springfield'],
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('is_mandatory' => 1),
                    $this->store['attribute']['site_country']        => array('is_mandatory' => 1),
                    $this->store['attribute']['site_address']        => array('is_mandatory' => 0),
                    $this->store['attribute']['site_location_code']  => array('is_mandatory' => 0),
                    $this->store['attribute']['site_headcount']      => array('is_mandatory' => 0),
                ),
                'search_list_attributes' => array(
                    $this->store['attribute']['site_name']          => array('order_number' => 1),
                    $this->store['attribute']['site_country']       => array('order_number' => 2),
                    $this->store['attribute']['site_address']       => array('order_number' => 3),
                    $this->store['attribute']['site_location_code'] => array('order_number' => 4),
                    $this->store['attribute']['site_headcount']     => array('order_number' => 5),
                ),
            ),

            array(
                'row' => array(
                    'name'                      => 'emp',
                    'description'               => 'Employee',
                    'note'                      => '',
                    'parent_ci_type_id'         => 0,
                    'order_number'              => '20',
                    'default_project_id'        => $this->store['project']['springfield'],
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(
                    $this->store['attribute']['emp_staff_number']           => array('is_mandatory' => 1),
                    $this->store['attribute']['emp_firstname']              => array('is_mandatory' => 1),
                    $this->store['attribute']['emp_lastname']               => array('is_mandatory' => 1),
                    $this->store['attribute']['emp_email_address']          => array('is_mandatory' => 1),
                    $this->store['attribute']['emp_ad_username']            => array('is_mandatory' => 0),
                    $this->store['attribute']['emp_client_os_family']       => array('is_mandatory' => 0),
                    $this->store['attribute']['emp_client_win_version']     => array('is_mandatory' => 0),
                    $this->store['attribute']['emp_client_linux_distro']    => array('is_mandatory' => 0),
                ),
                'search_list_attributes' => array(
                    $this->store['attribute']['emp_staff_number']   => array('order_number' => 1),
                    $this->store['attribute']['emp_firstname']      => array('order_number' => 2),
                    $this->store['attribute']['emp_lastname']       => array('order_number' => 3),
                    $this->store['attribute']['emp_email_address']  => array('order_number' => 4),
                ),
            ),

            //CITYPE FOR SQL ATTRIBUTE
            array(
                'row' => array(
                    'name'                      => 'sql_attribute_citype',
                    'description'               => 'sql_attribute_citype',
                    'note'                      => 'checking the sql filled attributes',
                    'parent_ci_type_id'         => 0,
                    'order_number'              => '21',
                    'default_project_id'        => null,
                    'default_attribute_id'      => null,
                    'default_sort_attribute_id' => null,
                    'is_default_sort_asc'       => '1',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),
                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
        );


        foreach($ciTypes as $entry) {
            $this->createCiType($entry);
        }

        $ciTypes = array(
            array(
                'row' => array(
                    'name'                      => 'emp_austria',
                    'description'               => 'Austria',
                    'note'                      => 'Employees in Austria',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp'],
                    'order_number'              => '10',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '0',
                    'is_attribute_attach'       => '0',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_germany',
                    'description'               => 'Germany',
                    'note'                      => 'Employees in Germany',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp'],
                    'order_number'              => '20',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '0',
                    'is_attribute_attach'       => '0',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_switzerland',
                    'description'               => 'Switzerland',
                    'note'                      => 'Employees in Switzerland',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp'],
                    'order_number'              => '30',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '0',
                    'is_attribute_attach'       => '0',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_rest_of_world',
                    'description'               => 'Rest of World',
                    'note'                      => 'Employees all over the world',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp'],
                    'order_number'              => '99',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '0',
                    'is_attribute_attach'       => '0',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
        );

        foreach($ciTypes as $entry) {
            $this->createCiType($entry);
        }


        $ciTypes = array(
            array(
                'row' => array(
                    'name'                      => 'emp_austria_vienna',
                    'description'               => 'Vienna',
                    'note'                      => 'Employees in Vienna',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp_austria'],
                    'order_number'              => '10',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_austria_graz',
                    'description'               => 'Graz',
                    'note'                      => 'Employees in Graz',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp_austria'],
                    'order_number'              => '15',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_germany_berlin',
                    'description'               => 'Berlin',
                    'note'                      => 'Employees in Berlin',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp_germany'],
                    'order_number'              => '20',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_germany_frankfurt',
                    'description'               => 'Frankfurt',
                    'note'                      => 'Employees in Frankfurt',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp_germany'],
                    'order_number'              => '23',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_germany_munich',
                    'description'               => 'Munich',
                    'note'                      => 'Employees in Munich',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp_germany'],
                    'order_number'              => '25',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
            array(
                'row' => array(
                    'name'                      => 'emp_switzerland_zurich',
                    'description'               => 'Zurich',
                    'note'                      => 'Employees in Zurich',
                    'parent_ci_type_id'         => $this->store['ci_type']['emp_switzerland'],
                    'order_number'              => '30',
                    'default_project_id'        => '',
                    'default_attribute_id'      => '',
                    'default_sort_attribute_id' => '',
                    'is_default_sort_asc'       => '',
                    'is_ci_attach'              => '1',
                    'is_attribute_attach'       => '1',
                    'is_tab_enabled'            => '0',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'attributes' => array(),
                'search_list_attributes' => array(),
            ),
        );

        foreach($ciTypes as $entry) {
            $this->createCiType($entry);
        }

    }

    protected function seedAttributeDetails() {
        print "Seed Attribute Details\n";

        $attributeName = 'general_dropdown_static';
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Option 1',
            'order_number'  => '10',
        ), 'value');
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Option 2',
            'order_number'  => '20',
        ), 'value');
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Option 3',
            'order_number'  => '30',
        ), 'value');


        $attributeName = 'general_checkbox';
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Check 1',
            'order_number'  => '10',
        ), 'value');
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Check 2',
            'order_number'  => '20',
        ), 'value');
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Check 3',
            'order_number'  => '30',
        ), 'value');


        $attributeName = 'general_radio';
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Radio 1',
            'order_number'  => '10',
        ), 'value');
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Radio 2',
            'order_number'  => '20',
        ), 'value');
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Radio 3',
            'order_number'  => '30',
        ), 'value');


        $attributeName = 'general_query';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => 'SELECT concat("Unique Input is: ", value_text) FROM ci_attribute where attribute_id = ' . $this->store['attribute']['general_unique_input'] . ' AND ci_id = :id:',
        ));


        $attributeName = 'general_dropdown_citype';
        $foreignId = $this->insertRow('attribute_default_citype', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'ci_type_id'    => $this->store['ci_type']['demo'],
        ), 'attribute_id');
        $this->insertRow('attribute_default_citype_attributes', array(
            'attribute_default_citype_id'  => $foreignId,
            'attribute_id'                  => $this->store['attribute']['general_unique_input'],
            'order_number'                  => 1
        ));
        $this->insertRow('attribute_default_citype_attributes', array(
            'attribute_default_citype_id'  => $foreignId,
            'attribute_id'                  => $this->store['attribute']['general_regular_input'],
            'order_number'                  => 2
        ));


        $attributeName = 'general_fixed_info';
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => '<div>This is part of the fixed info.<br />It shows up at every CI and can <i>not</i> be <b>edited</b>.</div>',
            'order_number'  => '0',
        ), 'value');


        $attributeName = 'general_query_persistent';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => 'SELECT concat("Last updated at: ", now()) FROM dual',
        ));


        $attributeName = 'general_dropdown_citype_persistent';
        $foreignId = $this->insertRow('attribute_default_citype', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'ci_type_id'    => $this->store['ci_type']['demo'],
        ), 'attribute_id');
        $this->insertRow('attribute_default_citype_attributes', array(
            'attribute_default_citype_id'  => $foreignId,
            'attribute_id'                  => $this->store['attribute']['general_unique_input'],
            'order_number'                  => 1
        ));
        $this->insertRow('attribute_default_citype_attributes', array(
            'attribute_default_citype_id'  => $foreignId,
            'attribute_id'                  => $this->store['attribute']['general_regular_input'],
            'order_number'                  => 2
        ));


        $attributeName = 'general_dropdown_static_ampel';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => '
SELECT
CASE adv.value
  WHEN \'Option 1\' THEN \'00FF00\'
  WHEN \'Option 2\' THEN \'00FF00\'
  WHEN \'Option 3\' THEN \'00FF00\'
  ELSE \'FF0000\'
END AS color,
adv.value AS status
FROM ci_attribute cia
JOIN attribute_default_values adv 
    ON adv.attribute_id = cia.attribute_id AND adv.id = cia.value_default
WHERE 
      cia.attribute_id = (SELECT id FROM `attribute` WHERE name = \'general_dropdown_static\') 
  and ci_id = :id: 
GROUP BY cia.ci_id
            ',
        ));


        $attributeName = 'general_dropdown_sql_filled_select';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => '
              SELECT 1 AS id, "Option 1" AS value FROM dual UNION
              SELECT 2 AS id, "Option 2" AS value FROM dual UNION
              SELECT 3 AS id, "Option 3" AS value FROM dual UNION
              SELECT 4 AS id, "Option 4" AS value FROM dual UNION
              SELECT 5 AS id, "Option 5" AS value FROM dual UNION
              SELECT 6 AS id, "Option 6" AS value FROM dual
            ',
        ));


        $attributeName = 'general_dropdown_sql_filled_autocomplete';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => '
              SELECT 1 AS id, "Option 1" AS value FROM dual UNION
              SELECT 2 AS id, "Option 2" AS value FROM dual UNION
              SELECT 3 AS id, "Option 3" AS value FROM dual UNION
              SELECT 4 AS id, "Option 4" AS value FROM dual UNION
              SELECT 5 AS id, "Option 5" AS value FROM dual UNION
              SELECT 6 AS id, "Option 6" AS value FROM dual
            ',
        ));


        $attributeName = 'general_dropdown_sql_filled_multiselect';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => '
              SELECT 1 AS id, "Option 1" AS value FROM dual UNION
              SELECT 2 AS id, "Option 2" AS value FROM dual UNION
              SELECT 3 AS id, "Option 3" AS value FROM dual UNION
              SELECT 4 AS id, "Option 4" AS value FROM dual UNION
              SELECT 5 AS id, "Option 5" AS value FROM dual UNION
              SELECT 6 AS id, "Option 6" AS value FROM dual
            ',
        ));


        $attributeName = 'general_dropdown_sql_filled_multiselect_counter';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => '
              SELECT 1 AS id, "Option 1" AS value FROM dual UNION
              SELECT 2 AS id, "Option 2" AS value FROM dual UNION
              SELECT 3 AS id, "Option 3" AS value FROM dual UNION
              SELECT 4 AS id, "Option 4" AS value FROM dual UNION
              SELECT 5 AS id, "Option 5" AS value FROM dual UNION
              SELECT 6 AS id, "Option 6" AS value FROM dual
            ',
        ));


        $attributeName = 'emp_client_os_family';
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Windows',
            'order_number'  => '10',
        ), 'value');
        $this->insertRow('attribute_default_values', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'value'         => 'Linux',
            'order_number'  => '20',
        ), 'value');


        $attributeName = 'site_headcount';
        $this->insertRow('attribute_default_queries', array(
            'attribute_id'  => $this->store['attribute'][$attributeName],
            'query'         => '
                select 
                    count(*) counter
                 from ci_attribute
                 where attribute_id = (select id from attribute where name = \'emp_location_code\')
                 and value_text = (
                    select 
                        value_text 
                    from ci_attribute 
                    where attribute_id = (select id from attribute where name = \'site_location_code\')
                    and ci_id = :id:
                )
            ',
        ));


    }

    protected function seedCiRelationTypes() {
        print "Seed CI Relation Types\n";

        $ciRelationTypes = array(
            array(
                'row' => array(
                    'name'                      => 'emp__site',
                    'description'               => 'Employees',
                    'description_optional'      => 'Site',
                    'note'                      => 'Employees - Site',
                    'visualize'                 => '1',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'ci_types' => array(
                    $this->store['ci_type']['site']                     => array('order_number' => '0', 'max_amount' => null),
                    $this->store['ci_type']['emp_austria_graz']         => array('order_number' => '0', 'max_amount' => null),
                    $this->store['ci_type']['emp_austria_vienna']       => array('order_number' => '0', 'max_amount' => null),
                    $this->store['ci_type']['emp_germany_berlin']       => array('order_number' => '0', 'max_amount' => null),
                    $this->store['ci_type']['emp_germany_frankfurt']    => array('order_number' => '0', 'max_amount' => null),
                    $this->store['ci_type']['emp_germany_munich']       => array('order_number' => '0', 'max_amount' => null),
                    $this->store['ci_type']['emp_switzerland_zurich']   => array('order_number' => '0', 'max_amount' => null),
                ),
            ),

            array(
                'row' => array(
                    'name'                      => 'demo__demo',
                    'description'               => 'Demo',
                    'description_optional'      => '',
                    'note'                      => 'Demo - Demo',
                    'visualize'                 => '1',
                    'is_active'                 => '1',
                    'user_id'                   => 1,
                ),

                'ci_types' => array(
                    $this->store['ci_type']['demo'] => array('order_number' => '0', 'max_amount' => null),
                ),
            ),
        );

        foreach($ciRelationTypes as $entry) {
            $id = $this->insertRow('ci_relation_type', $entry['row']);

            foreach($entry['ci_types'] as $ciTypeId => $ciType) {
                 $this->insertRow('ci_type_relation_type', array(
                     'ci_relation_type_id'  => $id,
                     'ci_type_id'           => $ciTypeId,
                     'order_number'         => $ciType['order_number'],
                     'max_amount'           => $ciType['max_amount'],
                 ));
            }

        }

    }

    protected function seedCis() {
        print "Seed CI's\n";

        $demos = $this->seedDemoCis();
        $sites = $this->seedSiteCis();
        $emps  = $this->seedEmployeeCis();
        $demoTabs = $this->seedDemoTabCis();

        foreach($emps as $empCiTypeId => $locationEmps) {
            foreach($locationEmps as $empCiId) {
                $this->insertRow('ci_relation', array(
                    'ci_relation_type_id'   => $this->store['ci_relation_type']['emp__site'],
                    'ci_id_1'               => $this->generalFaker->randomElement($sites[ $this->store['ci_type']['site'] ]),
                    'ci_id_2'               => $empCiId,
                ));
            }
        }
    }

    protected function seedSiteCis() {
        $returnedCis    = array();
        $projectId      = $this->store['project']['springfield'];
        $ciTypeId       = $this->store['ci_type']['site'];

        $rows = array(
            array(
                'ci_type_id' => $ciTypeId,
                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('value_text' => 'Graz'),
                    $this->store['attribute']['site_country']        => array('value_text' => 'Austria'),
                    $this->store['attribute']['site_address']        => array('value_text' => 'Rundweg 234, 1111 Pseudostadt'),
                    $this->store['attribute']['site_location_code']  => array('value_text' => 'GRZ'),
                ),
                'projects' => array(
                    $projectId,
                ),
            ),
            array(
                'ci_type_id' => $ciTypeId,
                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('value_text' => 'Vienna'),
                    $this->store['attribute']['site_country']        => array('value_text' => 'Austria'),
                    $this->store['attribute']['site_address']        => array('value_text' => 'Handlauf 2, 2222 Run'),
                    $this->store['attribute']['site_location_code']  => array('value_text' => 'VIE'),
                ),
                'projects' => array(
                    $projectId,
                ),
            ),
            array(
                'ci_type_id' => $ciTypeId,
                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('value_text' => 'Berlin'),
                    $this->store['attribute']['site_country']        => array('value_text' => 'Germany'),
                    $this->store['attribute']['site_address']        => array('value_text' => 'Konferenzstraße 999, 33111 Mariastadt'),
                    $this->store['attribute']['site_location_code']  => array('value_text' => 'BER'),
                ),
                'projects' => array(
                    $projectId,
                ),
            ),
            array(
                'ci_type_id' => $ciTypeId,
                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('value_text' => 'Frankfurt'),
                    $this->store['attribute']['site_country']        => array('value_text' => 'Germany'),
                    $this->store['attribute']['site_address']        => array('value_text' => 'Samsegasse 43, 4567 Dessenrand'),
                    $this->store['attribute']['site_location_code']  => array('value_text' => 'FRA'),
                ),
                'projects' => array(
                    $projectId,
                ),
            ),
            array(
                'ci_type_id' => $ciTypeId,
                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('value_text' => 'Munich'),
                    $this->store['attribute']['site_country']        => array('value_text' => 'Germany'),
                    $this->store['attribute']['site_address']        => array('value_text' => 'Wiesen 99, Damich'),
                    $this->store['attribute']['site_location_code']  => array('value_text' => 'MUC'),
                ),
                'projects' => array(
                    $projectId,
                ),
            ),
            array(
                'ci_type_id' => $ciTypeId,
                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('value_text' => 'Zurich'),
                    $this->store['attribute']['site_country']        => array('value_text' => 'Switzerland'),
                    $this->store['attribute']['site_address']        => array('value_text' => 'Ötliweg 67, Wasenruft'),
                    $this->store['attribute']['site_location_code']  => array('value_text' => 'ZRH'),
                ),
                'projects' => array(
                    $projectId,
                ),
            ),
        );


        for ($i=0; $i < 30; $i++) {
            $faker = $this->generalFaker->randomElement($this->fakers);
            $row = array(
                'ci_type_id' => $ciTypeId,
                'attributes' => array(
                    $this->store['attribute']['site_name']           => array('value_text' => $faker->city()),
                    $this->store['attribute']['site_country']        => array('value_text' => $faker->country()),
                    $this->store['attribute']['site_address']        => array('value_text' => $faker->streetAddress()),
                    $this->store['attribute']['site_location_code']  => array('value_text' => $this->randomLocationCode()),
                ),
                'projects' => array(
                    $projectId,
                ),
            );

            $rows[] = $row;
        }


        foreach($rows as $entry) {
            $ci = $this->createCi($entry);

            $siteLocationAttributeId = $this->store['attribute']['site_location_code'];
            $this->store['location_codes'][] = $entry['attributes'][$siteLocationAttributeId]['value_text'];
            $returnedCis[ $entry['ci_type_id'] ][] = $ci;
        }

        return $returnedCis;

    }

    protected function seedEmployeeCis() {
        $returnedCis    = array();
        $projectIds     = $this->store['project']['ids'];
        $ciTypeIds      = array(
            $this->store['ci_type']['emp_austria_graz'],
            $this->store['ci_type']['emp_austria_vienna'],
            $this->store['ci_type']['emp_germany_berlin'],
            $this->store['ci_type']['emp_germany_frankfurt'],
            $this->store['ci_type']['emp_germany_munich'],
            $this->store['ci_type']['emp_switzerland_zurich'],
        );
        $osFamilies = array(
            $this->store['attribute_default_values']['Windows'],
            $this->store['attribute_default_values']['Linux'],
        );
        $osWin = array(
            7,
            '8.1',
            8,
            10,
        );
        $osLinux = array(
            'Ubuntu',
            'Linux Mint',
            'ArchLinux',
            'Debian',
            'Manjaro',
        );

        $rows = array(
             array(
                'ci_type_id' => $this->store['ci_type']['emp_switzerland_zurich'],
                'attributes' => array(
                    $this->store['attribute']['emp_staff_number']           => array('value_text' => 1),
                    $this->store['attribute']['emp_firstname']              => array('value_text' => 'Joh'),
                    $this->store['attribute']['emp_lastname']               => array('value_text' => 'Doe'),
                    $this->store['attribute']['emp_email_address']          => array('value_text' => 'john.doe@example.com'),
                    $this->store['attribute']['emp_ad_username']            => array('value_text' => 'john.doe'),
                    $this->store['attribute']['emp_client_os_family']       => array('value_default' => $this->store['attribute_default_values']['Windows']),
                    $this->store['attribute']['emp_client_win_version']     => array('value_text' => '7'),
                    $this->store['attribute']['emp_location_code']          => array('value_text' => 'ZHR'),
                ),
                'projects' => array(
                    $this->store['project']['springfield'],
                ),
             ),

            array(
                'ci_type_id' => $this->store['ci_type']['emp_germany_berlin'],
                'attributes' => array(
                    $this->store['attribute']['emp_staff_number']           => array('value_text' => 5002),
                    $this->store['attribute']['emp_firstname']              => array('value_text' => 'Max'),
                    $this->store['attribute']['emp_lastname']               => array('value_text' => 'Mustermann'),
                    $this->store['attribute']['emp_email_address']          => array('value_text' => 'max.mustermann@example.com'),
                    $this->store['attribute']['emp_ad_username']            => array('value_text' => 'max.mustermann'),
                    $this->store['attribute']['emp_client_os_family']       => array('value_default' => $this->store['attribute_default_values']['Linux']),
                    $this->store['attribute']['emp_client_linux_distro']    => array('value_text' => 'Ubuntu'),
                    $this->store['attribute']['emp_location_code']          => array('value_text' => 'BER'),
                ),
                'projects' => array(
                    $this->store['project']['springfield'],
                ),
            ),
        );

        for ($i=0; $i < 400; $i++) {
            $row = array(
                'ci_type_id' => $this->generalFaker->randomElement($ciTypeIds),
                'attributes' => array(
                    $this->store['attribute']['emp_staff_number']           => array('value_text' => $this->generalFaker->unique()->randomNumber(5)),
                    $this->store['attribute']['emp_firstname']              => array('value_text' => $this->fakers['de']->firstName()),
                    $this->store['attribute']['emp_lastname']               => array('value_text' => $this->fakers['de']->lastName()),
                    $this->store['attribute']['emp_email_address']          => array('value_text' => $this->fakers['de']->email()),
                    $this->store['attribute']['emp_client_os_family']       => array('value_default' => $this->generalFaker->randomElement($osFamilies)),
                    $this->store['attribute']['emp_location_code']          => array('value_text' => $this->generalFaker->randomElement($this->store['location_codes'])),
                ),
                'projects' => $this->generalFaker->randomElements($projectIds, 2),
            );

            $row['attributes'][ $this->store['attribute']['emp_ad_username'] ]['value_text'] = strtolower(
                $row['attributes'][ $this->store['attribute']['emp_firstname'] ]['value_text'] .
                '.' .
                $row['attributes'][ $this->store['attribute']['emp_lastname'] ]['value_text']
            );

            $row['attributes'][ $this->store['attribute']['emp_email_address'] ]['value_text'] =  $row['attributes'][ $this->store['attribute']['emp_ad_username'] ]['value_text'] . '@example.com';

            if($row['attributes'][ $this->store['attribute']['emp_client_os_family'] ]  == $this->store['attribute_default_values']['Windows']) {
                $row['attributes'][ $this->store['attribute']['emp_client_win_version'] ]['value_text']   = $this->generalFaker->randomElement($osWin);
            } else {
                $row['attributes'][ $this->store['attribute']['emp_client_linux_distro'] ]['value_text']  = $this->generalFaker->randomElement($osLinux);
            }

            $rows[] = $row;
        }

        for ($i=0; $i < 400; $i++) {
            $faker = $this->generalFaker->randomElement($this->fakers);
            $row = array(
                'ci_type_id' => $this->store['ci_type']['emp_rest_of_world'],
                'attributes' => array(
                    $this->store['attribute']['emp_staff_number']           => array('value_text' => $this->generalFaker->unique()->randomNumber(5)),
                    $this->store['attribute']['emp_firstname']              => array('value_text' => $faker->firstName()),
                    $this->store['attribute']['emp_lastname']               => array('value_text' => $faker->lastName()),
                    $this->store['attribute']['emp_email_address']          => array('value_text' => $faker->safeEmail()),
                    $this->store['attribute']['emp_client_os_family']       => array('value_default' => $this->generalFaker->randomElement($osFamilies)),
                    $this->store['attribute']['emp_ad_username']            => array('value_text' => $faker->userName()),
                ),
                'projects' => $this->generalFaker->randomElements($projectIds, 2),
            );

            $rows[] = $row;
        }


        foreach($rows as $entry) {
            $ci = $this->createCi($entry);
            $returnedCis[ $entry['ci_type_id'] ][] = $ci;
        }

        return $returnedCis;
    }


    protected function seedDemoCis() {
        $returnedCis    = array();

        // demo_1
        $ci = $this->createCi(array(
            'ci_type_id' => $this->store['ci_type']['demo'],
            'attributes' => array(
                $this->store['attribute']['general_unique_input']       => array('value_text' => 'demo_1'),
                $this->store['attribute']['general_regular_input']      => array('value_text' => 'Regular Single Line Text Input'),
                $this->store['attribute']['general_numeric_input']      => array('value_text' => 42),
                $this->store['attribute']['general_textarea']           => array('value_text' => "Multiline Text Input\n\n--> MORE TEXT"),
                $this->store['attribute']['general_textedit']           => array('value_text' => 'Multiline Text Input<br /><br />WITH <i>STYLING</i> <b>OPTIONS</b>!'),
                $this->store['attribute']['general_dropdown_static']    => array('value_default' => $this->store['attribute_default_values']['Option 1']),
                $this->store['attribute']['general_checkbox']           => array('value_default' => $this->store['attribute_default_values']['Check 3']),
                $this->store['attribute']['general_radio']              => array('value_default' => $this->store['attribute_default_values']['Radio 2']),
                $this->store['attribute']['general_date']               => array('value_date' => '2019-02-18'),
                $this->store['attribute']['general_datetime']           => array('value_date' => '2013-11-01 12:24:10'),
                $this->store['attribute']['general_currency']           => array('value_text' => '29,99'),
                $this->store['attribute']['general_password']           => array('value_text' => 'secret demo password'),
                $this->store['attribute']['general_hyperlink']          => array('value_text' => 'http://bearingpoint.com'),
                $this->store['attribute']['general_regular_executable'] => array('value_text' => null),
                $this->store['attribute']['general_event_executable']   => array('value_text' => null),
                $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('value_ci' => 6),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('value_ci' => '3,5'),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('value_ci' => '1::4,4::2,5::1'),
            ),
            'projects' => array(
                $this->store['project']['springfield'],
            ),
        ));

        $returnedCis[ $this->store['ci_type']['demo'] ][] = $ci;

        // demo_2
        $ci = $this->createCi(array(
            'ci_type_id' => $this->store['ci_type']['demo'],
            'attributes' => array(
                $this->store['attribute']['general_unique_input']       => array('value_text' => 'demo_2'),
                $this->store['attribute']['general_regular_input']      => array('value_text' => 'Bearingpoint Technology GmbH'),
                $this->store['attribute']['general_numeric_input']      => array('value_text' => 8141),
                $this->store['attribute']['general_textarea']           => array('value_text' => "This is a small demo case\nThis field represents a Multiline-Text"),
                $this->store['attribute']['general_textedit']           => array('value_text' => 'This is a small demo case<br>Discover how <b>awesome</b> this product is!!'),
                $this->store['attribute']['general_dropdown_static']    => array('value_default' => $this->store['attribute_default_values']['Option 2']),
                $this->store['attribute']['general_checkbox']           => array('value_default' => $this->store['attribute_default_values']['Check 1']),
                $this->store['attribute']['general_radio']              => array('value_default' => $this->store['attribute_default_values']['Radio 3']),
                $this->store['attribute']['general_date']               => array('value_date' => '2012-03-05'),
                $this->store['attribute']['general_datetime']           => array('value_date' => '2018-07-10 08:00:00'),
                $this->store['attribute']['general_currency']           => array('value_text' => '1000,10'),
                $this->store['attribute']['general_password']           => array('value_text' => 'secret demo2 password'),
                $this->store['attribute']['general_hyperlink']          => array('value_text' => 'http://ourjourney.bearingpoint.com/'),
                $this->store['attribute']['general_dropdown_citype']                            => array('value_ci' => $ci),
                $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('value_ci' => 1),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('value_ci' => '2,3'),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('value_ci' => '4::1,5::100,6::2'),
            ),
            'projects' => array(
                $this->store['project']['springfield'],
            ),
        ));

        $returnedCis[ $this->store['ci_type']['demo'] ][] = $ci;

        return $returnedCis;
    }
    protected function seedDemoTabCis() {
        $returnedCis    = array();

        // demo_tab_1
        $ci = $this->createCi(array(
            'ci_type_id' => $this->store['ci_type']['demo_tab'],
            'attributes' => array(
                $this->store['attribute']['general_unique_input']       => array('value_text' => 'demo_tab_1'),
                $this->store['attribute']['general_regular_input']      => array('value_text' => 'Regular Single Line Text Input'),
                $this->store['attribute']['general_numeric_input']      => array('value_text' => 4242),
                $this->store['attribute']['general_textarea']           => array('value_text' => "Multiline Text Input\n\n--> MORE TEXT"),
                $this->store['attribute']['general_textedit']           => array('value_text' => 'Multiline Text Input<br /><br />WITH <i>STYLING</i> <b>OPTIONS</b>!'),
                $this->store['attribute']['general_dropdown_static']    => array('value_default' => $this->store['attribute_default_values']['Option 1']),
                $this->store['attribute']['general_checkbox']           => array('value_default' => $this->store['attribute_default_values']['Check 3']),
                $this->store['attribute']['general_radio']              => array('value_default' => $this->store['attribute_default_values']['Radio 2']),
                $this->store['attribute']['general_date']               => array('value_date' => '2019-02-18'),
                $this->store['attribute']['general_datetime']           => array('value_date' => '2013-11-01 12:24:10'),
                $this->store['attribute']['general_currency']           => array('value_text' => '29,99'),
                $this->store['attribute']['general_password']           => array('value_text' => 'secret demo password'),
                $this->store['attribute']['general_hyperlink']          => array('value_text' => 'http://bearingpoint.com'),
                $this->store['attribute']['general_regular_executable'] => array('value_text' => null),
                $this->store['attribute']['general_event_executable']   => array('value_text' => null),
                $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('value_ci' => 6),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('value_ci' => '3,5'),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('value_ci' => '1::4,4::2,5::1'),
            ),
            'projects' => array(
                $this->store['project']['springfield'],
            ),
        ));

        $returnedCis[ $this->store['ci_type']['demo_tab'] ][] = $ci;

        // demo_tab_2
        $ci = $this->createCi(array(
            'ci_type_id' => $this->store['ci_type']['demo_tab'],
            'attributes' => array(
                $this->store['attribute']['general_unique_input']       => array('value_text' => 'demo_tab_1'),
                $this->store['attribute']['general_regular_input']      => array('value_text' => 'Bearingpoint Technology GmbH'),
                $this->store['attribute']['general_numeric_input']      => array('value_text' => 1418),
                $this->store['attribute']['general_textarea']           => array('value_text' => "This is a small demo case\nThis field represents a Multiline-Text"),
                $this->store['attribute']['general_textedit']           => array('value_text' => 'This is a small demo case<br>Discover how <b>awesome</b> this product is!!'),
                $this->store['attribute']['general_dropdown_static']    => array('value_default' => $this->store['attribute_default_values']['Option 2']),
                $this->store['attribute']['general_checkbox']           => array('value_default' => $this->store['attribute_default_values']['Check 1']),
                $this->store['attribute']['general_radio']              => array('value_default' => $this->store['attribute_default_values']['Radio 3']),
                $this->store['attribute']['general_date']               => array('value_date' => '2021-11-30'),
                $this->store['attribute']['general_datetime']           => array('value_date' => '2020-07-10 08:00:00'),
                $this->store['attribute']['general_currency']           => array('value_text' => '1000,10'),
                $this->store['attribute']['general_password']           => array('value_text' => 'secret demotab2 password'),
                $this->store['attribute']['general_hyperlink']          => array('value_text' => 'http://ourjourney.bearingpoint.com/'),
                $this->store['attribute']['general_dropdown_citype']                            => array('value_ci' => $ci),
                $this->store['attribute']['general_dropdown_sql_filled_select']                 => array('value_ci' => 1),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect']            => array('value_ci' => '2,3'),
                $this->store['attribute']['general_dropdown_sql_filled_multiselect_counter']    => array('value_ci' => '4::1,5::100,6::2'),
            ),
            'projects' => array(
                $this->store['project']['springfield'],
            ),
        ));

        $returnedCis[ $this->store['ci_type']['demo_tab'] ][] = $ci;

        return $returnedCis;
    }

    public function randomLocationCode()
    {
        $faker = $this->generalFaker;
        return strtoupper($faker->randomLetter() . $faker->randomLetter() . $faker->randomLetter());
    }

}
