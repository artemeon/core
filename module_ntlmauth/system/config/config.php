<?php

$config["forceRedirect"] = false;
$config["userParam"] = $_SERVER['REMOTE_USER'] ?? '';
$config["authUrl"] = _webpath_.'/authenticate/index.php';

