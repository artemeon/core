#!/usr/bin/php
<?php

echo "merge and install vendor dependencies" . PHP_EOL;

$strRoot = realpath(__DIR__ . "/../../..");

$arrIncludedModules = [];
if (is_file($strRoot . "/project/packageconfig.json")) {
    $arrIncludedModules = json_decode(file_get_contents($strRoot . "/project/packageconfig.json"), true);
}

// merge composer files
$vendorDir = $strRoot . "/project";
$vendorComposer = $vendorDir . "/composer.json";
$vendorLock = $vendorDir . "/composer.lock";

// we merge and create a composer json file only in case no composer.lock file exists
if (!is_file($vendorLock)) {
    // in case we have no composer.json create one
    if (!is_file($vendorComposer)) {
        $content = <<<'JSON'
{
    "config": {
        "platform": {
            "php": "7.2"
        }
    },
    "scripts": {
        "phpcs": "phpcs -wp --colors --cache",
        "phpcs-modified": "phpcs -wp --colors --cache --filter=gitmodified"
        "phpcbf": "phpcbf -wp",
        "phpcbf-modified": "phpcbf -wp --filter=gitmodified"
    },
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
    $composer["require-dev"] = [];

    $collectRequiredPackages = static function ($requirements, array &$into): void {
        if (isset($requirements) && is_array($requirements)) {
            foreach ($requirements as $name => $version) {
                if (strpos($name, "/") !== false && isset($into[$name])) {
                    if ($into[$name] != $version) {
                        throw new \RuntimeException(
                            sprintf(
                                'Found dependency %s multiple times with different version %s vs %s',
                                $name,
                                $into[$name],
                                $version
                            )
                        );
                    }
                }

                $into[$name] = $version;
            }
        }
    };

    $objCoreDirs = new DirectoryIterator($strRoot);
    foreach ($objCoreDirs as $objCoreDir) {
        if ($objCoreDir->isDir() && substr($objCoreDir->getFilename(), 0, 4) == 'core') {
            $objModuleDirs = new DirectoryIterator($objCoreDir->getRealPath());
            foreach ($objModuleDirs as $objDir) {
                //defined as included?
                if (isset($arrIncludedModules[$objCoreDir->getFilename()]) && !in_array(
                    $objDir->getFilename(),
                    $arrIncludedModules[$objCoreDir->getFilename()]
                )) {
                    continue;
                }

                $composerFile = $objDir->getRealPath() . '/composer.json';
                if (is_file($composerFile)) {
                    $content = json_decode(file_get_contents($composerFile), true);
                    $collectRequiredPackages(@$content["require"], $composer["require"]);
                    $collectRequiredPackages(@$content["require-dev"], $composer["require-dev"]);
                }
            }
        }
    }

    if (empty($composer["require"])) {
        unset($composer["require"]);
    };
    if (empty($composer["require-dev"])) {
        unset($composer["require-dev"]);
    };
    file_put_contents($vendorComposer, json_encode($composer, JSON_PRETTY_PRINT));
} else {
    echo "Composer lock file already exists, the existing dependencies are not updated\n";
}

// install composer
$arrOutput = [];
$intReturn = 0;
exec(
    'composer install --prefer-dist --optimize-autoloader --working-dir ' . escapeshellarg($vendorDir),
    $arrOutput,
    $intReturn
);
if ($intReturn == 127) {
    echo "<span style='color: red;'>composer was not found. please run
        'composer install --prefer-dist --working-dir " . $vendorDir . "'
        manually</span>\n";
}
if ($intReturn == 1) {
    echo "<span style='color: red;'>composer error. please run
        'composer install --prefer-dist --working-dir " . $vendorDir . "'
        manually</span>\n";

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
