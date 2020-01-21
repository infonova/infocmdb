<?php

class CiTest extends \Codeception\Test\Unit
{
    /**
     * @var Service_Ci_Get
     */
    protected $ciServiceGet;

    protected function _before()
    {
        $this->ciServiceGet = new Service_Ci_Get(null, null, 0);
    }

    protected function _after()
    {
    }

    // tests

    /**
     * @covers Service_Ci_Get::getContextInfoForCi()
     */
    public function testGetContextInfoForCi()
    {
        $context = $this->ciServiceGet->getContextInfoForCi(2);

        $this->assertNotEmpty($context);

        $this->assertInstanceOf(stdClass::class, $context["relations"],
            "relations should be serialized to an json object");
        $this->assertEmpty(get_object_vars($context["relations"]),
            "context should contain no relations");

        $this->assertInstanceOf(stdClass::class, $context["projects"],
            "projects should be serialized to an json object");
        $this->assertNotEmpty(get_object_vars($context["projects"]),
            "context should contain projects");

        $this->assertInstanceOf(stdClass::class, $context["attributes"],
            "attributes should be serialized to an json object");
        $this->assertNotEmpty(get_object_vars($context["attributes"]),
            "context should contain attributes");
    }

}
