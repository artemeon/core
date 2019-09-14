<?php

/** @var \Kajona\Api\System\EndpointScanner $scanner */
$scanner = \Kajona\System\System\Carrier::getInstance()->getContainer()->offsetGet(\Kajona\Api\System\ServiceProvider::ENDPOINT_SCANNER);
$endpoints = $scanner->getEndpoints();

foreach ($endpoints as $endpoint) {
    if (empty($endpoint['authorization'])) {
        echo sprintf("<span style='color:red'>%s %s => %s@%s</span>\n", str_pad(implode("|", $endpoint["httpMethod"]), 6), str_pad($endpoint["path"], 64), $endpoint["class"], $endpoint["methodName"]);
    } else {
        echo sprintf("%s %s => %s@%s\n", str_pad(implode("|", $endpoint["httpMethod"]), 6), str_pad($endpoint["path"], 64), $endpoint["class"], $endpoint["methodName"]);
    }
}

?>

* Red routes have no authorization and can be accessed publicly. In most cases your route needs an @authorization annotation.
