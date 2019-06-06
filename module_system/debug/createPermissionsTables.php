<?php

use Kajona\System\System\DbDatatypes;

// copy from system installer

echo"Creating view-permissions table".PHP_EOL;
\Kajona\System\System\Database::getInstance()->createTable(
    "agp_permissions_view",
    [
        "view_id" => [DbDatatypes::STR_TYPE_CHAR20, false],
        "view_shortgroup" => [DbDatatypes::STR_TYPE_LONG, false],
    ],
    ["view_id", "view_shortgroup"],
    ["view_id", "view_shortgroup"]
);

\Kajona\System\System\Database::getInstance()->createTable(
    "agp_permissions_right2",
    [
        "right2_id" => [DbDatatypes::STR_TYPE_CHAR20, false],
        "right2_shortgroup" => [DbDatatypes::STR_TYPE_LONG, false],
    ],
    ["right2_id", "right2_shortgroup"],
    ["right2_id", "right2_shortgroup"]
);

echo"Creating group map".PHP_EOL;
$groupMap = [];
foreach (\Kajona\System\System\Database::getInstance()->getPArray("SELECT group_id, group_short_id FROM agp_user_group", []) as $row) {
    $groupMap[$row["group_short_id"]] = $row["group_id"];
}

echo"View permissions".PHP_EOL;
$systemRecords = \Kajona\System\System\Database::getInstance()->getPArray("SELECT system_id, right_view, right_right2 FROM agp_system", []);

$insertView = [];
$insertRight2 = [];
foreach ($systemRecords as $i => $row) {
    $groups = explode(",", trim($row["right_view"], ','));
    foreach ($groups as $shortid) {
        if (is_numeric($shortid) && array_key_exists($shortid, $groupMap)) {
            $insertView[] = [$row['system_id'], $shortid];
        }
    }

    $groups = explode(",", trim($row["right_right2"], ','));
    foreach ($groups as $shortid) {
        if (is_numeric($shortid) && array_key_exists($shortid, $groupMap)) {
            $insertRight2[] = [$row['system_id'], $shortid];
        }
    }

    if ($i % 500 == 0) {
        if (!empty($insertView)) {
            \Kajona\System\System\Database::getInstance()->multiInsert("agp_permissions_view", ["view_id", "view_shortgroup"], $insertView);
            $insertView = [];
        }

        if (!empty($insertRight2)) {
            \Kajona\System\System\Database::getInstance()->multiInsert("agp_permissions_right2", ["right2_id", "right2_shortgroup"], $insertRight2);
            $insertRight2 = [];
        }

        echo"Migrated {$i} records ".PHP_EOL;
        ob_flush();
        flush();
    }

}

if (!empty($insertView)) {
    \Kajona\System\System\Database::getInstance()->multiInsert("agp_permissions_view", ["view_id", "view_shortgroup"], $insertView);
}
if (!empty($insertRight2)) {
    \Kajona\System\System\Database::getInstance()->multiInsert("agp_permissions_right2", ["right2_id", "right2_shortgroup"], $insertRight2);
}
echo"Migrated {$i} records ".PHP_EOL;
