<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;

class LockmanagerTest extends Testbase
{


    public function testLocking()
    {

        return true;
        $objAspect = new SystemAspect();
        $objAspect->setStrName("test");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        $strAspectId = $objAspect->getSystemid();


        $this->assertTrue($objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue(!$objAspect->getLockManager()->isLocked());

        $objUser = new UserUser();
        $objUser->setStrUsername(generateSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objUser))->update($objUser);

        $this->assertTrue(Carrier::getInstance()->getObjSession()->loginUser($objUser));

        $objAspect->getLockManager()->lockRecord();

        $this->assertEquals($objUser->getSystemid(), $objAspect->getLockManager()->getLockId());

        $this->assertTrue($objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue($objAspect->getLockManager()->isLocked());
        $this->assertTrue($objAspect->getLockManager()->isLockedByCurrentUser());

        //updates should release the lock
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);

        $this->assertTrue($objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue(!$objAspect->getLockManager()->isLocked());
        $this->assertTrue(!$objAspect->getLockManager()->isLockedByCurrentUser());


        Carrier::getInstance()->getObjSession()->logout();
        $objAspect = new SystemAspect($strAspectId);
        $objAspect->deleteObjectFromDatabase();
        $objUser->deleteObjectFromDatabase();
    }


    public function testLockBetweenUsers()
    {
        return true;
        $objAspect = new SystemAspect();
        $objAspect->setStrName("test");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        $strAspectId = $objAspect->getSystemid();


        $this->assertTrue($objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue(!$objAspect->getLockManager()->isLocked());

        $objUser1 = new UserUser();
        $objUser1->setStrUsername(generateSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objUser1))->update($objUser1);

        $this->assertTrue(Carrier::getInstance()->getObjSession()->loginUser($objUser1));
        $objAspect->getLockManager()->lockRecord();

        $this->assertEquals($objUser1->getSystemid(), $objAspect->getLockManager()->getLockId());

        $this->assertTrue($objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue($objAspect->getLockManager()->isLocked());
        $this->assertTrue($objAspect->getLockManager()->isLockedByCurrentUser());

        $objUser2 = new UserUser();
        $objUser2->setStrUsername(generateSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objUser2))->update($objUser2);


        $this->assertTrue(Carrier::getInstance()->getObjSession()->loginUser($objUser2));

        $this->assertTrue(!$objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue($objAspect->getLockManager()->isLocked());
        $this->assertTrue(!$objAspect->getLockManager()->isLockedByCurrentUser());


        //updates should release the lock
        $objException = null;
        try {
            ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        } catch (Exception $objEx) {
            $objException = $objEx;
        }

        $this->assertNotNull($objException);

        //lock should remain
        $this->assertTrue(!$objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue($objAspect->getLockManager()->isLocked());
        $this->assertTrue(!$objAspect->getLockManager()->isLockedByCurrentUser());

        $this->assertEquals($objUser1->getSystemid(), $objAspect->getLockManager()->getLockId());

        //unlocking is not allowed for user 2
        $this->assertTrue(!$objAspect->getLockManager()->unlockRecord());

        //force unlock not allowed since user is not in admin group
        $this->assertTrue($objAspect->getLockManager()->unlockRecord(true));

        //lock should remain
        $this->assertTrue($objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue(!$objAspect->getLockManager()->isLocked());
        $this->assertTrue(!$objAspect->getLockManager()->isLockedByCurrentUser());


        //add user 2 to admin group
        $objGroup = new UserGroup(SystemSetting::getConfigValue("_admins_group_id_"));
        $this->assertTrue($objGroup->getObjSourceGroup()->addMember($objUser2->getObjSourceUser()));

        //relogin
        $this->flushDBCache();
        $objUser2 = new UserUser($objUser2->getSystemid());
        $this->assertTrue(Carrier::getInstance()->getObjSession()->loginUser($objUser2));

        //force unlock now allowed since user is not in admin group
        $this->assertTrue($objAspect->getLockManager()->unlockRecord(true));

        //lock should be gone
        $this->assertTrue($objAspect->getLockManager()->isAccessibleForCurrentUser());
        $this->assertTrue(!$objAspect->getLockManager()->isLocked());
        $this->assertTrue(!$objAspect->getLockManager()->isLockedByCurrentUser());


        Carrier::getInstance()->getObjSession()->logout();
        $objAspect = new SystemAspect($strAspectId);
        $objAspect->deleteObjectFromDatabase();
        $objUser1->deleteObjectFromDatabase();
        $objUser2->deleteObjectFromDatabase();
    }


    public function testLockExceptionOnSort()
    {
        return true;
        $objAspect = new SystemAspect();
        $objAspect->setStrName("test");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        $strAspectId = $objAspect->getSystemid();

        $objUser1 = new UserUser();
        $objUser1->setStrUsername(generateSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objUser1))->update($objUser1);

        $this->assertTrue(Carrier::getInstance()->getObjSession()->loginUser($objUser1));

        $objAspect->getLockManager()->lockRecord();
        $this->assertTrue($objAspect->getLockManager()->isLockedByCurrentUser());

        $objUser2 = new UserUser();
        $objUser2->setStrUsername(generateSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objUser2))->update($objUser2);

        $this->assertTrue(Carrier::getInstance()->getObjSession()->loginUser($objUser2));
        $this->assertTrue(!$objAspect->getLockManager()->isLockedByCurrentUser());

        $intSort = $objAspect->getIntSort();
        $objException = null;
        try {
            $objAspect->setAbsolutePosition(4);
        } catch (Exception $objEx) {
            $objException = $objEx;
        }

        $this->assertNotNull($objException);
        $this->assertEquals($intSort, $objAspect->getIntSort());

        Carrier::getInstance()->getObjSession()->logout();
        $objAspect = new SystemAspect($strAspectId);
        $objAspect->deleteObjectFromDatabase();
        $objUser1->deleteObjectFromDatabase();
        $objUser2->deleteObjectFromDatabase();
    }


}

