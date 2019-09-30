<?php
/*"******************************************************************************************************
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/


class class_project_setup
{

    private static $strRealPath = "";

    public static function setUp()
    {

        self::$strRealPath = __DIR__ . "/../";

        echo "<b>ARTEMEON core V7 project setup.</b>\nCreates the folder-structure required to build a new project.\n\n";

        $strCurFolder = __DIR__;

        echo "core-path: " . $strCurFolder . ", folder found: " . substr($strCurFolder, -4) . "\n";

        if (substr($strCurFolder, -4) != "core") {
            echo "current folder must be named core!";
            return;
        }


        $arrIncludedModules = null;
        if (is_file(self::$strRealPath . "project/packageconfig.json")) {
            $arrIncludedModules = json_decode(file_get_contents(self::$strRealPath."project/packageconfig.json"), true);
        }

        //Module-Constants
        $arrModules = array();
        foreach (scandir(self::$strRealPath) as $strRootFolder) {
            if (!isset($arrExcludedModules[$strRootFolder])) {
                $arrExcludedModules[$strRootFolder] = array();
            }

            if (strpos($strRootFolder, "core") === false) {
                continue;
            }

            foreach (scandir(self::$strRealPath . "/" . $strRootFolder) as $strOneModule) {
                if (preg_match("/^(module|_)+.*/i", $strOneModule) && (!is_array($arrIncludedModules) || (isset($arrIncludedModules[$strRootFolder]) && in_array($strOneModule, $arrIncludedModules[$strRootFolder])))) {
                    $arrModules[] = $strRootFolder . "/" . $strOneModule;
                }
            }
        }

        self::checkDir("/bin", false);
        self::createBinReadme();
        self::checkDir("/project/log", true);
        self::checkDir("/project/dbdumps", true);
        self::checkDir("/project/module_system/system/config", true);
        self::checkDir("/project/temp", true);
        self::checkDir("/files/cache", true);
        self::checkDir("/files/downloads/default", true);
        self::checkDir("/files/images", true);
        self::checkDir("/files/extract", true);

        echo "searching for files on root/project-path...\n";
        foreach ($arrModules as $strSingleModule) {
            if (!is_dir(self::$strRealPath . "/" . $strSingleModule)) {
                continue;
            }

            $arrContent = scandir(self::$strRealPath . "/" . $strSingleModule);
            foreach ($arrContent as $strSingleEntry) {
                if (substr($strSingleEntry, -5) == ".root" && !is_file(self::$strRealPath . "/" . substr($strSingleEntry, 0, -5))) {
                    copy(self::$strRealPath . "/" . $strSingleModule . "/" . $strSingleEntry, self::$strRealPath . "/" . substr($strSingleEntry, 0, -5));
                }

                if (substr($strSingleEntry, -8) == ".project" && !is_file(self::$strRealPath . "/project/" . substr($strSingleEntry, 0, -8))) {
                    copy(self::$strRealPath . "/" . $strSingleModule . "/" . $strSingleEntry, self::$strRealPath . "/project/" . substr($strSingleEntry, 0, -8));
                }
            }

            if (is_dir(self::$strRealPath . "/" . $strSingleModule . "/files")) {
                self::copyFolder(self::$strRealPath . "/" . $strSingleModule . "/files", self::$strRealPath . "/files");
            }
        }


        echo "\n<b>htaccess setup</b>\n";
        self::createAllowHtaccess("/files/cache/.htaccess");
        self::createAllowHtaccess("/files/images/.htaccess");
        self::createAllowHtaccess("/files/extract/.htaccess");

        self::createDenyHtaccess("/project/.htaccess");
        self::createDenyHtaccess("/files/.htaccess");

        self::createTokenKey();
        self::createRootGitIgnore();
        self::createDefaultPackageconfig();
        self::createRootTsconfig();
        self::loadNpmDependencies();
        self::buildJavascript();
        self::scanComposer();

        echo "\n<b>Done.</b>\nIf everything went well, <a href=\"../installer.php\">open the installer</a>\n";
    }


    private static function createRootGitIgnore()
    {
        if (is_file(self::$strRealPath . "/.gitignore")) {
            return;
        }
        $content = <<<TEXT
project/temp
project/vendor
project/log
project/dbdumps
files/cache
.vscode
tsconfig.json
.eslintrc.json
TEXT;
        file_put_contents(self::$strRealPath . "/.gitignore", $content);
    }


    private static function createRootTsconfig()
    {
        if (is_file(self::$strRealPath . "/tsconfig.json")) {
            return;
        }
        $content = <<<JSON
{
  "extends": "./core/_buildfiles/tsconfig"
}
JSON;
        file_put_contents(self::$strRealPath . "/tsconfig.json", $content);
    }




    private static function createDefaultPackageconfig()
    {
        if (is_file(self::$strRealPath . "/project/packageconfig.json")) {
            return;
        }

        $cfg = new class {
            public $core = [];
        };
        foreach (scandir(self::$strRealPath."/core") as $strOneEntry) {
            if ($strOneEntry == "." || $strOneEntry == "..") {
                continue;
            }

            if (is_dir(self::$strRealPath."/core" . "/" . $strOneEntry)) {
                $cfg->core[] = $strOneEntry;
            }
        }

        file_put_contents(self::$strRealPath . "/project/packageconfig.json", json_encode($cfg, JSON_PRETTY_PRINT));
    }


    private static function createBinReadme()
    {
        $strContent = <<<TEXT

This folder should contain the following external binaries:

module_fileindexer
* `tika-app-1.17.jar` (https://tika.apache.org/)

TEXT;

        file_put_contents(self::$strRealPath . "/bin/README.md", $strContent);
    }

    private static function checkDir($strFolder, $writeable)
    {
        echo "checking dir " . self::$strRealPath . $strFolder . "\n";
        if (!is_dir(self::$strRealPath . $strFolder)) {
            mkdir(self::$strRealPath . $strFolder, 0777, true);
            echo " \t\t... directory created\n";
        } else {
            echo " \t\t... already existing.\n";
        }
        if ($writeable) {
            chmod(self::$strRealPath . $strFolder, 0777);
        }
    }

    private static function copyFolder($strSourceFolder, $strTargetFolder, $arrExcludeSuffix = array())
    {
        $arrEntries = scandir($strSourceFolder);
        foreach ($arrEntries as $strOneEntry) {
            if ($strOneEntry == "." || $strOneEntry == ".."  || in_array(substr($strOneEntry, strrpos($strOneEntry, ".")), $arrExcludeSuffix)) {
                continue;
            }

            if (is_file($strSourceFolder . "/" . $strOneEntry) && !is_file($strTargetFolder . "/" . $strOneEntry)) {
                //echo "copying file ".$strSourceFolder."/".$strOneEntry." to ".$strTargetFolder."/".$strOneEntry."\n";
                if (!is_dir($strTargetFolder)) {
                    mkdir($strTargetFolder, 0777, true);
                }

                copy($strSourceFolder . "/" . $strOneEntry, $strTargetFolder . "/" . $strOneEntry);
                chmod($strTargetFolder . "/" . $strOneEntry, 0777);
            } elseif (is_dir($strSourceFolder . "/" . $strOneEntry)) {
                self::copyFolder($strSourceFolder . "/" . $strOneEntry, $strTargetFolder . "/" . $strOneEntry, $arrExcludeSuffix);
            }
        }
    }

    private static function createTokenKey()
    {
        // generate also token file for the installer api
        echo "Generate token key\n";

        $tokenFile = self::$strRealPath . "project/token.key";
        file_put_contents($tokenFile, bin2hex(random_bytes(16)));
    }

    private static function createDenyHtaccess($strPath)
    {
        if (is_file(self::$strRealPath . $strPath)) {
            return;
        }

        echo "placing deny htaccess in " . $strPath . "\n";
        $strContent = "\n\nRequire all denied\n\n";
        file_put_contents(self::$strRealPath . $strPath, $strContent);
    }

    private static function createAllowHtaccess($strPath)
    {
        if (is_file(self::$strRealPath . $strPath)) {
            return;
        }

        echo "placing allow htaccess in " . $strPath . "\n";
        $strContent = "\n\nRequire all granted\n\n";
        file_put_contents(self::$strRealPath . $strPath, $strContent);
    }

    private static function buildJavascript()
    {

        echo "Build js files" . PHP_EOL;
        $arrOutput = array();

        $workingDirectory = getcwd();
        chdir(__DIR__ . '/_buildfiles');
        exec('npm run build', $arrOutput, $exitCode);
        chdir($workingDirectory);

        if ($exitCode !== 0) {
            var_dump($arrOutput);
            echo "Error exited with a non successful status code";
            exit(1);
        }
        //echo "   " . implode("\n   ", $arrOutput);

    }

    private static function loadNpmDependencies()
    {
        echo "Installing node dependencies" . PHP_EOL;

        $arrOutput = array();

        $workingDirectory = getcwd();
        chdir(__DIR__ . '/_buildfiles');
        exec('npm config set registry "http://packages.artemeon.int:4873/"');
        exec('npm install', $arrOutput, $exitCode);
        chdir($workingDirectory);

        if ($exitCode !== 0) {
            var_dump($arrOutput);
            echo "Error exited with a non successful status code";
            exit(1);
        }

        echo "   " . implode("\n   ", $arrOutput);
    }


    private static function scanComposer()
    {
        if (is_file(__DIR__ . "/_buildfiles/bin/buildComposer.php")) {
            echo "Install composer dependencies" . PHP_EOL;
            $arrOutput = array();
            exec("php -f " . escapeshellarg(self::$strRealPath . "/core/_buildfiles/bin/buildComposer.php"), $arrOutput, $exitCode);
            if ($exitCode !== 0) {
                echo "Error exited with a non successful status code";
                exit(1);
            }
            echo "   " . implode("\n   ", $arrOutput);
        } else {
            echo "<span style='color: red;'>Missing buildComposer.php helper</span>";
        }
    }
}

echo "<pre>";

class_project_setup::setUp();

echo "</pre>";
