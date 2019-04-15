#!/usr/bin/php
<?php

echo "compiling and minifying skin css files".PHP_EOL;

echo "Collecting less files".PHP_EOL;

$strRoot = realpath(__DIR__."/../../..");
$arrFiles = [

];


//search less folders
$objIterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($strRoot, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
    RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
);

$arrIncludedModules = [];
if (is_file($strRoot."/project/packageconfig.php")) {
    include $strRoot."/project/packageconfig.php";
}
if (!isset($arrExcludedModules["core"])) {
    $arrExcludedModules["core"] = [];
}
$arrExcludedModules["core"][] = "_buildfiles";
$arrExcludedModules["core"][] = "module_installer";

if (in_array("module_v4skin", $arrExcludedModules['core']) || !in_array("module_v4skin", $arrIncludedModules['core'] ?? ["module_v4skin"])) {
    echo "less build not required".PHP_EOL;
    exit(0);
}

$arrFolders = [];
foreach ($objIterator as $strPath => $objDir) {
    $strTestPath = str_replace([$strRoot.DIRECTORY_SEPARATOR, "\\"], ["", "/"], $strPath);
    $arrPath = explode("/", $strTestPath);

    if (count($arrPath) > 2) {
        //defined as included?
        if (array_key_exists($arrPath[0], $arrIncludedModules)) {
            if (!in_array($arrPath[1], $arrIncludedModules[$arrPath[0]])) {
                continue;
            }
        }

        //defined as excluded?
        if (array_key_exists($arrPath[0], $arrExcludedModules)) {
            if (in_array($arrPath[1], $arrExcludedModules[$arrPath[0]])) {
                continue;
            }
        }

        if (strpos($strTestPath, "module_v4skin/admin/skins/kajona_v4") !== false) {
            continue;
        }
    } else {
        continue;
    }

    if ($objDir->getFilename() == "less" && $objDir->isDir()) {
        $arrFolders[] = $strPath;
        //fetch all less files inside
        foreach (scandir($strPath) as $strFile) {
            if (substr($strFile, -5) == ".less") {
                $arrFiles[] = $strPath.DIRECTORY_SEPARATOR.$strFile;
            }
        }

    }
}

natsort($arrFiles);
array_unshift($arrFiles, $strRoot."/core/module_v4skin/admin/skins/kajona_v4/less/bootstrap.less");

//create a temp less file
$strFile = "";
foreach ($arrFiles as $strLess) {
    //make it relative
    $strLess = str_replace([$strRoot."/", $strRoot.DIRECTORY_SEPARATOR, "\\"], ["", "", "/"], $strLess);
    $strLess = "../../../../../../".$strLess;
    $strFile .= "  @import \"".$strLess."\";".PHP_EOL;
}

echo "Temp file:".PHP_EOL.$strFile;

file_put_contents($strRoot."/core/module_v4skin/admin/skins/kajona_v4/less/styles.less", $strFile);

//files to compile
$arrFilesToCompile = array(
    __DIR__."/../../module_v4skin/admin/skins/kajona_v4/less/styles.less" => __DIR__."/../../module_v4skin/admin/skins/kajona_v4/less/styles.min.css"
);

foreach ($arrFilesToCompile as $strSourceFile => $strTargetFile) {
    if (is_file($strSourceFile)) {
        echo "Compiling ".$strSourceFile.PHP_EOL;
        $strLessBin = "node " . __DIR__ . "/../jstests/node_modules/less/bin/lessc";
        system($strLessBin . " --verbose " . escapeshellarg($strSourceFile) . " " . escapeshellarg($strTargetFile), $exitCode);
        if ($exitCode !== 0) {
            echo "Error exited with a non successful status code";
            exit(1);
        }

        echo "Minifiying ".$strTargetFile.PHP_EOL;
        $strMinifyBin = "node " . __DIR__ . "/../jstests/node_modules/clean-css-cli/bin/cleancss";
        system($strMinifyBin . " -o ". escapeshellarg($strTargetFile)." ". escapeshellarg($strTargetFile), $exitCode);
        if ($exitCode !== 0) {
            echo "Error exited with a non successful status code";
            exit(1);
        }
    } else {
        echo "Skipping ".$strSourceFile.", not existing".PHP_EOL;
    }
}

unlink($strRoot."/core/module_v4skin/admin/skins/kajona_v4/less/styles.less");
echo "Done.".PHP_EOL;