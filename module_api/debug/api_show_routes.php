<?php

/** @var \Kajona\Api\System\EndpointScanner $scanner */
$scanner = \Kajona\System\System\Carrier::getInstance()->getContainer()->offsetGet(\Kajona\Api\System\ServiceProvider::STR_ENDPOINT_SCANNER);
$endpoints = $scanner->getEndpoints();

foreach ($endpoints as $endpoint) {
    echo sprintf("%s %s => %s@%s\n", str_pad(implode("|", $endpoint["httpMethod"]), 6), str_pad($endpoint["path"], 64), $endpoint["class"], $endpoint["methodName"]);
}

