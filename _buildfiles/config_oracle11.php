<?php 

//database-settings
define("DB_HOST",				                "192.168.60.217");
define("DB_USER",                               "agp_build");
define("DB_PASS",                               "agp");
define("DB_DB",                                 "");
define("DB_DRIVER",                             "oci8");



ini_set("session.save_path", sys_get_temp_dir());
ini_set("session.use_cookies", "Off");
