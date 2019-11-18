<?php

class Dao_Migration extends Dao_Abstract
{

    private $schema = "devcmdb";


    public function __construct($schema)
    {
        if ($schema)
            $this->schema = $schema;

        parent::__construct();
    }


    public function getThemes()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.theme');
    }

    public function insertTheme($theme)
    {
        $table = new Db_Theme();
        $table->insert($theme);
    }

    public function getThemeMenus()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.theme_menu');
    }

    public function insertThemeMenu($data)
    {
        $table = new Db_ThemeMenu();
        $table->insert($data);
    }

    public function migrateThemePrivileges()
    {
        $this->db->query('INSERT INTO theme_privilege
		SELECT * FROM ' . $this->schema . '.theme_privilege');
    }

    public function getUsers()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.user');
    }


    public function insertUser($user)
    {
        $table = new Db_User();
        $table->insert($user);
    }


    public function migrateTodoItems()
    {
        $this->db->query('INSERT INTO todo_items
		SELECT * FROM ' . $this->schema . '.todo_items');
    }


    public function getAttributeGroups()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.view_type');
    }


    public function insertAttributeGroup($data)
    {
        $table = new Db_AttributeGroup();
        $table->insert($data);
    }


    public function getTemplates()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.templates');
    }


    public function insertTempalte($data)
    {
        $table = new Db_Templates();
        $table->insert($data);
    }


    public function getRoles()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.role');
    }


    public function insertRole($data)
    {
        $table = new Db_Role();
        $table->insert($data);
    }


    public function getProjects()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.project');
    }


    public function insertProject($data)
    {
        $table = new Db_Project();
        $table->insert($data);
    }


    public function getAttributes()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.attribute where exists(select ag.id from attribute_group ag where ag.id = view_type_id)');
    }


    public function insertAttribute($data)
    {
        $table = new Db_Attribute();
        $table->insert($data);
    }


    public function getCiTypes()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci_type');
    }


    public function insertCiType($data)
    {
        $table = new Db_CiType();
        $table->insert($data);
    }


    public function getAttributeDefaultCiTypes()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.attribute_default_citype where exists(select a.id from attribute a where a.id = ' . $this->schema . '.attribute_default_citype.attribute_id ) and exists (select c.id from ci_type c where c.id = ' . $this->schema . '.attribute_default_citype.ci_type_id )');
    }


    public function insertAttributeDefaultCiType($data)
    {
        $table = new Db_AttributeDefaultCitype();
        $table->insert($data);
    }


    public function getAttributeDefaultCiTypeAttributess()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.attribute_default_citype_attributes  where exists(select a.id from attribute a where a.id = ' . $this->schema . '.attribute_default_citype_attributes.attribute_id ) and exists(select c.id from attribute_default_citype c where c.id = ' . $this->schema . '.attribute_default_citype_attributes.attribute_default_citype_id )');
    }


    public function insertAttributeDefaultCiTypeAttribute($data)
    {
        $table = new Db_AttributeDefaultCitypeAttributes();
        $table->insert($data);
    }


    public function getAttributeDefaultQueries()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.attribute_default_queries');
    }


    public function insertAttributeDefaultQuerie($data)
    {
        $table = new Db_AttributeDefaultQueries();
        $table->insert($data);
    }


    public function getAttributeDefaultQueriesParameters()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.attribute_default_queries_parameter');
    }


    public function insertAttributeDefaultQuerieParameter($data)
    {
        $table = new Db_AttributeDefaultQueriesParameter();
        $table->insert($data);
    }


    public function getAttributeDefaultValues()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.attribute_default_values where exists(select attribute.id from attribute where attribute.id = ' . $this->schema . '.attribute_default_values.attribute_id)');
    }


    public function insertAttributeDefaultValue($data)
    {
        $table = new Db_AttributeDefaultValues();
        $table->insert($data);
    }


    public function getAttributeRoles()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.attribute_role where exists(select attribute.id from attribute where attribute.id = ' . $this->schema . '.attribute_role.attribute_id) and exists(select role.id from role where role.id = ' . $this->schema . '.attribute_role.role_id)');
    }


    public function insertAttributeRole($data)
    {
        $table = new Db_AttributeRole();
        $table->insert($data);
    }


    public function getCiRelationTypes()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci_relation_type');
    }


    public function insertCiRelationType($data)
    {
        $table = new Db_CiRelationType();
        $table->insert($data);
    }


    public function getCiTypeAttributes()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci_type_attribute where exists(select attribute.id from attribute where attribute.id = ' . $this->schema . '.ci_type_attribute.attribute_id) and exists(select ci_type.id from ci_type where ci_type.id = ' . $this->schema . '.ci_type_attribute.ci_type_id)');
    }


    public function insertCiTypeAttribute($data)
    {
        $table = new Db_CiTypeAttribute();
        $table->insert($data);
    }


    public function migrateCron()
    {
        $this->db->query('INSERT INTO cron
		SELECT * FROM ' . $this->schema . '.cron');
    }


    public function getImportMail()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.import_mail');
    }


    public function insertImportMail($data)
    {
        $table = new Db_MailImport();
        $table->insert($data);
    }


    public function getMails()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.mail');
    }


    public function insertMail($data)
    {
        $table = new Db_Mail();
        $table->insert($data);
    }

    public function getNotifications()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.notification');
    }


    public function insertNotification($data)
    {
        $table = new Db_Notification();
        $table->insert($data);
    }


    public function getReporting()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.reporting');
    }


    public function insertReporting($data)
    {
        $table = new Db_Reporting();
        $table->insert($data);
    }

    public function getReportingHistory()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.reporting_history');
    }


    public function insertReportingHistory($data)
    {
        $table = new Db_ReportingHistory();
        $table->insert($data);
    }


    public function getReportingMapping()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.reporting_mapping');
    }


    public function insertReportingMapping($data)
    {
        $table = new Db_ReportingMapping();
        $table->insert($data);
    }


    public function getSearchList()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.search_list where exists(select ci_type.id from ci_type where ci_type.id = ' . $this->schema . '.search_list.ci_type_id)');
    }


    public function insertSearchList($data)
    {
        $table = new Db_SearchList();
        $table->insert($data);
    }


    public function getSearchListAttribute()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.search_list_attribute where exists (select sl.id from ' . $this->schema . '.search_list sl where sl.id = ' . $this->schema . '.search_list_attribute.search_list_id)');
    }


    public function insertSearchListAttribute($data)
    {
        $table = new Db_SearchListAttribute();
        $table->insert($data);
    }


    public function getStoredQuery()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.stored_query');
    }


    public function insertStoredQuery($data)
    {
        $table = new Db_StoredQuery();
        $table->insert($data);
    }


    public function getUserProject()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.user_project where exists(select user.id from user where user.id = ' . $this->schema . '.user_project.user_id) and exists(select project.id from project where project.id = ' . $this->schema . '.user_project.project_id)');
    }


    public function insertUserProject($data)
    {
        $table = new Db_UserProject();
        $table->insert($data);
    }

    public function getUserRole()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.user_role where exists(select user.id from user where user.id = ' . $this->schema . '.user_role.user_id) and exists(select role.id from role where role.id = ' . $this->schema . '.user_role.role_id)');
    }


    public function insertUserRole($data)
    {
        $table = new Db_UserRole();
        $table->insert($data);
    }


    public function getCiTyperelationTypes()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci_type_relation_type where exists(select ci_type.id from ci_type where ci_type.id = ' . $this->schema . '.ci_type_relation_type.ci_type_id) and exists(select ci_relation_type.id from ci_relation_type where ci_relation_type.id = ' . $this->schema . '.ci_type_relation_type.ci_relation_type_id)');
    }


    public function insertCiTyperelationType($data)
    {
        $table = new Db_CiTypeRelationType();
        $table->insert($data);
    }


    public function migrateHistory()
    {
        $this->db->query('INSERT INTO history (id, user_id, datestamp, note)
		SELECT id, user_id, datestamp, note FROM ' . $this->schema . '.history group by ' . $this->schema . '.history.id');
    }


    public function migrateActiveCi()
    {
        $this->db->query('INSERT INTO ci (id, ci_type_id, icon, history_id, valid_from)
		SELECT id, ci_type_id, icon, ci_history_id, valid_from FROM ' . $this->schema . '.ci where valid_from < NOW() and (valid_to IS NULL OR valid_to > NOW())');
    }

    public function migrateActiveCiAttribute()
    {
        $this->db->query('INSERT INTO ci_attribute (id, ci_id, attribute_id, value_text, value_date, value_default, value_ci, note, is_initial, history_id, valid_from)
		SELECT id, ci_id, attribute_id, value_text, value_date, value_default, value_ci, note, initial, ci_history_id, valid_from FROM ' . $this->schema . '.ci_attribute where valid_from < NOW() and (valid_to IS NULL OR valid_to > NOW()) and exists(select ci.id from ci where ci.id = ' . $this->schema . '.ci_attribute.ci_id) and exists (select attribute.id from attribute where attribute.id = ' . $this->schema . '.ci_attribute.attribute_id)');
    }

    public function migrateActiveCiProject()
    {
        $this->db->query('INSERT INTO ci_project (id, ci_id, project_id, history_id, valid_from)
		SELECT id, ci_id, project_id, ci_history_id, valid_from FROM ' . $this->schema . '.ci_project where valid_from < NOW() and (valid_to IS NULL OR valid_to > NOW()) and exists(select ci.id from ci where ci.id = ' . $this->schema . '.ci_project.ci_id) and exists (select project.id from project where project.id = ' . $this->schema . '.ci_project.project_id)');
    }


    public function migrateActiveCiRelations()
    {
        $this->db->query('INSERT INTO ci_relation (id, ci_relation_type_id, ci_id_1, ci_id_2, attribute_id_1, attribute_id_2, direction, weighting, color, note, history_id, valid_from)
		SELECT id, ci_relation_type_id, ci_id_1, ci_id_2, attribute_id_1, attribute_id_2, direction, weighting, color, note, ci_history_id_1, valid_from FROM ' . $this->schema . '.ci_relation where valid_from < NOW() and (valid_to IS NULL OR valid_to > NOW()) and exists(select ci.id from ci where ci.id = ' . $this->schema . '.ci_relation.ci_id_1) and exists(select ci.id from ci where ci.id = ' . $this->schema . '.ci_relation.ci_id_2) and exists(select ci_relation_type.id from ci_relation_type where ci_relation_type.id = ' . $this->schema . '.ci_relation.ci_relation_type_id)');
    }


    public function getHistoryCi()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci where valid_to is not null and valid_to <= NOW() and ci_history_id IS NOT NULL and ci_history_id_delete IS NOT NULL');
    }


    public function insertHistoryCi($data)
    {
        $table = new Db_History_Ci();
        $table->insert($data);
    }


    public function getHistoryCiProject()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci_project where valid_to is not null and valid_to <= NOW() and ci_history_id IS NOT NULL and ci_history_id_delete IS NOT NULL and exists(select ci.id from ci where ci.id = ' . $this->schema . '.ci_project.ci_id) and exists (select project.id from project where project.id = ' . $this->schema . '.ci_project.project_id)');
    }


    public function insertHistoryCiProject($data)
    {
        $table = new Db_History_CiProject();
        $table->insert($data);
    }


    public function getHistoryCiRelation()
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci_relation where valid_to is not null and valid_to <= NOW() and ci_history_id_1 IS NOT NULL and ci_history_id_1_delete IS NOT NULL and exists(select ci.id from ci where ci.id = ' . $this->schema . '.ci_relation.ci_id_1) and exists (select ci.id from ci where ci.id = ' . $this->schema . '.ci_relation.ci_id_2) and exists(select ci_relation_type.id from ci_relation_type where ci_relation_type.id = ' . $this->schema . '.ci_relation.ci_relation_type_id)');
    }


    public function insertHistoryCiRelation($data)
    {
        $table = new Db_History_CiRelation();
        $table->insert($data);
    }


    public function getCiAttributes()
    {
        return $this->db->fetchAll('Select id, ci_id, unique_id from ' . $this->schema . '.v_ci_attribute where exists(select ci.id from ci where ci.id = ' . $this->schema . '.v_ci_attribute.ci_id) and exists (select attribute.id from attribute where attribute.id = ' . $this->schema . '.v_ci_attribute.attribute_id) limit 1');
    }

    public function deleteCiAttr($id)
    {
        return $this->db->query('DELETE from ' . $this->schema . '.ci_attribute where id = "' . $id . '"');
    }

    public function getHistoryCiAttribute($ciId, $uniqueId)
    {
        return $this->db->fetchAll('Select * from ' . $this->schema . '.ci_attribute where valid_to is not null and valid_to <= NOW() and ci_history_id IS NOT NULL and ci_history_id_delete IS NOT NULL and ci_id = "' . $ciId . '" and unique_id = "' . $uniqueId . '"');
    }


    public function getDeletedCiAttributeUniques()
    {
        return $this->db->fetchAll('Select id, ci_id, unique_id from ' . $this->schema . '.ci_attribute where valid_to is not null and valid_to <= NOW() and ci_history_id IS NOT NULL and ci_history_id_delete IS NOT NULL and exists(select ci.id from ci where ci.id = ' . $this->schema . '.ci_attribute.ci_id) and exists (select attribute.id from attribute where attribute.id = ' . $this->schema . '.ci_attribute.attribute_id) and not exists(select ' . $this->schema . '.v_ci_attribute.unique_id from ' . $this->schema . '.v_ci_attribute where ' . $this->schema . '.v_ci_attribute.unique_id = ' . $this->schema . '.ci_attribute.unique_id) group by unique_id limit 1');
    }

    public function deleteDeletedCiAttributeUniques($uniqueId)
    {
        return $this->db->query('DELETE from ' . $this->schema . '.ci_attribute where unique_id = "' . $uniqueId . '"');
    }


    public function insertHistoryCiAttribute($data)
    {
        $table = new Db_History_CiAttribute();
        $table->insert($data);
    }


    // WORKFLOW HANDLING


    public function getCustomizationList()
    {
        $select = $this->db->select()
            ->from($this->schema . '.customization');

        return $this->db->fetchAll($select);
    }

    public function createWorkflow($workflow)
    {
        $table = new Db_Workflow();
        return $table->insert($workflow);
    }


    public function createPlace($workflowId, $type, $name, $description)
    {
        $data                                = array();
        $data[Db_WorkflowPlace::WORKFLOW_ID] = $workflowId;
        $data[Db_WorkflowPlace::TYPE]        = $type;
        $data[Db_WorkflowPlace::NAME]        = $name;
        $data[Db_WorkflowPlace::DESCRIPTION] = $description;

        $table = new Db_WorkflowPlace();
        return $table->insert($data);
    }


    public function createTask($task)
    {
        $table = new Db_WorkflowTask();
        return $table->insert($task);
    }


    public function createTransition($transition)
    {
        $table = new Db_WorkflowTransition();
        return $table->insert($transition);
    }


    public function createArc($workflowId, $direction, $transitionId, $startId)
    {
        $data                                         = array();
        $data[Db_WorkflowArc::WORKFLOW_ID]            = $workflowId;
        $data[Db_WorkflowArc::WORKFLOW_PLACE_ID]      = $startId;
        $data[Db_WorkflowArc::WORKFLOW_TRANSITION_ID] = $transitionId;
        $data[Db_WorkflowArc::DIRECTION]              = $direction;
        $data[Db_WorkflowArc::TYPE]                   = 'SEQ';

        $table = new Db_WorkflowArc();
        return $table->insert($data);
    }


    public function getCustomizationTriggerById($customizationId)
    {
        $select = $this->db->select()
            ->from($this->schema . '.customization_mapping')
            ->where('customization_id =?', $customizationId);

        return $this->db->fetchAll($select);
    }


    public function insertWorkflowTrigger($tArray)
    {
        $table = new Db_WorkflowTrigger();
        return $table->insert($tArray);
    }


    public function dropCustomizationMapping()
    {
        $this->db->query('DROP table customization_mapping');
    }

    public function dropCustomization()
    {
        $this->db->query('DROP table customization');
    }


    public function getRelationsToRepair()
    {
        return $this->db->fetchAll('Select * from ci_relation where history_id = "0" limit 1');
    }


    public function createHistory()
    {
        $stmt = $this->db->query('select create_history(0, "create relation") as historyId');
        $res  = $stmt->fetch();
        return $res['historyId'];
    }


    public function updateRel($hid, $relId)
    {
        $this->db->query('update ci_relation set history_id = "' . $hid . '" WHERE id = "' . $relId . '"');
    }

}