<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                      *
********************************************************************************************************/

namespace Kajona\System\System;


/**
 * Generates a graph-instance based on the current config.
 * Therefore either ez components or pChart will be used.
 * Since pChart won't be shipped with kajona, the user has to download it manually.
 *
 * @author sidler@mulchprod.de
 * @since 3.4
 * @package module_system
 */
class GraphFactory {
    //put your code here

    public static $STR_TYPE_EZC = "ezc";
    public static $STR_TYPE_PCHART = "pchart";
    public static $STR_TYPE_FLOT = "flot";
    public static $STR_TYPE_JQPLOT = "jqplot";


    /**
     * Creates a graph-instance either based on the current config or
     * based on the passed param
     *
     * @param string $strType
     *
     * @throws Exception
     * @return GraphInterface
     */
    public static function getGraphInstance($strType = "") {

        if($strType == "") {
            if(SystemSetting::getConfigValue("_system_graph_type_") != "")
                $strType = SystemSetting::getConfigValue("_system_graph_type_");
            else
                $strType = "jqplot";
        }

        $strClassname = "Graph".ucfirst($strType);
        $strPath = Resourceloader::getInstance()->getPathForFile("/system/".$strClassname.".php");
        if($strPath !== false) {
            $objGraph = Classloader::getInstance()->getInstanceFromFilename($strPath, null, "Kajona\\System\\System\\GraphInterface");
            if($objGraph != null)
                return $objGraph;
        }

        throw new Exception("Requested charts-plugin ".$strType." not existing");
    }
}
