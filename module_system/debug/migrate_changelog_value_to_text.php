<?php

use Kajona\System\System\Database;
use Kajona\System\System\Config;
use Kajona\System\System\DbDatatypes;
use Kajona\System\System\SystemChangelog;


$arrTables = array("agp_changelog");
$arrProvider = SystemChangelog::getAdditionalProviders();
foreach($arrProvider as $objOneProvider) {
    $arrTables[] = $objOneProvider->getTargetTable();
}

$tables = [];
foreach($arrTables as $strOneTable) {
    //only transform if required
    $metainfo = Database::getInstance()->getTableInformation($strOneTable);
    $col = $metainfo->getColumnByName("change_oldvalue");
    if ($col === null) {
        continue;
    }
    if ($col->getInternalType() == DbDatatypes::STR_TYPE_TEXT) {
        continue;
    }

    $tables[] = $strOneTable;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    echo "Update the following changelog\n";
    foreach ($tables as $table) {
        if (Config::getInstance()->getConfig("dbdriver") == "mysqli") {
            //direct change on the table, if required
            Database::getInstance()->changeColumn($table, "change_oldvalue", "change_oldvalue", DbDatatypes::STR_TYPE_TEXT);
            Database::getInstance()->changeColumn($table, "change_newvalue", "change_newvalue", DbDatatypes::STR_TYPE_TEXT);

        } elseif (Config::getInstance()->getConfig("dbdriver") == "oci8") {

            //Need to do it this way since under oracle converting from varchar2 to clob is not possible
            Database::getInstance()->addColumn($table, "temp_change_oldvalue", DbDatatypes::STR_TYPE_TEXT);
            Database::getInstance()->_pQuery("UPDATE $table SET temp_change_oldvalue=change_oldvalue", []);
            Database::getInstance()->removeColumn($table, "change_oldvalue");
            Database::getInstance()->changeColumn($table, "temp_change_oldvalue", "change_oldvalue", DbDatatypes::STR_TYPE_TEXT);

            Database::getInstance()->addColumn($table, "temp_change_newvalue", DbDatatypes::STR_TYPE_TEXT);
            Database::getInstance()->_pQuery("UPDATE $table SET temp_change_newvalue=change_newvalue", []);
            Database::getInstance()->removeColumn($table, "change_newvalue");
            Database::getInstance()->changeColumn($table, "temp_change_newvalue", "change_newvalue", DbDatatypes::STR_TYPE_TEXT);
        }
    }
} else {
    echo "Found the following changelog tables\n";
    foreach ($tables as $table) {
        echo $table . "\n";
    }
}

echo "<form method='POST'><input type='submit' value='Migrate'></form>";
