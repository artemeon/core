<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Carrier;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Rights;
use Kajona\System\System\SystemModule;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;

class ObjectRightsTest extends Testbase
{

    /**
     * @var Rights
     */
    private $objRights;
    private $strUserId;


    public function testInheritanceForObjects()
    {
        $objRights = Carrier::getInstance()->getObjRights();
        $this->objRights = Carrier::getInstance()->getObjRights();


        //create a new user & group to be used during testing
        $objUser = new UserUser();
        $strUsername = "user_" . generateSystemid();
        $objUser->setStrUsername($strUsername);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objUser))->update($objUser);
        $this->strUserId = $objUser->getSystemid();

        $objGroup = new UserGroup();
        $strName = "name_" . generateSystemid();
        $objGroup->setStrName($strName);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objGroup))->update($objGroup);

        $objGroup->getObjSourceGroup()->addMember($objUser->getObjSourceUser());

        $strModuleId = $this->createObject("Kajona\\System\\System\\SystemModule", "0")->getSystemid();
        Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_MODULES);
        SystemModule::getAllModules();

        $strRootId = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strModuleId)->getSystemid();
        $strSecOne = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strRootId)->getSystemid();
        $strSecTwo = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strRootId)->getSystemid();

        $strThirdOne1 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strSecOne)->getSystemid();
        $strThirdOne2 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strSecOne)->getSystemid();
        $strThirdTwo1 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strSecTwo)->getSystemid();
        $strThirdTwo2 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strSecTwo)->getSystemid();

        $strThird111 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdOne1)->getSystemid();
        $strThird112 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdOne1)->getSystemid();
        $strThird121 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdOne2)->getSystemid();
        $strThird122 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdOne2)->getSystemid();
        $strThird211 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdTwo1)->getSystemid();
        $strThird212 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdTwo1)->getSystemid();
        $strThird221 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdTwo2)->getSystemid();
        $strThird222 = $this->createObject("Kajona\\Tags\\System\\TagsTag", $strThirdTwo2)->getSystemid();
        $arrThirdLevelNodes = array($strThird111, $strThird112, $strThird121, $strThird122, $strThird211, $strThird212, $strThird221, $strThird222);


        foreach ($arrThirdLevelNodes as $strOneRootNode) {
            $this->checkNodeRights($strOneRootNode, false, false);
        }

        $objRights->addGroupToRight($objGroup->getSystemid(), $strModuleId, "view");
        $objRights->addGroupToRight($objGroup->getSystemid(), $strModuleId, "edit");


        foreach ($arrThirdLevelNodes as $strOneRootNode) {
            $this->checkNodeRights($strOneRootNode, true, true);
        }


        $objRights->removeGroupFromRight($objGroup->getSystemid(), $strSecTwo, "view");
        $this->checkNodeRights($strRootId, true, true);
        $this->checkNodeRights($strSecOne, true, true);
        $this->checkNodeRights($strSecTwo, false, true);
        $this->checkNodeRights($strThirdOne1, true, true);
        $this->checkNodeRights($strThirdOne2, true, true);
        $this->checkNodeRights($strThirdTwo1, false, true);
        $this->checkNodeRights($strThirdTwo2, false, true);
        $this->checkNodeRights($strThird111, true, true);
        $this->checkNodeRights($strThird112, true, true);
        $this->checkNodeRights($strThird121, true, true);
        $this->checkNodeRights($strThird122, true, true);
        $this->checkNodeRights($strThird211, false, true);
        $this->checkNodeRights($strThird212, false, true);
        $this->checkNodeRights($strThird221, false, true);
        $this->checkNodeRights($strThird222, false, true);


        $objTempCommons = Objectfactory::getInstance()->getObject($strSecOne);
        $objTempCommons->setStrPrevId($strThird221);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objTempCommons))->update($objTempCommons);

        $this->checkNodeRights($strRootId, true, true);
        $this->checkNodeRights($strSecOne, false, true);
        $this->checkNodeRights($strSecTwo, false, true);
        $this->checkNodeRights($strThirdOne1, false, true);
        $this->checkNodeRights($strThirdOne2, false, true);
        $this->checkNodeRights($strThirdTwo1, false, true);
        $this->checkNodeRights($strThirdTwo2, false, true);
        $this->checkNodeRights($strThird111, false, true);
        $this->checkNodeRights($strThird112, false, true);
        $this->checkNodeRights($strThird121, false, true);
        $this->checkNodeRights($strThird122, false, true);
        $this->checkNodeRights($strThird211, false, true);
        $this->checkNodeRights($strThird212, false, true);
        $this->checkNodeRights($strThird221, false, true);
        $this->checkNodeRights($strThird222, false, true);


        $objRights->removeGroupFromRight($objGroup->getSystemid(), $strThirdTwo1, "edit");
        $objRights->addGroupToRight($objGroup->getSystemid(), $strThirdTwo1, "view");
        
        $this->checkNodeRights($strRootId, true, true);
        $this->checkNodeRights($strSecOne, false, true);
        $this->checkNodeRights($strSecTwo, false, true);
        $this->checkNodeRights($strThirdOne1, false, true);
        $this->checkNodeRights($strThirdOne2, false, true);
        $this->checkNodeRights($strThirdTwo1, true);
        $this->checkNodeRights($strThirdTwo2, false, true);
        $this->checkNodeRights($strThird111, false, true);
        $this->checkNodeRights($strThird112, false, true);
        $this->checkNodeRights($strThird121, false, true);
        $this->checkNodeRights($strThird122, false, true);
        $this->checkNodeRights($strThird211, true);
        $this->checkNodeRights($strThird212, true);
        $this->checkNodeRights($strThird221, false, true);
        $this->checkNodeRights($strThird222, false, true);


        $objTempCommons = Objectfactory::getInstance()->getObject($strThirdOne1);
        $objTempCommons->setStrPrevId($strThird211);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objTempCommons))->update($objTempCommons);
        
        $this->checkNodeRights($strRootId, true, true);
        $this->checkNodeRights($strSecOne, false, true);
        $this->checkNodeRights($strSecTwo, false, true);
        $this->checkNodeRights($strThirdOne1, true);
        $this->checkNodeRights($strThirdOne2, false, true);
        $this->checkNodeRights($strThirdTwo1, true);
        $this->checkNodeRights($strThirdTwo2, false, true);
        $this->checkNodeRights($strThird111, true);
        $this->checkNodeRights($strThird112, true);
        $this->checkNodeRights($strThird121, false, true);
        $this->checkNodeRights($strThird122, false, true);
        $this->checkNodeRights($strThird211, true);
        $this->checkNodeRights($strThird212, true);
        $this->checkNodeRights($strThird221, false, true);
        $this->checkNodeRights($strThird222, false, true);


        $objTempCommons = Objectfactory::getInstance()->getObject($strSecOne);
        $objTempCommons->setStrPrevId($strRootId);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objTempCommons))->update($objTempCommons);
        $objTempCommons = Objectfactory::getInstance()->getObject($strThirdOne1);
        $objTempCommons->setStrPrevId($strSecOne);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objTempCommons))->update($objTempCommons);
        $objRights->setInherited(true, $strThirdOne1);
        $this->checkNodeRights($strRootId, true, true);
        $this->checkNodeRights($strSecOne, true, true);
        $this->checkNodeRights($strSecTwo, false, true);
        $this->checkNodeRights($strThirdOne1, true, true);
        $this->checkNodeRights($strThirdOne2, true, true);
        $this->checkNodeRights($strThirdTwo1, true);
        $this->checkNodeRights($strThirdTwo2, false, true);
        $this->checkNodeRights($strThird111, true, true);
        $this->checkNodeRights($strThird112, true, true);
        $this->checkNodeRights($strThird121, true, true);
        $this->checkNodeRights($strThird122, true, true);
        $this->checkNodeRights($strThird211, true);
        $this->checkNodeRights($strThird212, true);
        $this->checkNodeRights($strThird221, false, true);
        $this->checkNodeRights($strThird222, false, true);


        $objRights->setInherited(true, $strSecTwo);
        $objRights->setInherited(true, $strThirdTwo1);
        
        $this->checkNodeRights($strRootId, true, true);
        $this->checkNodeRights($strSecOne, true, true);
        $this->checkNodeRights($strSecTwo, true, true);
        $this->checkNodeRights($strThirdOne1, true, true);
        $this->checkNodeRights($strThirdOne2, true, true);
        $this->checkNodeRights($strThirdTwo1, true, true);
        $this->checkNodeRights($strThirdTwo2, true, true);
        $this->checkNodeRights($strThird111, true, true);
        $this->checkNodeRights($strThird112, true, true);
        $this->checkNodeRights($strThird121, true, true);
        $this->checkNodeRights($strThird122, true, true);
        $this->checkNodeRights($strThird211, true, true);
        $this->checkNodeRights($strThird212, true, true);
        $this->checkNodeRights($strThird221, true, true);
        $this->checkNodeRights($strThird222, true, true);

        

        Objectfactory::getInstance()->getObject($strThird111)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThird112)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThird121)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThird122)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThird211)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThird212)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThird221)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThird222)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThirdOne1)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThirdOne2)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThirdTwo1)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strThirdTwo2)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strSecOne)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strSecTwo)->deleteObjectFromDatabase();
        Objectfactory::getInstance()->getObject($strRootId)->deleteObjectFromDatabase();

        Objectfactory::getInstance()->getObject($strModuleId)->deleteObjectFromDatabase();

        $objUser->deleteObjectFromDatabase();
        $objGroup->deleteObjectFromDatabase();

    }


    private function checkNodeRights(
        $strNodeId,
        $bitView = false,
        $bitEdit = false,
        $bitDelete = false,
        $bitRights = false,
        $bitRight1 = false,
        $bitRight2 = false,
        $bitRight3 = false,
        $bitRight4 = false,
        $bitRight5 = false
    )
    {

        $objTestObject = Objectfactory::getInstance()->getObject($strNodeId);

        $this->assertEquals($bitView, $this->objRights->rightView($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights View " . $strNodeId);
        $this->assertEquals($bitEdit, $this->objRights->rightEdit($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Edit " . $strNodeId);
        $this->assertEquals($bitDelete, $this->objRights->rightDelete($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Delete " . $strNodeId);
        $this->assertEquals($bitRights, $this->objRights->rightRight($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Rights" . $strNodeId);
        $this->assertEquals($bitRight1, $this->objRights->rightRight1($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Right1" . $strNodeId);
        $this->assertEquals($bitRight2, $this->objRights->rightRight2($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Right2" . $strNodeId);
        $this->assertEquals($bitRight3, $this->objRights->rightRight3($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Right3" . $strNodeId);
        $this->assertEquals($bitRight4, $this->objRights->rightRight4($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Right4" . $strNodeId);
        $this->assertEquals($bitRight5, $this->objRights->rightRight5($strNodeId, $this->strUserId), __FILE__ . " checkNodeRights Right5" . $strNodeId);

    }


}

