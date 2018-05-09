<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Lifecycle\ServiceLifeCycleUpdateException;
use Kajona\System\System\Model;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Reflection;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\StringUtil;
use ReflectionClass;

class GeneralModelTest extends Testbase
{


    public function testModuleModels()
    {

        Carrier::getInstance()->getObjRights()->setBitTestMode(true);

        $arrFiles = Resourceloader::getInstance()->getFolderContent("/system", array(".php"), false, null,
            function (&$strOneFile, $strFilename) {

                $objInstance = Classloader::getInstance()->getInstanceFromFilename($strFilename, "Kajona\\System\\System\\Model");

                if ($objInstance == null) {
                    return;
                }

                $objClass = new ReflectionClass($objInstance);

                $objAnnotations = new Reflection($objInstance);

                //block from autotesting?
                if ($objAnnotations->hasClassAnnotation("@blockFromAutosave")) {
                    echo "skipping class " . StringUtil::substring($strOneFile, 0, -4) . " due to @blockFromAutosave annotation" . "\n";
                    return;
                }

                $strOneFile = $objClass->newInstance();

            });

        $arrSystemids = array();

        /** @var $objOneInstance Model */
        foreach ($arrFiles as $objOneInstance) {

            if (!is_object($objOneInstance)) {
                continue;
            }

            //echo "testing object of type " . get_class($objOneInstance) . "@" . $objOneInstance->getSystemid() . "\n";
            try {
                ServiceLifeCycleFactory::getLifeCycle(get_class($objOneInstance))->update($objOneInstance);
                $this->assertTrue(true);
            } catch (ServiceLifeCycleUpdateException $e) {
                $this->fail("error saving object " . get_class($objOneInstance));
            }
            $arrSystemids[$objOneInstance->getSystemid()] = get_class($objOneInstance);
            //echo " ...saved object of type " . get_class($objOneInstance) . "@" . $objOneInstance->getSystemid() . "\n";
        }

        $objObjectfactory = Objectfactory::getInstance();
        foreach ($arrSystemids as $strSystemid => $strClass) {

            //echo "instantiating " . $strSystemid . "@" . $strClass . "\n";

            $objInstance = $objObjectfactory->getObject($strSystemid);

            $this->assertTrue($objInstance != null);

            $this->assertEquals(get_class($objInstance), $strClass);


            //echo "deleting " . $strSystemid . "@" . $strClass . "\n";
            $objInstance->deleteObjectFromDatabase();
        }


        Carrier::getInstance()->getObjRights()->setBitTestMode(false);

    }

}

