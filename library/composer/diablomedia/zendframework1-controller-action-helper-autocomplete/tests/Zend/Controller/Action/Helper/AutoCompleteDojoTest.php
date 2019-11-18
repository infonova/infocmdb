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
 * @package    Zend_Controller
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Test class for Zend_Controller_Action_Helper_AutoCompleteDojo.
 *
 * @category   Zend
 * @package    Zend_Controller
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Controller
 * @group      Zend_Controller_Action
 * @group      Zend_Controller_Action_Helper
 */
class Zend_Controller_Action_Helper_AutoCompleteDojoTest extends PHPUnit\Framework\TestCase
{
    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        Zend_Controller_Action_Helper_AutoCompleteDojoTest_LayoutOverride::resetMvcInstance();
        Zend_Controller_Action_HelperBroker::resetHelpers();
        Zend_Controller_Action_HelperBroker::setPluginLoader(null);

        $this->request  = new Zend_Controller_Request_Http();
        $this->response = new Zend_Controller_Response_Cli();
        $this->front    = Zend_Controller_Front::getInstance();
        $this->front->resetInstance();
        $this->front->setRequest($this->request)->setResponse($this->response);

        $this->viewRenderer = Zend_Controller_Action_HelperBroker::getStaticHelper('viewRenderer');
        $this->layout       = Zend_Layout::startMvc();
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

    public function testConcreteImplementationsDeriveFromAutoCompleteBaseClass()
    {
        $dojo = new Zend_Controller_Action_Helper_AutoCompleteDojo();
        $this->assertTrue($dojo instanceof Zend_Controller_Action_Helper_AutoComplete_Abstract);

        $scriptaculous = new Zend_Controller_Action_Helper_AutoCompleteScriptaculous();
        $this->assertTrue($scriptaculous instanceof Zend_Controller_Action_Helper_AutoComplete_Abstract);
    }

    public function testEncodeJsonProxiesToJsonActionHelper()
    {
        $dojo    = new Zend_Controller_Action_Helper_AutoCompleteDojo();
        $data    = array('foo', 'bar', 'baz');
        $encoded = $dojo->prepareAutoCompletion($data);
        $decoded = Zend_Json::decode($encoded);
        $test    = array();
        foreach ($decoded['items'] as $item) {
            $test[] = $item['name'];
        }
        $this->assertSame($data, $test);
        $this->assertFalse($this->layout->isEnabled());
        $headers = $this->response->getHeaders();
        $found   = false;
        foreach ($headers as $header) {
            if ('Content-Type' == $header['name']) {
                if ('application/json' == $header['value']) {
                    $found = true;
                }
                break;
            }
        }
        $this->assertTrue($found, 'JSON content-type header not found');
    }

    public function testDojoHelperEncodesToJson()
    {
        $dojo    = new Zend_Controller_Action_Helper_AutoCompleteDojo();
        $data    = array('foo', 'bar', 'baz');
        $encoded = $dojo->direct($data, false);
        $decoded = Zend_Json::decode($encoded);
        $this->assertContains('items', array_keys($decoded));
        $this->assertContains('identifier', array_keys($decoded));
        $this->assertEquals('name', $decoded['identifier']);

        $test = array();
        foreach ($decoded['items'] as $item) {
            $test[] = $item['label'];
        }
        $this->assertEquals($data, $test);
    }

    public function testDojoHelperSendsResponseByDefault()
    {
        $dojo               = new Zend_Controller_Action_Helper_AutoCompleteDojo();
        $dojo->suppressExit = true;
        $data               = array('foo', 'bar', 'baz');
        $encoded            = $dojo->direct($data);
        $decoded            = Zend_Json::decode($encoded);
        $test               = array();
        foreach ($decoded['items'] as $item) {
            $test[] = $item['name'];
        }
        $this->assertSame($data, $test);
        $body = $this->response->getBody();
        $this->assertSame($encoded, $body);
    }

    public function testDojoHelperDisablesLayoutsAndViewRendererByDefault()
    {
        $dojo               = new Zend_Controller_Action_Helper_AutoCompleteDojo();
        $dojo->suppressExit = true;
        $data               = array('foo', 'bar', 'baz');
        $encoded            = $dojo->direct($data);
        $this->assertFalse($this->layout->isEnabled());
        $this->assertTrue($this->viewRenderer->getNoRender());
    }

    public function testDojoHelperCanEnableLayoutsAndViewRenderer()
    {
        $dojo               = new Zend_Controller_Action_Helper_AutoCompleteDojo();
        $dojo->suppressExit = true;
        $data               = array('foo', 'bar', 'baz');
        $encoded            = $dojo->direct($data, false, true);
        $this->assertTrue($this->layout->isEnabled());
        $this->assertFalse($this->viewRenderer->getNoRender());
    }
    /**
     * @group   ZF-9126
     */
    public function testDojoHelperEncodesUnicodeChars()
    {
        $dojo               = new Zend_Controller_Action_Helper_AutoCompleteDojo();
        $dojo->suppressExit = true;
        $data               = array('garçon', 'schließen', 'Helgi Þormar Þorbjörnsson');
        $encoded            = $dojo->direct($data);
        $body               = $this->response->getBody();
        $decoded            = Zend_Json::decode($encoded);
        $test               = array();
        foreach ($decoded['items'] as $item) {
            $test[] = $item['name'];
        }
        $this->assertSame($data, $test);
        $this->assertSame($encoded, $body);
    }
}

class Zend_Controller_Action_Helper_AutoCompleteDojoTest_LayoutOverride extends Zend_Layout
{
    public static function resetMvcInstance()
    {
        self::$_mvcInstance = null;
    }
}
