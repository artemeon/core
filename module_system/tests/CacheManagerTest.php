<?php
/*"******************************************************************************************************
*   (c) 2015-2016 by Kajona, www.kajona.de                                                         *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\Tests;

use Kajona\System\System\CacheManager;
use Kajona\System\System\Exception;
use Kajona\System\System\Filesystem;
use PHPUnit\Framework\TestCase;

/**
 * @author christoph.kappestein@gmail.com
 * @since 5.0
 */
class CacheManagerTest extends TestCase
{
    public function testContains()
    {
        $objCacheManager = new CacheManager();

        $this->assertFalse($objCacheManager->containsValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->addValue("foo", "bar", 180, CacheManager::TYPE_ARRAY);

        $this->assertTrue($objCacheManager->containsValue("foo", CacheManager::TYPE_ARRAY));
        $this->assertEquals("bar", $objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->removeValue("foo", CacheManager::TYPE_ARRAY);

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));
        $this->assertFalse($objCacheManager->containsValue("foo", CacheManager::TYPE_ARRAY));
    }

    public function testCacheGetAddRemove()
    {
        $objCacheManager = new CacheManager();

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->addValue("foo", "bar", 180, CacheManager::TYPE_ARRAY);

        $this->assertEquals("bar", $objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->removeValue("foo", CacheManager::TYPE_ARRAY);

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));
    }

    public function testCacheGetAddRemoveNamespace()
    {
        $strKey = __METHOD__;
        $objCacheManager = new CacheManager();

        $this->assertFalse($objCacheManager->getValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_GLOBAL));
        $this->assertFalse($objCacheManager->getValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_BOOTSTRAP));

        $objCacheManager->addValue($strKey, "foo", 180, CacheManager::TYPE_ARRAY, CacheManager::NS_GLOBAL);
        $objCacheManager->addValue($strKey, "bar", 180, CacheManager::TYPE_ARRAY, CacheManager::NS_BOOTSTRAP);

        $this->assertEquals("foo", $objCacheManager->getValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_GLOBAL));
        $this->assertEquals("bar", $objCacheManager->getValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_BOOTSTRAP));

        // check whether we can flush items only for a specific namespace and flush not the complete cache
        $objCacheManager->flushCache(CacheManager::TYPE_ARRAY, CacheManager::NS_GLOBAL);

        $this->assertEquals(false, $objCacheManager->getValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_GLOBAL));
        $this->assertEquals("bar", $objCacheManager->getValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_BOOTSTRAP));

        $objCacheManager->removeValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_BOOTSTRAP);

        $this->assertEquals(false, $objCacheManager->getValue($strKey, CacheManager::TYPE_ARRAY, CacheManager::NS_BOOTSTRAP));
    }

    public function testFlushCache()
    {
        $objCacheManager = new CacheManager();

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->addValue("foo", "bar", 180, CacheManager::TYPE_ARRAY);

        $this->assertEquals("bar", $objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->flushCache(CacheManager::TYPE_ARRAY);

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));
    }

    public function testFlushCacheHard()
    {
        $objCacheManager = new CacheManager();

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_FILESYSTEM));

        $objCacheManager->addValue("foo", "bar", 180, CacheManager::TYPE_FILESYSTEM);

        $this->assertEquals("bar", $objCacheManager->getValue("foo", CacheManager::TYPE_FILESYSTEM));

        //creating the copy-folders that should be removed by test with a cache
        $strCacheDir = _realpath_."project/temp/";
        $strCacheFolderCopy1 = "cache1";
        $strCacheFolderCopy2 = "cache2";
        $objFileSys = new Filesystem();
        $objFileSys->folderCreate($strCacheDir.$strCacheFolderCopy1);
        $objFileSys->folderCreate($strCacheDir.$strCacheFolderCopy2);

        $objCacheManager->flushCache(CacheManager::TYPE_FILESYSTEM, CacheManager::NS_GLOBAL, true);

        $arrFileList = $objFileSys->getCompleteList($strCacheDir, null, null, [".", ".."], true, false);
        $isStrCacheFolderCopy1InList = in_array($strCacheFolderCopy1, $arrFileList['folders']);
        $isStrCacheFolderCopy2InList = in_array($strCacheFolderCopy2, $arrFileList['folders']);

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));
        $this->assertFalse($isStrCacheFolderCopy1InList);
        $this->assertFalse($isStrCacheFolderCopy2InList);
    }

    public function testFlushAll()
    {
        $objCacheManager = new CacheManager();

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->addValue("foo", "bar", 180, CacheManager::TYPE_ARRAY);

        $this->assertEquals("bar", $objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));

        $objCacheManager->flushAll(CacheManager::TYPE_ARRAY);

        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));
    }

    /**
     * Test which simply calls the get value method with every possible type combination
     *
     * @dataProvider typeDataProvider
     */
    public function testTypes($intType)
    {
        $objCacheManager = new CacheManager();

        $this->assertFalse($objCacheManager->getValue("foo", $intType));

        // this call should com from the internal cache
        $this->assertFalse($objCacheManager->getValue("foo", CacheManager::TYPE_ARRAY));
    }

    /**
     * @expectedException Exception
     */
    public function testInvalidType()
    {
        $objCacheManager = new CacheManager();
        $objCacheManager->getValue("foo", 1 << 5);
    }

    public function testGetInstance()
    {
        $this->assertInstanceOf(CacheManager::class, CacheManager::getInstance());
    }

    public function testGetAvailableDriver()
    {
        $this->assertEquals(array(CacheManager::TYPE_APC, CacheManager::TYPE_FILESYSTEM, CacheManager::TYPE_PHPFILE), array_keys(CacheManager::getAvailableDriver()));
    }

    public function typeDataProvider()
    {
        $intMax = CacheManager::TYPE_ARRAY | CacheManager::TYPE_APC | CacheManager::TYPE_FILESYSTEM | CacheManager::TYPE_PHPFILE;
        $arrTypes = array();
        for ($intI = 0; $intI <= $intMax; $intI++) {
            $arrTypes[] = array($intI);
        }

        return $arrTypes;
    }
}
