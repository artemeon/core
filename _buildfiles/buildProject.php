#!/usr/bin/php
<?php

class BuildHelper
{

    public $strProjectPath = "";

    public $bitOnlyProjectsetup = false;

    public $strConfigFile = "";

    public function main()
    {

        echo "\n";
        echo "Kajona Build Project Helper\n";
        echo " Params:\n";
        echo "   projectPath: ".$this->strProjectPath."\n";
        echo "   configFile: ".$this->strConfigFile."\n";
        echo "   onlySetup: ".($this->bitOnlyProjectsetup ? "true" : "false")."\n";
        echo "\n";
        echo "  PHP Version: ".PHP_VERSION."\n";
        echo "  PHP integer size: ".PHP_INT_SIZE." (4 = 32bit, 8 = 64bit)\n";
        echo "\n";


        $arrCores = array();
        foreach (scandir(__DIR__."/".$this->strProjectPath) as $strRootFolder) {
            if (strpos($strRootFolder, "core") === false) {
                continue;
            }
            $arrCores[] = $strRootFolder;
        }

        //trigger the setup script, try to get the matching one
        $filename = __DIR__ . DIRECTORY_SEPARATOR . $this->strProjectPath . "/core/setupproject.php";
        echo "Calling {$filename}".PHP_EOL;
        require($filename);

        echo "calling cleanCore script: php -f '".__DIR__."/cleanCore.php' '".$this->strProjectPath."'\n";
        $arrReturn = array();
        exec("php -f \"".__DIR__."/cleanCore.php\" \"".$this->strProjectPath."\"", $arrReturn);
        echo implode("\n", $arrReturn)."\n";


        if ($this->bitOnlyProjectsetup) {
            return;
        }

        //include config
        echo "include config.php -> ".__DIR__."/".$this->strConfigFile."\n";
        require(__DIR__."/".$this->strConfigFile);


        echo "creating modified config.php...\n";
        echo "using db-driver ".DB_DRIVER."...\n";
        $strConfigfile = file_get_contents(__DIR__."/".$this->strProjectPath."/core/module_system/system/config/config.php");
        $strConfigfile = str_replace(
            array("%%defaulthost%%", "%%defaultusername%%", "%%defaultpassword%%", "%%defaultdbname%%", "%%defaultdriver%%", "%%defaultport%%"),
            array(DB_HOST, DB_USER, DB_PASS, DB_DB, DB_DRIVER, ""),
            $strConfigfile
        );

        $strSearch = "/\[\'debuglevel\'\]\s* = 0/";
        $strReplace = "['debuglevel'] = 1";
        $strConfigfile = preg_replace($strSearch, $strReplace, $strConfigfile);
        $strSearch = "/\[\'debuglogging\'\]\s* = 1/";
        $strReplace = "['debuglogging'] = 3";
        $strConfigfile = preg_replace($strSearch, $strReplace, $strConfigfile);
        file_put_contents(__DIR__."/".$this->strProjectPath."/project/module_system/system/config/config.php", $strConfigfile);

        echo "starting up system-kernel...\n";
        echo "including ".__DIR__."/".$this->strProjectPath."/core/module_system/bootstrap.php...\n";
        include __DIR__."/".$this->strProjectPath."/core/module_system/bootstrap.php";
        $objCarrier = \Kajona\System\System\Carrier::getInstance();

        echo "dropping old tables...\n";
        $objDB = $objCarrier->getObjDB();
        $arrTables = $objDB->getTables();

        foreach ($arrTables as $strOneTable) {
            $objDB->_pQuery("DROP TABLE ".$strOneTable, array());
        }

        \Kajona\System\System\Carrier::getInstance()->flushCache(\Kajona\System\System\Carrier::INT_CACHE_TYPE_DBQUERIES | \Kajona\System\System\Carrier::INT_CACHE_TYPE_DBTABLES | \Kajona\System\System\Carrier::INT_CACHE_TYPE_MODULES | \Kajona\System\System\Carrier::INT_CACHE_TYPE_OBJECTFACTORY | \Kajona\System\System\Carrier::INT_CACHE_TYPE_ORMCACHE);


        echo "\n";
        echo "Searching for packages to be installed...".PHP_EOL;
        $objManager = new \Kajona\Packagemanager\System\PackagemanagerManager();
        $arrPackagesToInstall = $objManager->getAvailablePackages();


        echo "nr of packages found to install: ".count($arrPackagesToInstall)."\n";
        echo "\n";

        $intMaxLoops = 0;
        echo "starting installations...\n";
        \Kajona\System\System\ResponseObject::getInstance()->setObjEntrypoint(\Kajona\System\System\RequestEntrypointEnum::INSTALLER());

//        //start with the system package
//        foreach ($arrPackagesToInstall as $intKey => $objOneMetadata) {
//            if ($objOneMetadata->getStrTitle() == "system") {
//                $objHandler = $objManager->getPackageManagerForPath($objOneMetadata->getStrPath());
//                echo dateToString(new \Kajona\System\System\Date())." Installing ".$objOneMetadata->getStrTitle()."...\n";
//                $objHandler->installOrUpdate();
//                unset($arrPackagesToInstall[$intKey]);
//            }
//        }

        while (count($arrPackagesToInstall) > 0 && ++$intMaxLoops < 100) {
            /** @var \Kajona\Packagemanager\System\PackagemanagerMetadata $objOneMetadata */
            foreach ($arrPackagesToInstall as $intKey => $objOneMetadata) {
                $objHandler = $objManager->getPackageManagerForPath($objOneMetadata->getStrPath());
                if (!$objOneMetadata->getBitProvidesInstaller()) {
                    $objHandler->installOrUpdate();
                    unset($arrPackagesToInstall[$intKey]);
                    continue;
                }


                if (!$objHandler->isInstallable()) {
                    continue;
                }

                echo dateToString(new \Kajona\System\System\Date())." Installing ".$objOneMetadata->getStrTitle()."...\n";
                $objHandler->installOrUpdate();

                unset($arrPackagesToInstall[$intKey]);
            }
        }


        echo "Installing samplecontent...\n\n";
        foreach (\Kajona\Installer\System\SamplecontentInstallerHelper::getSamplecontentInstallers() as $objOneInstaller) {
            if (!$objOneInstaller->isInstalled()) {
                echo dateToString(new \Kajona\System\System\Date())." Installing ".get_class($objOneInstaller)."...\n";
                $objOneInstaller->install();
            }
        }


        echo dateToString(new \Kajona\System\System\Date())." Finished buildProject\n";
    }
}

$build = new BuildHelper();
$build->strProjectPath = $argv[1];
$build->bitOnlyProjectsetup = $argv[2] == "onlySetup";
if (isset($argv[3])) {
    $build->strConfigFile = $argv[3];
}
$build->main();

