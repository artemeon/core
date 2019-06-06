<?php

// echo "<link rel=\"stylesheet\" href=\"" . _webpath_ . "/core/module_v4skin/admin/skins/kajona_v4/less/styles.min.css?0\" type=\"text/css\">\n";

define("_skinwebpath_", "/");

$arrIcons = \Kajona\V4skin\Admin\Skins\Kajona_V4\AdminskinImageresolver::$arrFAImages;
$objResolver = new \Kajona\V4skin\Admin\Skins\Kajona_V4\AdminskinImageresolver();

foreach ($arrIcons as $strName => $strImage) {
    echo $objResolver->getImage($strName) . " => " . $strName . PHP_EOL;
}
