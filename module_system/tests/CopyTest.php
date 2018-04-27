<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Carrier;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Rights;
use Kajona\System\System\SystemAspect;

class CopyTest extends Testbase
{


    function testCopy()
    {


        $objAspect = new SystemAspect();
        $objAspect->setStrName("copytest");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        $strSysid = $objAspect->getSystemid();

        $objAspect->copyObject();
        $strCopyId = $objAspect->getSystemid();


        $objAspect = new SystemAspect($strSysid);
        $objCopy = new SystemAspect($strCopyId);

        $this->assertEquals($objAspect->getStrName(), $objCopy->getStrName());
        $this->assertEquals($objAspect->getStrPrevId(), $objCopy->getStrPrevId());
        $this->assertEquals($objAspect->getIntRecordStatus(), $objCopy->getIntRecordStatus());
        $this->assertEquals($objAspect->getStrRecordClass(), $objCopy->getStrRecordClass());
        $this->assertNotEquals($objAspect->getSystemid(), $objCopy->getSystemid());

        $objAspect->deleteObjectFromDatabase();
        $objCopy->deleteObjectFromDatabase();
    }

    function testCopySystemStatus()
    {


        $objAspect = new SystemAspect();
        $objAspect->setStrName("copytest");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        $strSysid = $objAspect->getSystemid();
        $objAspect->setIntRecordStatus(0);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);

        $objAspect->copyObject();
        $strCopyId = $objAspect->getSystemid();


        $objAspect = new SystemAspect($strSysid);
        $objCopy = new SystemAspect($strCopyId);

        $this->assertEquals($objAspect->getStrName(), $objCopy->getStrName());
        $this->assertEquals($objAspect->getStrPrevId(), $objCopy->getStrPrevId());
        $this->assertEquals($objAspect->getIntRecordStatus(), $objCopy->getIntRecordStatus());
        $this->assertEquals($objAspect->getStrRecordClass(), $objCopy->getStrRecordClass());
        $this->assertNotEquals($objAspect->getSystemid(), $objCopy->getSystemid());

        $objAspect->deleteObjectFromDatabase();
        $objCopy->deleteObjectFromDatabase();
    }


    function testCopyPermissions()
    {

        $objAspect = new SystemAspect();
        $objAspect->setStrName("copytest");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        $strViewId = generateSystemid();
        $strSysid = $objAspect->getSystemid();

        $objRights = Carrier::getInstance()->getObjRights();
        $objRights->addGroupToRight($strViewId, $strSysid, Rights::$STR_RIGHT_RIGHT3);
        $arrRow = $objRights->getArrayRights($strSysid);

        $objAspect->copyObject();
        $strCopyId = $objAspect->getSystemid();
        $arrCopyRow = $objRights->getArrayRights($strCopyId);

        $this->assertEquals($arrRow, $arrCopyRow);

        $objAspect = new SystemAspect($strSysid);
        $objCopy = new SystemAspect($strCopyId);

        $objAspect->deleteObjectFromDatabase();
        $objCopy->deleteObjectFromDatabase();

    }


}

