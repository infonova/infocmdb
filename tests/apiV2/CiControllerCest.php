<?php

class CiControllerCest extends AbstractApiV2Test
{
    private function createNewCi(ApiTester $I)
    {
        $now  = date('Y-m-d H:i:s');
        $ciId = $I->haveInDatabase('ci', array(
            'ci_type_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ));
        $I->haveInDatabase('ci_project', array(
            'ci_id'      => $ciId,
            'project_id' => 1,
        ));

        return $ciId;
    }

    public function getCi(ApiTester $I)
    {
        $I->wantTo('fetch ci successfully');
        $I->sendGET('/apiV2/ci?id=2');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));

        $I->seeResponseJsonMatchesJsonPath('$.data.data.ci.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.data.ciType.id');
        $I->seeResponseJsonMatchesJsonPath('$.data.data.projectList[0].id');
        $I->seeResponseJsonMatchesJsonPath('$.data.data.icon');
        $I->seeResponseJsonMatchesJsonPath('$.data.data.relations');
        $I->seeResponseJsonMatchesJsonPath('$.data.data.breadcrumbs[0].id');


        $I->wantTo('fetch ci non existent ci');
        $I->sendGET('/apiV2/ci?id=-1');
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Forbidden',
        ));

        $I->sendGET('/apiV2/ci?id=99999999999');
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Forbidden',
        ));

        $I->loggingIn('single_project_author','single_project_author', true);

        $I->wantTo('fetch ci without proper permission');
        $I->sendGET('/apiV2/ci?id=2');
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Forbidden',
            'data' => 'Not allowed to edit CI: 2',
        ));

        $I->loggingIn('admin','admin', true);


    }

    public function getCiIndex(ApiTester $I)
    {
        $I->wantTo('fetch the demo citype ci list for ciTypeId 1 (demo)');
        $I->sendGET('/apiV2/ci/index?ciTypeId=1');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->seeResponseJsonMatchesJsonPath('$.data.data.ciList[0]');

        $I->wantTo('fetch the demo citypeid 1 ci list from projectId 4 (Springfield)');
        $I->sendGET('/apiV2/ci/index?ciTypeId=1&ProjectId=4');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->seeResponseJsonMatchesJsonPath('$.data.data.ciList[0]');

        $I->wantTo('fetch the demo citype ci list from project Springfield');
        $I->sendGET('/apiV2/ci/index?ciTypeName=demo&ProjectName=Springfield');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->seeResponseJsonMatchesJsonPath('$.data.data.ciList[0]');

        $I->wantTo('fetch the demo citype ci list from projectId 4 (Springfield)');
        $I->sendGET('/apiV2/ci/index?ciTypeName=demo&ProjectId=4');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->seeResponseJsonMatchesJsonPath('$.data.data.ciList[0]');

        $I->wantTo('fetch the demo citype ci list from user projects / no project given');
        $I->sendGET('/apiV2/ci/index?ciTypeName=demo');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->seeResponseJsonMatchesJsonPath('$.data.data.ciList[0]');

        $I->wantTo('fetch the demo citype ci list with a projectName that has no cis');
        $I->sendGET('/apiV2/ci/index?ciTypeName=demo&ProjectName=Firestorm');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.data.ciList[0]');

        $I->wantTo('fetch the demo citype ci list with a projectID 2 (Firestorm) that has no cis');
        $I->sendGET('/apiV2/ci/index?ciTypeName=demo&ProjectId=2');
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'success',
        ));
        $I->dontSeeResponseJsonMatchesJsonPath('$.data.data.ciList[0]');

    }

    public function updateAttributeInvalidCi(ApiTester $I)
    {
        $data = array(
            'ci' => array(
                'atttributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_unique_input',
                        'value' => 'red fox',
                    ),
                ),
            ),
        );
        $ciId = '1234564';

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Forbidden',
        ));
    }

    public function updateAttributeNoCiPermission(ApiTester $I)
    {
        $now  = date('Y-m-d H:i:s');
        $ciId = $I->haveInDatabase('ci', array(
            'ci_type_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_unique_input',
                        'value' => 'red fox',
                    ),
                ),
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Forbidden',
        ));
    }

    public function updateAttributeInsert(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);
        $I->haveInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'insert',
                        'name'  => 'general_regular_input',
                        'value' => 'value 2',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->seeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $I->seeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 2',
        ));

    }

    public function updateAttributeUpdate(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);
        $I->haveInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_regular_input',
                        'value' => 'value 1 (updated)',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->seeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1 (updated)',
        ));

        $I->cantSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));
    }

    public function updateAttributeSetExisting(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);
        $I->haveInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_regular_input',
                        'value' => 'value 1 (updated)',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->seeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1 (updated)',
        ));

        $I->cantSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));
    }

    public function updateAttributeSetNew(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_regular_input',
                        'value' => 'value 1',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->seeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));
    }

    public function updateAttributeDelete(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);
        $I->haveInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'delete',
                        'name'  => 'general_regular_input',
                        'value' => 'value 12345',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));
    }

    public function updateAttributeDeleteEmpty(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);
        $I->haveInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_regular_input',
                        'value' => '',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));
    }

    public function updateAttributeInvalidMode(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'everything is fine',
                        'name'  => 'general_regular_input',
                        'value' => 'value 1',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'attributes: Invalid mode "everything is fine"" for attribute: general_regular_input',
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));
    }

    public function updateAttributeInvalidCiAttributeId(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'ciAttributeId' => 23454563434,
                        'mode'          => 'set',
                        'name'          => 'general_regular_input',
                        'value'         => 'value 1',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'attributes: Could not find row with ci_attribute-id: 23454563434',
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'id' => 23454563434,
        ));
    }

    public function updateAttributeValidationError(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_unique_input',
                        'value' => 'demo_2',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Validation failed',
            'data'    => array(
                'general_unique_input (0): Value must be unique!',
            ),
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 1,
            'value_text'   => 'demo_2',
        ));
    }

    public function updateAttributeNoPermission(ApiTester $I)
    {
        $I->loggingIn('reader', 'reader', true);

        $ciId = $this->createNewCi($I);
        $I->haveInDatabase('ci_project', array(
            'ci_id'      => $ciId,
            'project_id' => 3,
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_regular_input',
                        'value' => 'value 1',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Forbidden',
            'data'    => 'attributes: Not allowed to edit attribute: general_regular_input',
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));

        $I->loggingIn('admin', 'admin', true);
    }

    public function updateAttributeSetMultipleCiAttributes(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);
        $I->haveInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1',
        ));
        $I->haveInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 2',
        ));

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_regular_input',
                        'value' => 'value 1 (updated)',
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'attributes: Multiple ci_attribute rows for attribute: general_regular_input',
        ));

        $I->dontSeeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 3,
            'value_text'   => 'value 1 (updated)',
        ));
    }

    public function updateAttributeSetAttachment(ApiTester $I)
    {
        $ciId = $this->createNewCi($I);

        $I->wantTo('Upload a test file');
        $fileData = file_get_contents(__DIR__ . '/../_data/test_img.png');
        $I->sendPost('/apiV2/fileupload', $fileData);
        $I->seeResponseContainsJson(array(
           'success' => true,
        ));
        $response = json_decode($I->grabResponse());
        $uploadId = $response->data;

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'general_attachment',
                        'value' => 'detective.png',
                        'uploadId' => $uploadId,
                    ),
                )
            ),
        );

        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->seeInDatabase('ci_attribute', array(
            'ci_id'        => $ciId,
            'attribute_id' => 16,
            'note'   => 'detective.png',
        ));
    }

    public function updateAttributeWithRegex(ApiTester $I)
    {
        $ciId        = $this->createNewCi($I);
        $attributeId = $I->grabColumnFromDatabase("attribute", "id", array("name" => "site_identifier"))[0];
        $I->updateInDatabase('attribute',
            array('regex' => '/^sn.*?$/'),
            array('id' => $attributeId)
        );

        $data = array(
            'ci' => array(
                'attributes' => array(
                    array(
                        'mode'  => 'set',
                        'name'  => 'site_identifier',
                        'value' => 'sn1234',
                    ),
                ),
            ),
        );

        $I->wantTo("Update a ci with an attribute regex and valid input");
        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => true,
            'message' => 'CI saved successfully',
        ));

        $I->wantTo("Update a ci with an attribute regex and invalid input");

        $data['ci']['attributes'][0]['value'] = '123456';
        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Validation failed',
        ));


        $I->wantTo("Update a ci with an attribute regex and invalid input");
        $I->updateInDatabase('attribute',
            array('regex' => '^sn.*?$'),
            array('id' => $attributeId)
        );

        $data['ci']['attributes'][0]['value'] = 'sn123456';
        $I->sendPUT('/apiV2/ci/id/' . $ciId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Internal Server Error',
        ));
    }
}
