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
 * @package    Zend_Cache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * @category   Zend
 * @package    Zend_Cache
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Cache
 */
class Zend_Cache_LibmemcachedBackendTest extends Zend_Cache_CommonExtendedBackendTestCase
{
    protected $_instance;
    protected $_skipped;

    public function __construct($name = null, array $data = array(), $dataName = '')
    {
        parent::__construct('Zend_Cache_Backend_Libmemcached', $data, $dataName);
    }

    public function setUp($notag = true)
    {
        $this->_skipped = false;

        if (!defined('TESTS_ZEND_CACHE_LIBMEMCACHED_ENABLED') ||
            constant('TESTS_ZEND_CACHE_LIBMEMCACHED_ENABLED') === false) {
            $this->_skipped = true;
            $this->markTestSkipped('Tests are not enabled in TestConfiguration.php');
            return;
        } elseif (!extension_loaded('memcached')) {
            $this->_skipped = true;
            $this->markTestSkipped("Extension 'Memcached' is not loaded");
            return;
        } else {
            if (!defined('TESTS_ZEND_CACHE_LIBMEMCACHED_HOST')) {
                define('TESTS_ZEND_CACHE_LIBMEMCACHED_HOST', '127.0.0.1');
            }
            if (!defined('TESTS_ZEND_CACHE_LIBMEMCACHED_PORT')) {
                define('TESTS_ZEND_CACHE_LIBMEMCACHED_PORT', 11211);
            }
            if (!defined('TESTS_ZEND_CACHE_LIBMEMCACHED_WEIGHT')) {
                define('TESTS_ZEND_CACHE_LIBMEMCACHED_WEIGHT', 1);
            }
        }

        if (!class_exists('Memcached')) {
            $this->_skipped = true;
            $this->markTestSkipped('Memcached is not installed, skipping test');
            return;
        }

        $serverValid = array(
            'host'   => TESTS_ZEND_CACHE_LIBMEMCACHED_HOST,
            'port'   => TESTS_ZEND_CACHE_LIBMEMCACHED_PORT,
            'weight' => TESTS_ZEND_CACHE_LIBMEMCACHED_WEIGHT
        );
        $options = array(
            'servers' => array($serverValid),
            'client'  => array(
                'no_block'                 => false, // set Memcached client option by name
                Memcached::OPT_TCP_NODELAY => false, // set Memcached client option by value
            ),
        );
        $this->_instance = new Zend_Cache_Backend_Libmemcached($options);
        parent::setUp($notag);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->_instance = null;
        if ($this->_skipped === false) {
            // We have to wait after a memcached flush
            sleep(1);
        }
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testConstructorCorrectCall()
    {
        $test = new Zend_Cache_Backend_Libmemcached();
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCleanModeOld()
    {
        $this->_instance->setDirectives(array('logging' => false));
        $this->_instance->clean('old');
        // do nothing, just to see if an error occured
        $this->_instance->setDirectives(array('logging' => true));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCleanModeMatchingTags()
    {
        $this->_instance->setDirectives(array('logging' => false));
        $this->_instance->clean('matchingTag', array('tag1'));
        // do nothing, just to see if an error occured
        $this->_instance->setDirectives(array('logging' => true));
    }

    /**
     * @doesNotPerformAssertions
     */
    public function testCleanModeNotMatchingTags()
    {
        $this->_instance->setDirectives(array('logging' => false));
        $this->_instance->clean('notMatchingTag', array('tag1'));
        // do nothing, just to see if an error occured
        $this->_instance->setDirectives(array('logging' => true));
    }

    public function testGetWithCompression()
    {
        $this->_instance->setOption('compression', true);
        $this->testGetWithAnExistingCacheIdAndUTFCharacters();
    }

    public function testConstructorWithAnAlternativeSyntax()
    {
        $server = array(
            'host'   => TESTS_ZEND_CACHE_LIBMEMCACHED_HOST,
            'port'   => TESTS_ZEND_CACHE_LIBMEMCACHED_PORT,
            'weight' => TESTS_ZEND_CACHE_LIBMEMCACHED_WEIGHT
        );
        $options = array(
            'servers' => $server
        );
        $this->_instance = new Zend_Cache_Backend_Libmemcached($options);
        $this->testGetWithAnExistingCacheIdAndUTFCharacters();
    }

    // Because of limitations of this backend...
    public function testGetWithAnExpiredCacheId()
    {
        $this->markTestSkipped('Not supported by this backend');
    }
    public function testCleanModeMatchingTags2()
    {
        $this->markTestSkipped('Not supported by this backend');
    }
    public function testCleanModeNotMatchingTags2()
    {
        $this->markTestSkipped('Not supported by this backend');
    }
    public function testCleanModeNotMatchingTags3()
    {
        $this->markTestSkipped('Not supported by this backend');
    }

    public function testSaveCorrectCall()
    {
        $this->_instance->setDirectives(array('logging' => false));
        parent::testSaveCorrectCall();
        $this->_instance->setDirectives(array('logging' => true));
    }

    public function testSaveWithNullLifeTime()
    {
        $this->_instance->setDirectives(array('logging' => false));
        parent::testSaveWithNullLifeTime();
        $this->_instance->setDirectives(array('logging' => true));
    }

    public function testSaveWithSpecificLifeTime()
    {
        $this->_instance->setDirectives(array('logging' => false));
        parent::testSaveWithSpecificLifeTime();
        $this->_instance->setDirectives(array('logging' => true));
    }

    public function testGetMetadatas($notag = false)
    {
        parent::testGetMetadatas(true);
    }

    public function testGetFillingPercentage()
    {
        $this->_instance->setDirectives(array('logging' => false));
        parent::testGetFillingPercentage();
    }

    public function testGetFillingPercentageOnEmptyBackend()
    {
        $this->_instance->setDirectives(array('logging' => false));
        parent::testGetFillingPercentageOnEmptyBackend();
    }
}
