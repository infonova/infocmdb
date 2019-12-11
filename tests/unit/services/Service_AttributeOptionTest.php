<?php

class Service_AttributeOptionTest extends \Codeception\Test\Unit
{
    /**
     * @var AcceptanceTester
     */
    protected $tester;
    protected $service;
    protected $logger           = null;
    protected $translator       = null;
    protected $languagePath       = null;
    protected $userLanguagePath       = null;

    /**
     * @var Dao_User
     */
    protected $userDao       = null;

    protected function _before()
    {
        $this->logger = Zend_Registry::get('Log');
        $this->translator       = Zend_Registry::get('Zend_Translate');
        $this->languagePath     = Zend_Registry::get('Language_Path');
        $this->userLanguagePath = APPLICATION_PUBLIC . '/translation/';
        $this->translator->setLocale('en');
        $this->userDao = new Dao_User();
    }

    protected function _after()
    {
    }

    // tests
    public function testGetAttributeToOrder()
    {

        $this->service = new Service_Attribute_Option(
            $this->translator, $this->logger,
            $this->userDao->getUserByUsername('admin')[Db_User::THEME_ID]
        );

        $attributeId = $this->tester->grabFromDatabase('attribute_group', 'id', array('name' => 'General'));

        $this->tester->wantTo("Want no error when inserting string as a ordernumber");

        $gotError = false;
        try {
            $values = array('optionName' => '', 'ordernumber' => '');

            $insertId = $this->service->insertNewOption($values, $attributeId);
            $this->tester->assertTrue(is_numeric($insertId));

        } catch (Exception_Attribute_InsertOptionFailed $e) {
            $gotError = true;
        }

        $this->tester->assertFalse($gotError);

        $this->tester->wantTo("Want no error");

        $gotError = false;
        try {
            $values = array('optionName' => 'test', 'ordernumber' => 1);
            $insertId = $this->service->insertNewOption($values, $attributeId);
            $this->tester->assertTrue(is_numeric($insertId));
        } catch (Exception_Attribute_InsertOptionFailed $e) {
            $gotError = true;
        }

        $this->tester->assertFalse($gotError);

        $this->tester->wantTo("Want no error for duplicate entry");

        $gotError = false;
        try {
            $values = array('optionName' => 'test', 'ordernumber' => 1);
            $insertId = $this->service->insertNewOption($values, $attributeId);
            $this->tester->assertTrue(is_numeric($insertId));
        } catch (Exception_Attribute_InsertOptionFailed $e) {
            $gotError = true;
        }

        $this->tester->assertFalse($gotError);
        $this->tester->wantTo("Want no error for duplicate entry");

        $gotError = false;
        try {
            $values = array('optionName' => 'test test', 'ordernumber' => -1);
            $insertId = $this->service->insertNewOption($values, $attributeId);
            $this->tester->assertTrue(is_numeric($insertId));
        } catch (Exception_Attribute_InsertOptionFailed $e) {
            $gotError = true;
        }

        $this->tester->assertFalse($gotError);
    }
}
