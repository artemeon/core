<?php

// remove dbbrowser module
$module = \Kajona\System\System\SystemModule::getModuleByName("dbbrowser");
if ($module instanceof \Kajona\System\System\SystemModule) {
    $module->deleteObjectFromDatabase();
}

