<?php


$bitRemoveSource = isset($argv[1]) && $argv[1] == "deletesource" ? true : false;

/**
 * CLI params:
 * deploypath=xxx
 * removesource=xxx
 *
 * Class PharCreator
 */
class PharCreator
{

    public $strDeployPath = "";
//    public $strDeployPath = "/Users/sidler/web/kajona_build/kajona";

    public $bitRemoveSource = false;


    public function generatePhars()
    {
        $arrCores = scandir("./../");

        foreach ($arrCores as $strOneCore) {
            if (strpos($strOneCore, "core") === false) {
                continue;
            }

            $arrFiles = scandir("./../".$strOneCore);

            foreach ($arrFiles as $strFile) {

                if (is_dir(__DIR__."/../".$strOneCore."/".$strFile) && (substr($strFile, 0, 7) == 'module_')) {
                    $this->generatePhar($strFile, $strOneCore);
                }

            }
        }
    }

    public function generatePhar($strFile, $strOneCore)
    {
        $strModuleName = substr($strFile, 7);
        $strPharName = $strFile.".phar";


        $strTargetPath = __DIR__."/../".$strOneCore."/".$strPharName;
        if ($this->strDeployPath != "" && is_dir($this->strDeployPath."/".$strOneCore)) {
            $strTargetPath = $this->strDeployPath."/".$strOneCore."/".$strPharName;
        }

        $phar = new Phar(
            $strTargetPath,
            FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::KEY_AS_FILENAME,
            $strPharName
        );
        $phar->buildFromDirectory(__DIR__."/../".$strOneCore."/module_".$strModuleName);
        $phar->setStub($phar->createDefaultStub());
        // Compression with ZIP or GZ?
        //$phar->convertToExecutable(Phar::ZIP);
        //$phar->compress(Phar::GZ);
        echo 'Generated phar '.$strPharName."\n";

        if($this->bitRemoveSource) {
            $this->rrmdir(__DIR__."/../".$strOneCore."/module_".$strModuleName);
        }
    }

    public function generateVendorZip()
    {
        $rootPath = realpath(__DIR__ . "/../project/vendor");
        $zipFile = $rootPath . ".zip";

        // @see https://stackoverflow.com/questions/4914750/how-to-zip-a-whole-folder-using-php
        // Initialize archive object
        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        // Create recursive directory iterator
        /** @var SplFileInfo[] $files */
        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($rootPath),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($rootPath) + 1);

                // Add current file to archive
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();

        echo 'Generated zip '.$zipFile."\n";
    }

    public function parseParams($arrParams) {
        $arrParsed = array();

        foreach($arrParams as $strOneParam) {
            $arrOneParam = explode("=", $strOneParam);
            if(count($arrOneParam) == 2) {
                $arrParsed[$arrOneParam[0]] = $arrOneParam[1];
            }
        }

        if(isset($arrParsed["deploypath"])) {
            $this->strDeployPath = $arrParsed["deploypath"];
        }

        if(isset($arrParsed["removesource"])) {
            $this->bitRemoveSource = $arrParsed["removesource"];
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
                    if (is_dir($strDir."/".$objObject)) {
                        $this->rrmdir($strDir."/".$objObject);
                    } else {
                        $intRetry = 0;
                        while (!unlink($strDir."/".$objObject) && $intRetry < 8) {
                            sleep(2);
                            $intRetry++;
                        }
                    }
                }
            }
            rmdir($strDir);
        }
    }

}

$objCreator = new PharCreator();
$objCreator->parseParams(array_slice($argv, 1));
$objCreator->generatePhars();
