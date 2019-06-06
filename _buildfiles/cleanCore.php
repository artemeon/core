#!/usr/bin/php
<?php

class CleanCoreHelper
{

    public $strProjectPath = "";


    public function main()
    {

        echo "\n\n";
        echo "Kajona Clean CoreHelper\n";
        echo " Params:\n";
        echo "   projectPath: ".$this->strProjectPath."\n";
        echo "\n\n";

        $arrCores = array();
        foreach (scandir(__DIR__."/".$this->strProjectPath) as $strRootFolder) {
            if (strpos($strRootFolder, "core") === false) {
                continue;
            }
            $arrCores[] = $strRootFolder;
        }


        //trigger cleanups if required, e.g. since a module is excluded or an explicit include list is present
        echo "\n\nSearching for modules at ".__DIR__."/".$this->strProjectPath."/project/packageconfig.json"."\n\n";
        if (file_exists(__DIR__."/".$this->strProjectPath."/project/packageconfig.json")) {
            $arrIncludedModules = json_decode(file_get_contents(__DIR__."/".$this->strProjectPath."/project/packageconfig.json"), true);

            foreach ($arrCores as $strCoreFolder) {
                foreach (scandir(__DIR__."/".$this->strProjectPath."/".$strCoreFolder) as $strOneModule) {
                    if (preg_match("/^(module|_)+.*/i", $strOneModule)) {

                        //skip module if not marked as to be included
                        if (count($arrIncludedModules) > 0 && (isset($arrIncludedModules[$strCoreFolder]) && in_array($strOneModule, $arrIncludedModules[$strCoreFolder]))) {
                            continue;
                        }

                        echo " Deleting ".__DIR__."/".$this->strProjectPath."/".$strCoreFolder."/".$strOneModule."\n";
                        $this->rrmdir(__DIR__."/".$this->strProjectPath."/".$strCoreFolder."/".$strOneModule);
                    }
                }
            }
        }
    }

    /**
     * @param $strDir
     *
     * @see http://www.php.net/manual/de/function.rmdir.php#98622
     */
    private function rrmdir($strDir)
    {
        if (is_dir($strDir)) {
            $arrObjects = scandir($strDir);
            foreach ($arrObjects as $objObject) {
                if ($objObject != "." && $objObject != "..") {
                    if (filetype($strDir."/".$objObject) == "dir") {
                        $this->rrmdir($strDir."/".$objObject);
                    } else {
                        unlink($strDir."/".$objObject);
                    }
                }
            }
            reset($arrObjects);
            rmdir($strDir);
        }
    }
}

$objCoreCleaner = new CleanCoreHelper();
$objCoreCleaner->strProjectPath = $argv[1];
$objCoreCleaner->main();

