<?php
/**
 * Zend Framework
 *
 * LICENSE
 *
 * This source file is subject to the new BSD license that is bundled
 * with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://framework.zend.com/license/new-bsd
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@zend.com so we can send you a copy immediately.
 *
 * @category   Zend
 * @package    Zend_Dojo
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Test class for Zend_Dojo_View_Helper_NumberSpinner.
 *
 * @category   Zend
 * @package    Zend_Dojo
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Dojo
 * @group      Zend_Dojo_View
 */
class Zend_Dojo_View_Helper_NumberSpinnerTest extends PHPUnit\Framework\TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        Zend_Registry::_unsetInstance();
        Zend_Dojo_View_Helper_Dojo::setUseDeclarative();

        $this->view   = $this->getView();
        $this->helper = new Zend_Dojo_View_Helper_NumberSpinner();
        $this->helper->setView($this->view);
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
    }

    public function getView()
    {
        $view = new Zend_View();
        $view->addHelperPath('Zend/Dojo/View/Helper/', 'Zend_Dojo_View_Helper');
        return $view;
    }

    public function getElement()
    {
        return $this->helper->numberSpinner(
            'elementId',
            '5',
            array(
                'smallDelta' => '10',
                'min'        => 9,
                'max'        => 1550,
                'places'     => 0,
                'required'   => true,
            ),
            array()
        );
    }

    public function testShouldAllowDeclarativeDijitCreation()
    {
        $html = $this->getElement();
        $this->assertRegExp('/<input[^>]*(dojoType="dijit.form.NumberSpinner")/', $html, $html);
    }

    public function testShouldAllowProgrammaticDijitCreation()
    {
        Zend_Dojo_View_Helper_Dojo::setUseProgrammatic();
        $html = $this->getElement();
        $this->assertNotRegExp('/<input[^>]*(dojoType="dijit.form.NumberSpinner")/', $html);
        $this->assertNotNull($this->view->dojo()->getDijit('elementId'));
    }

    public function testShouldCreateTextInput()
    {
        $html = $this->getElement();
        $this->assertRegExp('/<input[^>]*(type="text")/', $html);
    }

    public function testShouldJsonEncodeConstraints()
    {
        $html = $this->getElement();
        if (!preg_match('/constraints="(.*?)(" )/', $html, $m)) {
            $this->fail('Did not serialize constraints');
        }
        $constraints = str_replace("'", '"', $m[1]);
        if (Zend_Dojo_View_Helper_Dojo::useDeclarative()) {
            // Convert &#39; to "'" for json_decode. See Zend_Dojo_View_Helper_Dijit::_prepareDijit() (line 254)
            $constraints = str_replace('&#39;', '"', $constraints);
        }
        $constraints = Zend_Json::decode($constraints);
        $this->assertInternalType('array', $constraints, var_export($m[1], 1));
        $this->assertArrayHasKey('min', $constraints);
        $this->assertArrayHasKey('max', $constraints);
        $this->assertArrayHasKey('places', $constraints);
    }

    public function testInvalidConstraintsShouldBeStrippedPriorToRendering()
    {
        $html = $this->helper->numberSpinner(
            'foo',
            5,
            array(
                'constraints' => 'bogus',
            )
        );
        $this->assertNotContains('constraints="', $html);
    }
}