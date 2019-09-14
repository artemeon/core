<?php

namespace Kajona\System\Tests;

use Kajona\System\Admin\AdminController;
use Kajona\System\Admin\AdminInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\RedirectException;
use Kajona\System\System\Reflection;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;
use ReflectionClass;
use ReflectionMethod;

class GeneralActionTest extends Testbase
{

    public static function setUpBeforeClass()
    {
        //Create a new user
        $objUser = new UserUser();
        $objUser->setStrUsername(__CLASS__);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objUser))->update($objUser);

        /** @var UserUser $objAdminGroup */
        $objAdminGroup = Objectfactory::getInstance()->getObject(SystemSetting::getConfigValue("_admins_group_id_"));
        $objAdminGroup->getObjSourceGroup()->addMember($objUser->getObjSourceUser());

        Carrier::getInstance()->getObjSession()->loginUser($objUser);
    }

    public static function tearDownAfterClass()
    {
        Carrier::getInstance()->getObjSession()->logout();
        UserUser::getAllUsersByName(__CLASS__)[0]->deleteObjectFromDatabase();
    }


    public function testAdminModules()
    {

        AdminskinHelper::defineSkinWebpath();
        Carrier::getInstance()->getObjRights()->setBitTestMode(true);

        //load all admin-classes
        $arrFiles = Resourceloader::getInstance()->getFolderContent("/admin", array(".php"), false, null,
            function (&$strOneFile, $strPath) {
                $strOneFile = Classloader::getInstance()->getInstanceFromFilename($strPath, AdminController::class, null, array(), true);
            });

        foreach ($arrFiles as $objAdminInstance) {
            if ($objAdminInstance !== null) {
                $this->runSingleFile($objAdminInstance);
            }
        }

        Carrier::getInstance()->getObjRights()->setBitTestMode(false);

        $this->assertTrue(true);//dummy assertion to make test not risky. Until here no exception should have occurred

    }

    /**
     * @param AdminInterface $objViewInstance
     */
    private function runSingleFile($objViewInstance)
    {

        $objReflection = new ReflectionClass($objViewInstance);
        $arrMethods = $objReflection->getMethods();

        $objAnnotations = new Reflection(get_class($objViewInstance));

        //collect the autotestable annotations located on class-level
        foreach ($objAnnotations->getAnnotationValuesFromClass("@autoTestable") as $strValue) {
            foreach (explode(",", $strValue) as $strOneMethod) {
                //echo "found method " . get_class($objViewInstance) . "@" . $strOneMethod . " marked as class-based @autoTestable, preparing call\n";
                //echo "   calling via action() method\n";
                try {
                    $objViewInstance->action($strOneMethod);
                } catch (RedirectException $e) {
                    // redirect exceptions are allowed
                }
            }
        }


        /** @var ReflectionMethod $objOneMethod */
        foreach ($arrMethods as $objOneMethod) {

            if ($objAnnotations->hasMethodAnnotation($objOneMethod->getName(), "@autoTestable")) {
                //echo "found method " . get_class($objViewInstance) . "@" . $objOneMethod->getName() . " marked as @autoTestable, preparing call\n";

                if (StringUtil::substring($objOneMethod->getName(), 0, 6) == "action" && $objReflection->hasMethod("action")) {
                    //echo "   calling via action() method\n";
                    try {
                        $objViewInstance->action(StringUtil::substring($objOneMethod->getName(), 6));
                    } catch (RedirectException $e) {
                        // redirect exceptions are allowed
                    }
                } else {
                    //echo "   direct call";
                    $objOneMethod->invoke($objViewInstance);
                }
            }
        }

        $this->assertTrue(true);//dummy assertion to make test not risky. Until here no exception should have occurred

    }

}
