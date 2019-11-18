<?php

class Dao_AttributeTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;
    protected $dao;

    protected function _before()
    {
        $this->dao = new Dao_Attribute();
    }

    protected function _after()
    {
    }

    // tests
    public function testGetAttributeToOrder()
    {
        $attributeGroupId = $this->tester->grabFromDatabase('attribute_group', 'id', array('name' => 'General'));

        $result = $this->dao->getAttributeToOrder($attributeGroupId);

        $this->assertNotEmpty($result);

        $this->assertArrayHasKey(0, $result);
        $row = $result[0];

        $this->assertArrayHasKey('id', $row);
        $this->assertArrayHasKey('name', $row);
        $this->assertArrayHasKey('description', $row);
        $this->assertArrayHasKey('note', $row);
        $this->assertArrayHasKey('attribute_type_id', $row);
        $this->assertArrayHasKey('attribute_group_id', $row);
        $this->assertArrayHasKey('order_number', $row);
        $this->assertArrayHasKey('is_active', $row);
    }
}