<?php

class AttributeControllerCest extends AbstractApiV2Test
{

    public function updateSuccess(ApiTester $I)
    {
        $attributeId = 10;
        $data        = array(
            'attribute' => array(
                'name' => 'general_date',
            ),
        );

        $I->wantTo("Precheck if current attribute row meets expectation");
        $I->seeInDatabase('attribute', $data['attribute']);

        $I->wantTo("Send update request for attribute name");
        $data['attribute']['name'] = 'attribute_update' . rand(100, 1000);
        $I->sendPUT('/apiV2/attribute/id/' . $attributeId, json_encode($data));

        $I->wantTo("Check result");
        $I->seeResponseContainsJson(array(
            'success' => true
        ));
        $I->seeInDatabase('attribute', $data['attribute']);
    }

    public function updateValidation(ApiTester $I)
    {
        $attributeId = 11;
        $data        = array(
            'attribute' => array(
                'name' => 'general_datetime',
            ),
        );

        $I->wantTo("Precheck if current attribute row meets expectation");
        $I->seeInDatabase('attribute', $data['attribute']);

        $I->wantTo("Send invalid update request for attribute");
        $data['attribute']['name']        = '';
        $data['attribute']['description'] = '';
        $I->sendPUT('/apiV2/attribute/id/' . $attributeId, json_encode($data));

        $I->wantTo("Check if result contains validation errors");
        $I->seeResponseContainsJson(array(
            'success' => false,
            'data'    => array(
                'name'        => array(
                    'isEmpty' => 'Value is required and can\'t be empty',
                ),
                'description' => array(
                    'isEmpty' => 'Value is required and can\'t be empty',
                ),
            )
        ));
        $I->dontSeeInDatabase('attribute', $data['attribute']);
    }

    public function updateInvalidAttribute(ApiTester $I)
    {
        $attributeId = 0;
        $data        = array(
            'attribute' => array(
                'name' => 'red_fox',
            ),
        );

        $I->sendPUT('/apiV2/attribute/id/' . $attributeId, json_encode($data));
        $I->seeResponseContainsJson(array(
            'success' => false,
            'message' => 'Not Found',
        ));
    }
}