<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Carrier;
use Kajona\System\System\Date;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Model;
use Kajona\System\System\ModelInterface;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\OrmSchemamanager;

/**
 * Class class_test_orm_schemamanagerTest
 *
 */
class OrmObjectinitTest extends Testbase
{



    protected function setUp()
    {
        $objSchema = new OrmSchemamanager();
        $objSchema->createTable(OrmObjectinitTestclass::class);
        parent::setUp();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $objDb = Carrier::getInstance()->getObjDB();
        $objDb->_pQuery("DROP TABLE agp_inittestclass", array());
        Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_DBTABLES);
    }


    public function tebbkkstObjectInit()
    {
        $objObject = new OrmObjectinitTestclass();

        $objObject->setStrChar20("char20");
        $objObject->setStrChar254("char254");
        $objObject->setStrText("text");
        $objObject->setStrLongtext("longtext");
        $objObject->setIntInteger(12345);
        $objObject->setIntBigint(20161223120000);
        $objObject->setFloatDouble(123.45);
        $objObject->setBitBoolean(false);

        $longDate = 20160827011525;
        $objObject->setObjDate(new Date($longDate));
        ServiceLifeCycleFactory::getLifeCycle(get_class($objObject))->update($objObject);

        Objectfactory::getInstance()->flushCache();

        /** @var OrmObjectinitTestclass $objObj */
        $objObj = Objectfactory::getInstance()->getObject($objObject->getSystemid());

        $this->assertTrue($objObj->getObjDate() instanceof Date);
        $this->assertTrue($objObj->getObjDate()->isSameDay(new Date($longDate)));

        $this->assertSame("char20", $objObj->getStrChar20());
        $this->assertSame("char254", $objObj->getStrChar254());
        $this->assertSame("text", $objObj->getStrText());
        $this->assertSame("longtext", $objObj->getStrLongtext());
        $this->assertSame(12345, $objObj->getIntInteger());
        $this->assertSame(20161223120000, $objObj->getIntBigint());
        $this->assertSame(123.45, round($objObj->getFloatDouble(), 2));
        $this->assertEquals(false, $objObj->getBitBoolean());

        $objObj->setBitBoolean(true);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objObj))->update($objObj);

        $objObj = Objectfactory::getInstance()->getObject($objObject->getSystemid());
        $this->assertEquals(true, $objObj->getBitBoolean());
    }


    public function testObjectInitNull()
    {
        $objObject = new OrmObjectinitTestclass();
        ServiceLifeCycleFactory::getLifeCycle(get_class($objObject))->update($objObject);
        Objectfactory::getInstance()->flushCache();

        /** @var OrmObjectinitTestclass $objObj */
        $objObj = Objectfactory::getInstance()->getObject($objObject->getSystemid());

        $this->assertNull($objObj->getStrChar20());
        $this->assertNull($objObj->getStrChar254());
        $this->assertNull($objObj->getStrText());
        $this->assertNull($objObj->getStrLongtext());
        $this->assertNull($objObj->getIntInteger());
        $this->assertNull($objObj->getIntBigint());
        $this->assertNull($objObj->getFloatDouble());
        $this->assertNull($objObj->getObjDate());
        $this->assertNull($objObj->getBitBoolean());
    }



}



/**
 * Class orm_schematest_testclass
 *
 * @targetTable agp_inittestclass.testclass_id
 * @module system
 * @moduleId _system_modul_id_
 */
class OrmObjectinitTestclass extends Model implements ModelInterface
{

    /**
     * @var Date
     * @tableColumn agp_inittestclass.col1
     * @tableColumnDatatype long
     */
    private $objDate = null;

    /**
     * @var string
     * @tableColumn agp_inittestclass.col2
     * @tableColumnDatatype char20
     */
    private $strChar20 = null;

    /**
     * @var string
     * @tableColumn agp_inittestclass.col3
     * @tableColumnDatatype char254
     */
    private $strChar254 = null;

    /**
     * @var string
     * @tableColumn agp_inittestclass.col4
     * @tableColumnDatatype text
     */
    private $strText = null;

    /**
     * @var string
     * @tableColumn agp_inittestclass.col5
     * @tableColumnDatatype longtext
     */
    private $strLongtext = null;

    /**
     * @var int
     * @tableColumn agp_inittestclass.col6
     * @tableColumnDatatype int
     */
    private $intInteger = null;

    /**
     * @var bool
     * @tableColumn agp_inittestclass.col7
     * @tableColumnDatatype int
     */
    private $bitBoolean = null;

    /**
     * @var int
     * @tableColumn agp_inittestclass.col8
     * @tableColumnDatatype long
     */
    private $intBigint = null;

    /**
     * @var float
     * @tableColumn agp_inittestclass.col9
     * @tableColumnDatatype double
     */
    private $floatDouble = null;

    /**
     * Returns the name to be used when rendering the current object, e.g. in admin-lists.
     *
     * @return string
     */
    public function getStrDisplayName()
    {
        return "testing";
    }

    /**
     * @return Date
     */
    public function getObjDate(): ?Date
    {
        return $this->objDate;
    }

    /**
     * @param Date $objDate
     */
    public function setObjDate(Date $objDate)
    {
        $this->objDate = $objDate;
    }

    /**
     * @return string
     */
    public function getStrChar20(): ?string
    {
        return $this->strChar20;
    }

    /**
     * @param string $strChar20
     */
    public function setStrChar20(string $strChar20)
    {
        $this->strChar20 = $strChar20;
    }

    /**
     * @return string
     */
    public function getStrChar254(): ?string
    {
        return $this->strChar254;
    }

    /**
     * @param string $strChar254
     */
    public function setStrChar254(string $strChar254)
    {
        $this->strChar254 = $strChar254;
    }

    /**
     * @return string
     */
    public function getStrText(): ?string
    {
        return $this->strText;
    }

    /**
     * @param string $strText
     */
    public function setStrText(string $strText)
    {
        $this->strText = $strText;
    }

    /**
     * @return string
     */
    public function getStrLongtext(): ?string
    {
        return $this->strLongtext;
    }

    /**
     * @param string $strLongtext
     */
    public function setStrLongtext(string $strLongtext)
    {
        $this->strLongtext = $strLongtext;
    }

    /**
     * @return int
     */
    public function getIntInteger(): ?int
    {
        return $this->intInteger;
    }

    /**
     * @param int $intInteger
     */
    public function setIntInteger(int $intInteger)
    {
        $this->intInteger = $intInteger;
    }

    /**
     * @return int
     */
    public function getIntBigint(): ?int
    {
        return $this->intBigint;
    }

    /**
     * @param int $intBigint
     */
    public function setIntBigint(int $intBigint)
    {
        $this->intBigint = $intBigint;
    }

    /**
     * @return float
     */
    public function getFloatDouble(): ?float
    {
        return $this->floatDouble;
    }

    /**
     * @param float $floatDouble
     */
    public function setFloatDouble(float $floatDouble)
    {
        $this->floatDouble = $floatDouble;
    }

    /**
     * @return bool
     */
    public function getBitBoolean(): ?bool
    {
        return $this->bitBoolean;
    }

    /**
     * @param bool $bitBoolean
     */
    public function setBitBoolean(bool $bitBoolean)
    {
        $this->bitBoolean = $bitBoolean;
    }

}


