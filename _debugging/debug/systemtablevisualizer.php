<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2015 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                           *
********************************************************************************************************/

echo "+-------------------------------------------------------------------------------+\n";
echo "| Kajona Debug Subsystem                                                        |\n";
echo "|                                                                               |\n";
echo "| System Table Visualizer                                                       |\n";
echo "|                                                                               |\n";
echo "| Providing a tree-like view on your system-table.                              |\n";
echo "+-------------------------------------------------------------------------------+\n";



$objDb = class_carrier::getInstance()->getObjDB();


echo "scanning system-table...\n";
$strQuery = "SELECT system_id FROM "._dbprefix_."system";
$arrSystemids = $objDb->getPArray($strQuery, array());

echo "  found ".count($arrSystemids)." systemrecords.\n";

echo "traversing internal tree structure...\n\n";

echo "root-record / 0\n";
$objCommon = new class_module_system_common();
$arrChilds = $objCommon->getChildNodesAsIdArray("0");

echo "<div style=\"border: 1px solid #cccccc; margin: 0 0 10px 0px;\" >";
foreach($arrChilds as $strSingleId) {
    if(validateSystemid($strSingleId))
        printSingleLevel($strSingleId, $arrSystemids);
}
echo "</div>";

echo "<script type=\"text/javascript\" >";
echo "function fold(id, callbackShow) {";
echo "	var style = document.getElementById(id).style.display;";
echo "	if (style == 'none') {";
echo "		document.getElementById(id).style.display = 'block';";
echo "		if (callbackShow != undefined) {";
echo "			callbackShow();";
echo "		}";
echo "	} else {";
echo "		document.getElementById(id).style.display = 'none';";
echo "	}";
echo "}";

echo "</script>";

foreach($arrSystemids as $intI => $strId) {
    if($strId["system_id"] == "0") {
        unset($arrSystemids[$intI]);
        break;
    }
}
echo "Remaining records not in hierarchy: ".count($arrSystemids)."\n";

foreach($arrSystemids as $intI => $strId) {
    echo " > ".$strId["system_id"]."\n";
}

echo "\n\n";
echo "+-------------------------------------------------------------------------------+\n";
echo "| (c) www.kajona.de                                                             |\n";
echo "+-------------------------------------------------------------------------------+\n";


function printSingleLevel($strStartId, &$arrGlobalNodes) {

    foreach($arrGlobalNodes as $intI => $strId) {
        if($strId["system_id"] == $strStartId) {
            unset($arrGlobalNodes[$intI]);
            break;
        }
    }


    $objCommon = new class_module_system_common($strStartId);
    $arrRecord = $objCommon->getSystemRecord();

    $arrChilds = $objCommon->getChildNodesAsIdArray();


    echo "<div style=\"padding-bottom: 5px; ".(count($arrChilds) > 0 ? " cursor: pointer; ": "" )."  \"
             onmouseover=\"this.style.backgroundColor='#cccccc';\" onmouseout=\"this.style.backgroundColor='#ffffff';\"
            ".(count($arrChilds) > 0 ? " onclick=\"javascript:fold('".$strStartId."')\"  " : " " )."
            >";
    $strStatus = "<span style=\"color: green; \">active</span>";
    if($objCommon->getIntRecordStatus() == 0)
        $strStatus = "<span style=\"color: red;\">inactive</span>";

    if(count($arrChilds) > 0)
        echo    " + ";
    else
        echo    "  ";

    echo $objCommon->getStrRecordClass()." / ".$objCommon->getRecordComment()." / ".$objCommon->getSystemid()."\n";

    echo "   state: ".$strStatus ." module nr: ".$arrRecord["system_module_nr"]." sort: ".$arrRecord["system_sort"]."\n";

    echo "</div>";



    if(count($arrChilds) > 0) {

        echo "<div id=\"".$strStartId."\" style=\"border: 1px solid #cccccc; margin: 0 0 0px 20px; display: none;\" >";
        for($intI = 0; $intI < count($arrChilds); $intI++ ) {
            $strSingleId = $arrChilds[$intI];
            printSingleLevel($strSingleId, $arrGlobalNodes);
        }
        echo "</div>";
    }
}

