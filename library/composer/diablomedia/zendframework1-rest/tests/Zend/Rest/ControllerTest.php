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
 * @package    Zend_Rest
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @category   Zend
 * @package    Zend_Rest
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 */
class Zend_Rest_TestController extends Zend_Rest_Controller
{
    public $testValue = '';
    public function __construct(
        Zend_Controller_Request_Abstract $request,
        Zend_Controller_Response_Abstract $response,
        array $invokeArgs = array()
    ) {
        $this->testValue = '';
    }
    public function indexAction()
    {
        $this->testValue = 'indexAction';
    }
    public function getAction()
    {
        $this->testValue = 'getAction';
    }
    public function headAction()
    {
        $this->testValue = 'headAction';
    }
    public function postAction()
    {
        $this->testValue = 'postAction';
    }
    public function putAction()
    {
        $this->testValue = 'putAction';
    }
    public function deleteAction()
    {
        $this->testValue = 'deleteAction';
    }
}
/**
 * @category   Zend
 * @package    Zend_Rest
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Rest
 */
class Zend_Rest_ControllerTest extends PHPUnit\Framework\TestCase
{
    protected $_testController;

    public function setUp()
    {
        $request               = new Zend_Controller_Request_HttpTestCase();
        $response              = new Zend_Controller_Response_HttpTestCase();
        $this->_testController = new Zend_Rest_TestController($request, $response);
    }

    public function test_action_methods()
    {
        $this->_testController->indexAction();
        $this->assertEquals('indexAction', $this->_testController->testValue);
        $this->_testController->getAction();
        $this->assertEquals('getAction', $this->_testController->testValue);
        $this->_testController->headAction();
        $this->assertEquals('headAction', $this->_testController->testValue);
        $this->_testController->postAction();
        $this->assertEquals('postAction', $this->_testController->testValue);
        $this->_testController->putAction();
        $this->assertEquals('putAction', $this->_testController->testValue);
        $this->_testController->deleteAction();
        $this->assertEquals('deleteAction', $this->_testController->testValue);
    }
}
