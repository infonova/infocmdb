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
 * Test class for Zend_Dojo_View_Helper_SimpleTextarea.
 *
 * @category   Zend
 * @package    Zend_Dojo
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Dojo
 * @group      Zend_Dojo_View
 */
class Zend_Dojo_View_Helper_SimpleTextareaTest extends PHPUnit\Framework\TestCase
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
        $this->helper = new Zend_Dojo_View_Helper_SimpleTextarea();
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
        Zend_Dojo::enableView($view);
        return $view;
    }

    public function getElement()
    {
        return $this->helper->simpleTextarea(
            'elementId',
            'some content',
            array(),
            array()
        );
    }

    public function testShouldAllowDeclarativeDijitCreation()
    {
        $html = $this->getElement();
        $this->assertRegExp('/<textarea[^>]*(dojoType="dijit.form.SimpleTextarea")/', $html, $html);
    }

    public function testShouldAllowProgrammaticDijitCreation()
    {
        Zend_Dojo_View_Helper_Dojo::setUseProgrammatic();
        $html = $this->getElement();
        $this->assertNotRegExp('/<textarea[^>]*(dojoType="dijit.form.SimpleTextarea")/', $html);
        $this->assertNotNull($this->view->dojo()->getDijit('elementId'));
    }

    public function testPassingIdAsAttributeShouldOverrideUsingNameAsId()
    {
        $html = $this->helper->simpleTextarea('foo[bar]', '', array(), array('id' => 'foo-bar'));
        $this->assertContains('id="foo-bar"', $html);
    }
}