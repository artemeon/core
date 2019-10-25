<?php

namespace Kajona\Search\Tests;

use Kajona\Search\Event\SearchObjectdeletedlistener;
use Kajona\Search\Event\SearchRecordupdatedlistener;
use Kajona\Search\System\SearchCommons;
use Kajona\Search\System\SearchSearch;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\Rights;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemModule;
use Kajona\System\Tests\Testbase;
use Kajona\Tags\System\TagsTag;

class SearchIndexEventTest extends Testbase
{

    protected function setUp()
    {
        parent::setUp();
        SearchObjectdeletedlistener::$BIT_UPDATE_INDEX_ON_END_OF_REQUEST = false;
        SearchRecordupdatedlistener::$BIT_UPDATE_INDEX_ON_END_OF_REQUEST = false;

        Rights::getInstance()->setBitTestMode(true);
    }

    protected function tearDown()
    {
        parent::tearDown();
        SearchObjectdeletedlistener::$BIT_UPDATE_INDEX_ON_END_OF_REQUEST = true;
        SearchRecordupdatedlistener::$BIT_UPDATE_INDEX_ON_END_OF_REQUEST = true;

        Rights::getInstance()->setBitTestMode(false);
    }


    public function testIndexEvent()
    {

        if (SystemModule::getModuleByName("tags") == null || SystemModule::getModuleByName("system") == null) {
            return;
        }


        $strSearchKey1 = generateSystemid();

        $objAspect = new SystemAspect();
        $objAspect->setStrName($strSearchKey1);

        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);

        $objSearchCommons = new SearchCommons();

        $objSearchParams = new SearchSearch();
        $objSearchParams->setStrQuery($strSearchKey1);
        $arrResult = $objSearchCommons->doIndexedSearch($objSearchParams, null);
        $this->assertEquals(count($arrResult), 1);
        $this->assertEquals($arrResult[0]->getObjObject()->getStrSystemid(), $objAspect->getStrSystemid());


        $strSearchKey2 = generateSystemid();
        $objTag = new TagsTag();
        $objTag->setStrName($strSearchKey2);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objTag))->update($objTag);


        $objSearchParams = new SearchSearch();
        $objSearchParams->setStrQuery($strSearchKey2);
        $arrResult = $objSearchCommons->doIndexedSearch($objSearchParams, null);
        $this->assertEquals(count($arrResult), 1);
        $this->assertEquals($arrResult[0]->getObjObject()->getStrSystemid(), $objTag->getStrSystemid());


        $objTag->assignToSystemrecord($objAspect->getStrSystemid());

        $arrResult = $objSearchCommons->doIndexedSearch($objSearchParams, null);
        $this->assertEquals(count($arrResult), 2);

        $objSearchParams->setFilterModules([_system_modul_id_]);
        $arrResult = $objSearchCommons->doIndexedSearch($objSearchParams, null);
        $this->assertEquals(count($arrResult), 1);
        $this->assertEquals($arrResult[0]->getObjObject()->getStrSystemid(), $objAspect->getStrSystemid());


        $objTag->removeFromSystemrecord($objAspect->getStrSystemid());

        //the aspect itself should not be found any more
        $objSearchParams = new SearchSearch();
        $objSearchParams->setStrQuery($strSearchKey2);
        $arrResult = $objSearchCommons->doIndexedSearch($objSearchParams, null);
        $this->assertEquals(count($arrResult), 1);
        $this->assertEquals($arrResult[0]->getObjObject()->getStrSystemid(), $objTag->getStrSystemid());


        $objAspect->deleteObjectFromDatabase();
        $objTag->deleteObjectFromDatabase();

    }

}

