#!/usr/bin/php
<?php

echo "merge and install vendor dependencies".PHP_EOL;

$strRoot = realpath(__DIR__."/../../..");

$arrIncludedModules = [];
if (is_file($strRoot."/project/packageconfig.php")) {
    include $strRoot."/project/packageconfig.php";
}
if (!isset($arrExcludedModules["core"])) {
    $arrExcludedModules["core"] = [];
}

// merge composer files
$vendorDir = $strRoot."/project";
$vendorComposer = $vendorDir."/composer.json";
$vendorLock = $vendorDir."/composer.lock";

// we merge and create a composer json file only in case no composer.lock file exists
if (!is_file($vendorLock)) {
    // in case we have no composer.json create one
    if (!is_file($vendorComposer)) {
        $content = <<<'JSON'
{
    "repositories": [
        {
            "type": "composer",
            "url": "https:\/\/buildpackages.kajona.de:5443"
        }
    ]
}
JSON;
        file_put_contents($vendorComposer, $content);
    }

    // merge all packages from all composer.json files inside a module
    $composer = json_decode(file_get_contents($vendorComposer), true);
    $composer["require"] = [];

    $objCoreDirs = new DirectoryIterator($strRoot);
    foreach ($objCoreDirs as $objCoreDir) {
        if ($objCoreDir->isDir() && substr($objCoreDir->getFilename(), 0, 4) == 'core') {
            $objModuleDirs = new DirectoryIterator($objCoreDir->getRealPath());
            foreach ($objModuleDirs as $objDir) {

                //defined as included?
                if (isset($arrIncludedModules[$objCoreDir->getFilename()]) && !in_array($objDir->getFilename(), $arrIncludedModules[$objCoreDir->getFilename()])) {
                    continue;
                }

                //defined as excluded?
                if (isset($arrExcludedModules[$objCoreDir->getFilename()]) && in_array($objDir->getFilename(), $arrExcludedModules[$objCoreDir->getFilename()])) {
                    continue;
                }

                $composerFile = $objDir->getRealPath() . '/composer.json';
                if (is_file($composerFile)) {
                    $content = json_decode(file_get_contents($composerFile), true);

                    if (isset($content["require"]) && is_array($content["require"])) {
                        foreach ($content["require"] as $name => $version) {
                            if (strpos($name, "/") !== false && isset($composer["require"][$name])) {
                                if ($composer["require"][$name] != $version) {
                                    throw new \RuntimeException("Found dependency {$name} multiple times with different version {$composer["require"][$name]} vs {$version}");
                                }
                            }

                            $composer["require"][$name] = $version;
                        }
                    }
                }
            }
        }
    }

    file_put_contents($vendorComposer, json_encode($composer, JSON_PRETTY_PRINT));
} else {
    echo "Composer lock file already exists, the existing dependencies are not updated\n";
}

// install composer
$arrOutput = array();
$intReturn = 0;
exec('composer install --no-dev --prefer-dist --optimize-autoloader --working-dir ' . escapeshellarg($vendorDir), $arrOutput, $intReturn);
if ($intReturn == 127) {
    echo "<span style='color: red;'>composer was not found. please run 'composer install --prefer-dist --working-dir " . $vendorDir . "' manually</span>\n";
}
if ($intReturn == 1) {
    echo "<span style='color: red;'>composer error. please run 'composer install --prefer-dist --working-dir " . $vendorDir . "' manually</span>\n";

    if (!is_writable($vendorDir)) {
        echo "<span style='color: red;'>    target folder " . $vendorDir . " is not writable</span>\n";
    }
}
if ($intReturn !== 0) {
    echo "Error exited with a non successful status code";
    exit(1);
}

echo "Composer install finished for " . $vendorComposer . ": \n";

echo "   " . implode("\n   ", $arrOutput);


// generate also token file for the installer api
echo "\n\nGenerate token key\n\n";

$tokenFile = $vendorDir . "/token.key";
file_put_contents($tokenFile, bin2hex(random_bytes(16)));
