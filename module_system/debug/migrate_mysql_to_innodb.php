<?php

use Kajona\System\System\Database;
use Kajona\System\System\StringUtil;
use Kajona\System\System\Config;

if (Config::getInstance()->getConfig("dbdriver") !== "mysqli") {
    echo "No mysqli driver is used";
    exit;
}

$tables = [];
foreach (Database::getInstance()->getTables() as $table) {
    $create = StringUtil::toLowerCase(Database::getInstance()->getPRow("show create table {$table}", [])["Create Table"]);
    if (StringUtil::indexOf($create, "engine=myisam") !== false) {
        $tables[] = $table;
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "Updates tables:\n";
    foreach ($tables as $table) {
        echo $table . "\n";
        Database::getInstance()->_pQuery("ALTER TABLE {$table} ENGINE = InnoDB", []);
    }
} else {
    echo "Found the following MyISAM tables:\n";
    foreach ($tables as $table) {
        echo $table . "\n";
    }
}

echo "<form method='POST'><input type='submit' value='Migrate'></form>";
