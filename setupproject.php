<?php
/*"******************************************************************************************************
*   (c) 2007-2011 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id: class_config.php 4235 2011-11-20 19:10:07Z sidler $                                             *
********************************************************************************************************/


class class_project_setup {
    public static function setUp() {

        echo "<b>Kajona V4 project setup.</b>\nCreates the folder-structure required to build a new project.\n\n";

        $strCurFolder = __DIR__;

        echo "core-path: ".$strCurFolder.", ".substr($strCurFolder, -4)."\n";

        if(substr($strCurFolder, -4) != "core") {
            echo "current folder must be named core!";
            return;
        }




        echo "loading core...\n\n";
        include __DIR__."/bootstrap.php";

        $arrModules = scandir(_corepath_);

        $arrModules = array_filter($arrModules, function($strValue) {
            return preg_match("/(module|element|_)+.*/i", $strValue);
        });


        self::checkDir("/admin");
        self::createAdminRedirect();

        self::checkDir("/project");
        self::checkDir("/project/log");
        self::checkDir("/project/dbdumps");
        self::checkDir("/project/lang");
        self::checkDir("/project/system");
        self::checkDir("/project/system/config");
        self::checkDir("/project/portal");
        self::checkDir("/templates");
        self::checkDir("/files");
        self::checkDir("/files/cache");
        self::checkDir("/files/downloads");
        self::checkDir("/files/images");
        self::checkDir("/files/public");

        self::checkDir("/templates/default");
        self::checkDir("/templates/default/js");
        self::checkDir("/templates/default/css");
        self::checkDir("/templates/default/tpl");
        self::checkDir("/templates/default/pics");


        echo "searching for files on root-path...\n";
        foreach($arrModules as $strSingleModule) {
            if(!is_dir(_corepath_."/".$strSingleModule))
                continue;

            $arrContent = scandir(_corepath_."/".$strSingleModule);
            foreach($arrContent as $strSingleEntry) {
                if(substr($strSingleEntry, -5) == ".root") {
                    echo "copy ".$strSingleEntry." to "._realpath_."/".substr($strSingleEntry, 0, -5)."\n";
                    copy(_corepath_."/".$strSingleModule."/".$strSingleEntry, _realpath_."/".substr($strSingleEntry, 0, -5));
                }
            }
        }


        echo "<b>Kajona V4 template setup.</b>\nCreates the default-template-pack required to render pages.\n";
        echo "Files already existing are NOT overwritten.\n";


        foreach($arrModules as $strSingleModule) {
            if(is_dir(_corepath_."/".$strSingleModule."/templates"))
                self::copyFolder(_corepath_."/".$strSingleModule."/templates", _realpath_."/templates");

            if(is_dir(_corepath_."/".$strSingleModule."/files"))
                self::copyFolder(_corepath_."/".$strSingleModule."/files", _realpath_."/files");
        }


        echo "<b>Kajona V4 htaccess setup</b>\n";
        self::createAllowHtaccess("/files/.htaccess");
        self::createAllowHtaccess("/templates/.htaccess");

        self::createDenyHtaccess("/project/.htaccess");

    }



    private static function createAdminRedirect() {
        $strContent  = "<html>\n";
        $strContent .= " <head>\n";
        $strContent .= "  <title>Loading</title>\n";
        $strContent .= "  <meta http-equiv='refresh' content='0; URL=../index.php?admin=1'>\n";
        $strContent .= " </head>\n";
        $strContent .= " <body>Loading...</body>\n";
        $strContent .= "</html>\n";

        file_put_contents(_realpath_."/admin/index.html", $strContent);
    }

    private static function checkDir($strFolder) {
        echo "checking dir "._realpath_.$strFolder."\n";
        if(!is_dir(_realpath_.$strFolder)) {
            mkdir(_realpath_.$strFolder, 0777);
            echo " \t\t... directory created\n";
        }
        else {
            echo " \t\t... already existing.\n";
        }
    }


    private static function copyFolder($strSourceFolder, $strTargetFolder) {
        $arrEntries = scandir($strSourceFolder);
        foreach($arrEntries as $strOneEntry) {
            if($strOneEntry == "." || $strOneEntry == ".." || $strOneEntry == ".svn")
                continue;

            if(is_file($strSourceFolder."/".$strOneEntry) && !is_file($strTargetFolder."/".$strOneEntry)) {
                echo "copying file ".$strSourceFolder."/".$strOneEntry." to ".$strTargetFolder."/".$strOneEntry."\n";
                copy($strSourceFolder."/".$strOneEntry, $strTargetFolder."/".$strOneEntry);
                chmod($strTargetFolder."/".$strOneEntry, 0777);
            }
            else if(is_dir($strSourceFolder."/".$strOneEntry)) {
                if(!is_dir($strTargetFolder."/".$strOneEntry))
                    mkdir($strTargetFolder."/".$strOneEntry, 0777);

                self::copyFolder($strSourceFolder."/".$strOneEntry, $strTargetFolder."/".$strOneEntry);
            }
        }
    }

    private static function createDenyHtaccess($strPath) {
        echo "placing deny htaccess in ".$strPath."\n";
        $strContent = "\n\nDeny from all\n\n";
        file_put_contents(_realpath_.$strPath, $strContent);
    }

    private static function createAllowHtaccess($strPath) {
        echo "placing allow htaccess in ".$strPath."\n";
        $strContent = "\n\nAllow from all\n\n";
        file_put_contents(_realpath_.$strPath, $strContent);
    }
}

echo "<pre>";

class_project_setup::setUp();

echo "</pre>";
