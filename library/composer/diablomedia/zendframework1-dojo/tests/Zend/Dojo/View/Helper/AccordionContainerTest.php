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
 * Test class for Zend_Dojo_View_Helper_AccordionContainer.
 *
 * @category   Zend
 * @package    Zend_Dojo
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Dojo
 * @group      Zend_Dojo_View
 */
class Zend_Dojo_View_Helper_AccordionContainerTest extends PHPUnit\Framework\TestCase
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
        $this->helper = new Zend_Dojo_View_Helper_AccordionContainer();
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

    public function getContainer()
    {
        $html = '';
        for ($i = 1; $i < 6; ++$i) {
            $id      = 'pane' . $i;
            $title   = 'Pane ' . $i;
            $content = 'This is the content of pane ' . $i;
            $html .= $this->view->accordionPane($id, $content, array('title' => $title));
        }
        return $this->helper->accordionContainer('container', $html, array(), array('style' => 'height: 200px; width: 100px;'));
    }

    public function testShouldAllowDeclarativeDijitCreation()
    {
        $html = $this->getContainer();
        $this->assertRegExp('/<div[^>]*(dojoType="dijit.layout.AccordionContainer")/', $html, $html);
    }

    public function testShouldAllowProgrammaticDijitCreation()
    {
        Zend_Dojo_View_Helper_Dojo::setUseProgrammatic();
        $html = $this->getContainer();
        $this->assertNotRegExp('/<div[^>]*(dojoType="dijit.layout.AccordionContainer")/', $html);
        $this->assertNotNull($this->view->dojo()->getDijit('container'));
    }

    public function testShouldAllowCapturingNestedContent()
    {
        $this->helper->captureStart('foo', array(), array('style' => 'height: 200px; width: 100px;'));
        $this->view->accordionPane()->captureStart('bar', array('title' => 'Captured Pane'));
        echo "Captured content started\n";
        $this->view->accordionPane()->captureStart('baz', array('title' => 'Nested Pane'));
        echo 'Nested Content';
        echo $this->view->accordionPane()->captureEnd('baz');
        echo "Captured content ended\n";
        echo $this->view->accordionPane()->captureEnd('bar');
        $html = $this->helper->captureEnd('foo');
        $this->assertRegExp('/<div[^>]*(id="bar")/', $html);
        $this->assertRegExp('/<div[^>]*(id="baz")/', $html);
        $this->assertRegExp('/<div[^>]*(id="foo")/', $html);
        $this->assertEquals(2, substr_count($html, 'dijit.layout.AccordionPane'));
        $this->assertEquals(1, substr_count($html, 'dijit.layout.AccordionContainer'));
        $this->assertContains('started', $html);
        $this->assertContains('ended', $html);
        $this->assertContains('Nested Content', $html);
    }

    public function testCapturingShouldRaiseErrorWhenDuplicateIdDiscovered()
    {
        $this->expectException(\Zend_Dojo_View_Exception::class);
        $this->expectExceptionMessage('Lock already exists for id "bar"');

        try {
            $this->helper->captureStart('foo', array(), array('style' => 'height: 200px; width: 100px;'));
            $this->view->accordionPane()->captureStart('bar', array('title' => 'Captured Pane'));
            $this->view->accordionPane()->captureStart('bar', array('title' => 'Captured Pane'));
        } catch (Exception $e) {
            // Closing the output buffering to stop the
            // "Test code or tested code did not (only) close its own output buffers" risky error message
            $this->view->accordionPane()->captureEnd('bar');
            $html = $this->helper->captureEnd('foo');
            throw $e;
        }
    }

    public function testCapturingShouldRaiseErrorWhenNonexistentIdPassedToEnd()
    {
        $this->expectException(\Zend_Dojo_View_Exception::class);
        $this->expectExceptionMessage('No capture lock exists for id "bar"; nothing to capture');

        try {
            $this->helper->captureStart('foo', array(), array('style' => 'height: 200px; width: 100px;'));
            $html = $this->helper->captureEnd('bar');
        } catch (Exception $e) {
            // Closing the output buffering to stop the
            // "Test code or tested code did not (only) close its own output buffers" risky error message
            $this->helper->captureEnd('foo');
            throw $e;
        }
    }
}
