<?php


class MenuPermissionCest extends AbstractAcceptanceTest
{

    private $pages = array(
        // critical routes
        '/workflow/index',
        '/workflow/execute/workflowId/1',
        '/workflow/rebuild/id/1',
        '/workflow/instance/instanceId/1',
        '/workflow/delete/workflowId/7',
        '/reporting/index',
        '/reporting/execute/reportingId/1',
        '/download/fileimport/id/1',
        '/download/report/id/1',

        // CREATE / UPDATE / DELETE
        '/announcement/create',
        '/attribute/create',
        '/attributegroup/create',
        '/citype/create',
        '/mail/create',
        '/mailimport/create',
        '/project/create',
        '/query/create',
        '/relation/create',
        '/relationtype/create',
        '/reporting/create',
        '/role/create',
        '/theme/create',
        '/user/create',
        '/workflow/create',
        '/announcement/edit/announcementId/1',
        '/attribute/edit/attributeId/1',
        '/attributegroup/edit/attributeGroupId/1',
        '/citype/edit/citypeId/1',
        '/mail/edit/mailId/1',
        '/mailimport/edit/mailImportId/1',
        '/menu/edit/menuId/1',
        '/project/edit/projectId/1',
        '/query/edit/queryId/1',
        '/relationtype/edit/relationTypeId/1',
        '/reporting/edit/reportingId/1',
        '/role/edit/roleId/1',
        '/theme/edit/themeId/1',
        '/user/edit/userId/1',
        '/workflow/edit/workflowId/1',
        '/announcement/delete/announcementId/1',
        '/attribute/delete/attributeId/1',
        '/attributegroup/delete/attributeGroupId/1',
        '/citype/delete/citypeId/1',
        '/fileimport/delete/file_history_id/1',
        '/fileimport/deletefile/fileId/1',
        '/mail/delete/mailId/1',
        '/mailimport/delete/mailImportId/1',
        '/project/delete/projectId/1',
        '/query/delete/queryId/1',
        '/relation/delete/relationId/1',
        '/relationtype/delete/relationTypeId/1',
        '/reporting/delete/reportingId/1',
        '/role/delete/roleId/1',
        '/theme/delete/themeId/1',
        '/user/delete/userId/1',
        '/validation/delete/validationId/1',
        '/workflow/delete/workflowId/1',
    );

    public function _before(AcceptanceTester $I)
    {
        // override - do not login
    }

    public function checkReaderHasNoAccess(AcceptanceTester $I)
    {
        $I->loggingIn($I, 'reader', 'reader');

        foreach ($this->pages as $page) {
            $I->amOnPage($page);
            $I->see('Zugriff verweigert');
        }
    }

    public function checkAdminHasAccess(AcceptanceTester $I)
    {
        $I->loggingIn($I, 'admin', 'admin');

        // disable global exception tests (e.g. id not found, invalid parameter)
        $I->disableErrorChecks();

        foreach ($this->pages as $page) {
            $I->amOnPage($page);
            $I->dontSee('Zugriff verweigert');
        }

        // we deleted stuff which we may need in a following test
        \Helper\Phinx::resetTestEnvironment();

    }
}
