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
 * @package    Zend_Loader
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @version    $Id$
 */


/**
 * Test class for Zend_Loader_PluginLoader.
 *
 * @category   Zend
 * @package    Zend_Loader
 * @subpackage UnitTests
 * @copyright  Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license    http://framework.zend.com/license/new-bsd     New BSD License
 * @group      Zend_Loader
 */
class Zend_Loader_PluginLoaderTest extends PHPUnit\Framework\TestCase
{
    protected $_includeCache;

    /**
     * Sets up the fixture, for example, open a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    public function setUp()
    {
        if (file_exists($this->_includeCache)) {
            unlink($this->_includeCache);
        }
        Zend_Loader_PluginLoader::setIncludeFileCache(null);
        $this->_includeCache = dirname(__FILE__) . '/_files/includeCache.inc.php';
        $this->libPath       = realpath(dirname(__FILE__) . '/../../../src');
        $this->testLibPath   = realpath(dirname(__FILE__) . '/../../../tests');
        $this->key           = null;
    }

    /**
     * Tears down the fixture, for example, close a network connection.
     * This method is called after a test is executed.
     *
     * @return void
     */
    public function tearDown()
    {
        $this->clearStaticPaths();
        Zend_Loader_PluginLoader::setIncludeFileCache(null);
        if (file_exists($this->_includeCache)) {
            unlink($this->_includeCache);
        }
    }

    public function clearStaticPaths()
    {
        if (null !== $this->key) {
            $loader = new Zend_Loader_PluginLoader(array(), $this->key);
            $loader->clearPaths();
        }
    }

    public function testAddPrefixPathNonStatically()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths();
        $this->assertCount(2, $paths);
        $this->assertArrayHasKey('Zend_View_', $paths);
        $this->assertArrayHasKey('Zend_Loader_', $paths);
        $this->assertCount(1, $paths['Zend_View_']);
        $this->assertCount(2, $paths['Zend_Loader_']);
    }

    public function testAddPrefixPathMultipleTimes()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader');
        $paths = $loader->getPaths();

        $this->assertInternalType('array', $paths);
        $this->assertCount(1, $paths['Zend_Loader_']);
    }

    public function testAddPrefixPathStatically()
    {
        $this->key = 'foobar';
        $loader    = new Zend_Loader_PluginLoader(array(), $this->key);
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths();
        $this->assertCount(2, $paths);
        $this->assertArrayHasKey('Zend_View_', $paths);
        $this->assertArrayHasKey('Zend_Loader_', $paths);
        $this->assertCount(1, $paths['Zend_View_']);
        $this->assertCount(2, $paths['Zend_Loader_']);
    }

    public function testAddPrefixPathThrowsExceptionWithNonStringPrefix()
    {
        $loader = new Zend_Loader_PluginLoader();

        $this->expectException(\Zend_Loader_PluginLoader_Exception::class);

        $loader->addPrefixPath(array(), $this->libPath);
    }

    public function testAddPrefixPathThrowsExceptionWithNonStringPath()
    {
        $loader = new Zend_Loader_PluginLoader();

        $this->expectException(\Zend_Loader_PluginLoader_Exception::class);

        $loader->addPrefixPath('Foo_Bar', array());
    }

    public function testRemoveAllPathsForGivenPrefixNonStatically()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths('Zend_Loader');
        $this->assertCount(2, $paths);
        $loader->removePrefixPath('Zend_Loader');
        $this->assertFalse($loader->getPaths('Zend_Loader'));
    }

    public function testRemoveAllPathsForGivenPrefixStatically()
    {
        $this->key = 'foobar';
        $loader    = new Zend_Loader_PluginLoader(array(), $this->key);
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths('Zend_Loader');
        $this->assertCount(2, $paths);
        $loader->removePrefixPath('Zend_Loader');
        $this->assertFalse($loader->getPaths('Zend_Loader'));
    }

    public function testRemovePrefixPathThrowsExceptionIfPrefixNotRegistered()
    {
        $loader = new Zend_Loader_PluginLoader();

        $this->expectException(\Zend_Loader_PluginLoader_Exception::class);

        $loader->removePrefixPath('Foo_Bar');
    }

    public function testRemovePrefixPathThrowsExceptionIfPrefixPathPairNotRegistered()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Foo_Bar', realpath(dirname(__FILE__)));
        $paths = $loader->getPaths();
        $this->assertTrue(isset($paths['Foo_Bar_']));
        try {
            $loader->removePrefixPath('Foo_Bar', $this->libPath);
            $this->fail('Removing non-existent prefix/path pair should throw an exception');
        } catch (Exception $e) {
        }
    }

    public function testClearPathsNonStaticallyClearsPathArray()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths();
        $this->assertCount(2, $paths);
        $loader->clearPaths();
        $paths = $loader->getPaths();
        $this->assertCount(0, $paths);
    }

    public function testClearPathsStaticallyClearsPathArray()
    {
        $this->key = 'foobar';
        $loader    = new Zend_Loader_PluginLoader(array(), $this->key);
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths();
        $this->assertCount(2, $paths);
        $loader->clearPaths();
        $paths = $loader->getPaths();
        $this->assertCount(0, $paths);
    }

    public function testClearPathsWithPrefixNonStaticallyClearsPathArray()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths();
        $this->assertCount(2, $paths);
        $loader->clearPaths('Zend_Loader');
        $paths = $loader->getPaths();
        $this->assertCount(1, $paths);
    }

    public function testClearPathsWithPrefixStaticallyClearsPathArray()
    {
        $this->key = 'foobar';
        $loader    = new Zend_Loader_PluginLoader(array(), $this->key);
        $loader->addPrefixPath('Zend_View', $this->libPath . '/Zend/View')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend/Loader')
               ->addPrefixPath('Zend_Loader', $this->libPath . '/Zend');
        $paths = $loader->getPaths();
        $this->assertCount(2, $paths);
        $loader->clearPaths('Zend_Loader');
        $paths = $loader->getPaths();
        $this->assertCount(1, $paths);
    }

    public function testGetClassNameNonStaticallyReturnsFalseWhenClassNotLoaded()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_View_Helper', $this->libPath . '/Zend/View/Helper');
        $this->assertFalse($loader->getClassName('FormElement'));
    }

    public function testGetClassNameStaticallyReturnsFalseWhenClassNotLoaded()
    {
        $this->key = 'foobar';
        $loader    = new Zend_Loader_PluginLoader(array(), $this->key);
        $loader->addPrefixPath('Zend_View_Helper', $this->libPath . '/Zend/View/Helper');
        $this->assertFalse($loader->getClassName('FormElement'));
    }

    public function testLoadPluginNonStaticallyLoadsClass()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_View_Helper', $this->testLibPath . '/Zend/View/Helper');
        try {
            $className = $loader->load('FormButtonHelper');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertEquals('Zend_View_Helper_FormButtonHelper', $className);
        $this->assertTrue(class_exists('Zend_View_Helper_FormButtonHelper', false));
        $this->assertTrue($loader->isLoaded('FormButtonHelper'));
    }

    public function testLoadPluginStaticallyLoadsClass()
    {
        $this->key = 'foobar';
        $loader    = new Zend_Loader_PluginLoader(array(), $this->key);
        $loader->addPrefixPath('Zend_View_Helper', $this->testLibPath . '/Zend/View/Helper');
        try {
            $className = $loader->load('FormRadioHelper');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertEquals('Zend_View_Helper_FormRadioHelper', $className);
        $this->assertTrue(class_exists('Zend_View_Helper_FormRadioHelper', false));
        $this->assertTrue($loader->isLoaded('FormRadioHelper'));
    }

    public function testLoadThrowsExceptionIfFileFoundInPrefixButClassNotLoaded()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Foo_Helper', $this->libPath . '/Zend/View/Helper');

        $this->expectException(\Zend_Loader_PluginLoader_Exception::class);

        $className = $loader->load('Doctype');
    }

    public function testLoadThrowsExceptionIfNoHelperClassLoaded()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Foo_Helper', $this->libPath . '/Zend/View/Helper');

        $this->expectException(\Zend_Loader_PluginLoader_Exception::class);

        $className = $loader->load('FooBarBazBat');
    }

    public function testGetClassAfterNonStaticLoadReturnsResolvedClassName()
    {
        $loader = new Zend_Loader_PluginLoader();
        $loader->addPrefixPath('Zend_View_Helper', $this->testLibPath . '/Zend/View/Helper');
        try {
            $className = $loader->load('FormSelectHelper');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertEquals($className, $loader->getClassName('FormSelectHelper'));
        $this->assertEquals('Zend_View_Helper_FormSelectHelper', $loader->getClassName('FormSelectHelper'));
    }

    public function testGetClassAfterStaticLoadReturnsResolvedClassName()
    {
        $this->key = 'foobar';
        $loader    = new Zend_Loader_PluginLoader(array(), $this->key);
        $loader->addPrefixPath('Zend_View_Helper', $this->testLibPath . '/Zend/View/Helper');
        try {
            $className = $loader->load('FormCheckboxHelper');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertEquals($className, $loader->getClassName('FormCheckboxHelper'));
        $this->assertEquals('Zend_View_Helper_FormCheckboxHelper', $loader->getClassName('FormCheckboxHelper'));
    }

    public function testClassFilesAreSearchedInLifoOrder()
    {
        $loader = new Zend_Loader_PluginLoader(array());
        $loader->addPrefixPath('Zend_View_Helper', $this->libPath . '/Zend/View/Helper');
        $loader->addPrefixPath('ZfTest', dirname(__FILE__) . '/_files/ZfTest');
        try {
            $className = $loader->load('FormSubmit');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertEquals($className, $loader->getClassName('FormSubmit'));
        $this->assertEquals('ZfTest_FormSubmit', $loader->getClassName('FormSubmit'));
    }

    /**
     * @group ZF-2741
     */
    public function testWin32UnderscoreSpacedShortNamesWillLoad()
    {
        $loader = new Zend_Loader_PluginLoader(array());
        $loader->addPrefixPath('Zend_View_Filter', $this->testLibPath . '/Zend/View/Filter');
        try {
            // Plugin loader will attempt to load "c:\path\to\library/Zend/Filter/Word\UnderscoreToDash.php"
            $className = $loader->load('Word_UnderscoreToDash');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertEquals($className, $loader->getClassName('Word_UnderscoreToDash'));
    }

    /**
     * @group ZF-4670
     */
    public function testIncludeCacheShouldBeNullByDefault()
    {
        $this->assertNull(Zend_Loader_PluginLoader::getIncludeFileCache());
    }

    /**
     * @group ZF-4670
     */
    public function testPluginLoaderShouldAllowSpecifyingIncludeFileCache()
    {
        $cacheFile = $this->_includeCache;
        $this->testIncludeCacheShouldBeNullByDefault();
        Zend_Loader_PluginLoader::setIncludeFileCache($cacheFile);
        $this->assertEquals($cacheFile, Zend_Loader_PluginLoader::getIncludeFileCache());
    }

    /**
     * @group ZF-4670
     */
    public function testPluginLoaderShouldThrowExceptionWhenPathDoesNotExist()
    {
        $this->expectException(\Zend_Loader_PluginLoader_Exception::class);

        $cacheFile = dirname(__FILE__) . '/_filesDoNotExist/includeCache.inc.php';
        $this->testIncludeCacheShouldBeNullByDefault();
        Zend_Loader_PluginLoader::setIncludeFileCache($cacheFile);
        $this->fail('Should not allow specifying invalid cache file path');
    }

    /**
     * @group ZF-4670
     */
    public function testPluginLoaderShouldAppendIncludeCacheWhenClassIsFound()
    {
        $cacheFile = $this->_includeCache;
        Zend_Loader_PluginLoader::setIncludeFileCache($cacheFile);
        $loader = new Zend_Loader_PluginLoader(array());
        $loader->addPrefixPath('Zend_View_Helper', $this->libPath . '/Zend/View/Helper');
        $loader->addPrefixPath('ZfTest', dirname(__FILE__) . '/_files/ZfTest');
        try {
            $className = $loader->load('CacheTestFile');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertFileExists($cacheFile);
        $cache = file_get_contents($cacheFile);
        $this->assertContains('CacheTestFile.php', $cache);
    }

    /**
     * @group ZF-5208
     */
    public function testStaticRegistryNamePersistsInDifferentLoaderObjects()
    {
        $loader1 = new Zend_Loader_PluginLoader(array(), 'PluginLoaderStaticNamespace');
        $loader1->addPrefixPath('Zend_View_Helper', 'Zend/View/Helper');

        $loader2 = new Zend_Loader_PluginLoader(array(), 'PluginLoaderStaticNamespace');
        $this->assertEquals(array(
            'Zend_View_Helper_' => array('Zend/View/Helper/'),
        ), $loader2->getPaths());
    }

    /**
     * @group ZF-4697
     */
    public function testClassFilesGrabCorrectPathForLoadedClasses()
    {
        require_once __DIR__ . '/../View/Helper/DeclareVarsHelper.php';
        $reflection = new ReflectionClass('Zend_View_Helper_DeclareVarsHelper');
        $expected   = $reflection->getFileName();

        $loader = new Zend_Loader_PluginLoader(array());
        $loader->addPrefixPath('Zend_View_Helper', $this->libPath . '/Zend/View/Helper');
        $loader->addPrefixPath('ZfTest', dirname(__FILE__) . '/_files/ZfTest');
        try {
            // Class in /Zend/View/Helper and not in /_files/ZfTest
            $className = $loader->load('DeclareVarsHelper');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }

        $classPath = $loader->getClassPath('DeclareVarsHelper');
        $this->assertContains($expected, $classPath);
    }

    /**
     * @group ZF-7350
     */
    public function testPrefixesEndingInBackslashDenoteNamespacedClasses()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped(__CLASS__ . '::' . __METHOD__ . ' requires PHP 5.3.0 or greater');
            return;
        }
        $loader = new Zend_Loader_PluginLoader(array());
        $loader->addPrefixPath('Zfns\\', dirname(__FILE__) . '/_files/Zfns');
        try {
            $className = $loader->load('Foo');
        } catch (Exception $e) {
            $paths = $loader->getPaths();
            $this->fail(sprintf('Failed loading helper; paths: %s', var_export($paths, 1)));
        }
        $this->assertEquals('Zfns\\Foo', $className);
        $this->assertEquals('Zfns\\Foo', $loader->getClassName('Foo'));
    }

    /**
     * @group ZF-9721
     */
    public function testRemovePrefixPathThrowsExceptionIfPathNotRegisteredInPrefix()
    {
        try {
            $loader = new Zend_Loader_PluginLoader(array('My_Namespace_' => 'My/Namespace/'));
            $loader->removePrefixPath('My_Namespace_', 'ZF9721');
            $this->fail();
        } catch (Exception $e) {
            $this->assertTrue($e instanceof Zend_Loader_PluginLoader_Exception);
            $this->assertContains('Prefix My_Namespace_ / Path ZF9721', $e->getMessage());
        }
        $this->assertCount(1, $loader->getPaths('My_Namespace_'));
    }

    /**
     * @group ZF-11330
     */
    public function testLoadClassesWithBackslashInName()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped(__CLASS__ . '::' . __METHOD__ . ' requires PHP 5.3.0 or greater');
            return;
        }
        $loader = new Zend_Loader_PluginLoader(array());
        $loader->addPrefixPath('Zfns\\', dirname(__FILE__) . '/_files/Zfns');
        try {
            $className = $loader->load('Foo\\Bar');
        } catch (Exception $e) {
            $this->fail(sprintf('Failed loading helper with backslashes in name'));
        }
        $this->assertEquals('Zfns\\Foo\\Bar', $className);
    }

    /**
     * @url https://github.com/zendframework/zf1/issues/152
     */
    public function testLoadClassesWithBackslashAndUnderscoreInName()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped(__CLASS__ . '::' . __METHOD__ . ' requires PHP 5.3.0 or greater');
            return;
        }

        $loader = new Zend_Loader_PluginLoader(array());
        $loader->addPrefixPath('Zfns\\Foo_', dirname(__FILE__) . '/_files/Zfns/Foo');

        try {
            $className = $loader->load('Demo');
        } catch (Exception $e) {
            $this->fail(sprintf('Failed loading helper with backslashes and underscores in name'));
        }

        $this->assertEquals('Zfns\Foo_Demo', $className);
    }
}
