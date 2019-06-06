<?php

namespace Kajona\System\Tests;

use Kajona\System\System\Carrier;

class DatabaseMultiInsertTest extends Testbase
{


    public function tearDown()
    {
        $this->flushDBCache();
        if (in_array("agp_temp_autotest", Carrier::getInstance()->getObjDB()->getTables())) {
            $strQuery = "DROP TABLE agp_temp_autotest";
            Carrier::getInstance()->getObjDB()->_pQuery($strQuery, array());
        }

        parent::tearDown();
    }

    public function testInserts()
    {

        $objDB = Carrier::getInstance()->getObjDB();

        //echo "current driver: " . Carrier::getInstance()->getObjConfig()->getConfig("dbdriver") . "\n";

        $arrFields = array();
        $arrFields["temp_id"] = array("char20", false);
        $arrFields["temp_int"] = array("int", true);
        $arrFields["temp_long"] = array("long", true);
        $arrFields["temp_double"] = array("double", true);
        $arrFields["temp_char10"] = array("char10", true);
        $arrFields["temp_char20"] = array("char20", true);
        $arrFields["temp_char100"] = array("char100", true);
        $arrFields["temp_char254"] = array("char254", true);
        $arrFields["temp_char500"] = array("char500", true);
        $arrFields["temp_text"] = array("text", true);
        $arrFields["temp_longtext"] = array("longtext", true);
        $arrFields["temp_json"] = array("longtext", true);

        $escapeValues = array_fill(0, count($arrFields), false);

        $this->assertEquals(count($arrFields), count($escapeValues));
        $this->assertTrue($objDB->createTable("agp_temp_autotest", $arrFields, array("temp_id")), "testDataBase createTable");

        $strQuery = "DELETE FROM agp_temp_autotest";
        $this->assertTrue($objDB->_pQuery($strQuery, array()), "testDataBase truncateTable");
        $objDB->flushQueryCache();

        $arrValues = array();
        for ($intI = 1; $intI <= 50; $intI++) {
            $arrValues[] = array(
                "id" . $intI,
                10,
                13,
                13.37,
                "char10",
                "char20",
                "char100",
                "char254",
                "char500",
                "text",
                "longtext",
                '{"structure":"json","valid":"escaping"}'
            );
        }

        $this->assertTrue($objDB->multiInsert("agp_temp_autotest", array_keys($arrFields), $arrValues, $escapeValues));

        $arrRow = $objDB->getPRow("SELECT COUNT(*) AS cnt FROM agp_temp_autotest", array());
        $this->assertEquals($arrRow["cnt"], 50);

        for ($intI = 1; $intI <= 50; $intI++) {
            $arrRow = $objDB->getPRow("SELECT * FROM agp_temp_autotest WHERE temp_id = ?", array("id" . $intI));

            $this->assertEquals(10, $arrRow["temp_int"]);
            $this->assertEquals(13, $arrRow["temp_long"]);
            $this->assertEquals(13.37, round($arrRow["temp_double"], 2));
            $this->assertEquals("char10", $arrRow["temp_char10"]);
            $this->assertEquals("char20", $arrRow["temp_char20"]);
            $this->assertEquals("char100", $arrRow["temp_char100"]);
            $this->assertEquals("char254", $arrRow["temp_char254"]);
            $this->assertEquals("char500", $arrRow["temp_char500"]);
            $this->assertEquals("text", $arrRow["temp_text"]);
            $this->assertEquals("longtext", $arrRow["temp_longtext"]);
            $this->assertEquals('{"structure":"json","valid":"escaping"}', $arrRow["temp_json"]);
        }

        $strQuery = "DELETE FROM agp_temp_autotest";
        $this->assertTrue($objDB->_pQuery($strQuery, array()), "testDataBase truncateTable");
        $objDB->flushQueryCache();

        $strQuery = "SELECT COUNT(*) AS cnt FROM agp_temp_autotest";
        $this->assertEquals(0, $objDB->getPRow($strQuery, array())["cnt"], "testDataBase countLimitReach");

        $objDB->flushQueryCache();

        $arrValues = array();
        for ($intI = 1; $intI <= 1200; $intI++) {
            $arrValues[] = array(generateSystemid(), "text long " . $intI, "text " . $intI);
        }
        $this->assertTrue($objDB->multiInsert("agp_temp_autotest", array("temp_id", "temp_char254", "temp_char100"), $arrValues));
        $strQuery = "SELECT COUNT(*) AS cnt FROM agp_temp_autotest";
        $this->assertEquals(1200, $objDB->getPRow($strQuery, array())["cnt"], "testDataBase countLimitReach");


        $strQuery = "DROP TABLE agp_temp_autotest";
        $this->assertTrue($objDB->_pQuery($strQuery, array()), "testDataBase dropTable");

    }


}

