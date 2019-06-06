<?php
/*"******************************************************************************************************
 *   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
 *   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
 *       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
 *-------------------------------------------------------------------------------------------------------*
 *   $Id$                                                  *
 ********************************************************************************************************/

/*
PLEASE READ:

There's no need to change anything in this file.
All values and settings may be overridden by placing them in the projects' config-file at

/project/module_system/system/config/config.php

A minimal config-file will be created during the installation of the system.

 */

//--database access -------------------------------------------------------------------------------------

$config['dbhost'] = "%%defaulthost%%"; //Server name
$config['dbusername'] = "%%defaultusername%%"; //Username
$config['dbpassword'] = "%%defaultpassword%%"; //Password
$config['dbname'] = "%%defaultdbname%%"; //Database name
$config['dbdriver'] = "%%defaultdriver%%"; //DB-Driver, one of: mysqli, postgres, sqlite3, oci8, sqlsrv
$config['dbprefix'] = "%%defaultprefix%%"; //table-prefix
$config['dbport'] = "%%defaultport%%"; //Database port, default: ""

$config['dbexport'] = "default"; //the way to import / export database dumps
//default: binaries provided by the driver
//internal: requires the module 'dbdump' to be present, uses a core-internal dump import / export routine

//--common settings -------------------------------------------------------------------------------------

$config['dirlang'] = "/lang"; //Path containing the language-files
$config['dirproject'] = "/project"; //Path containing the project-files
$config['dirfiles'] = "/files"; //Path containing the files-directory

$config["images_cachepath"] = "/files/cache/"; //Path used to store the cached and manipulated images

$config['adminlangs'] = "de,en,pt,ru,bg,sv"; //Available languages for the administration

$config['admintoolkit'] = "ToolkitAdmin"; //The admin-toolkit class to use. If you created your own implementation,
//e.g. by extending the Kajona-class, set the name of the class here.

$config['https_header'] = "HTTPS"; //Http-header used to validate if the current connection is encrypted by https.
//If your application server uses another value, set it here. If you want to validate multiple headers, pass an array,
//e.g. array("HTTPS", "HTTPS_FRONTEND")

$config['https_header_value'] = "on"; //If the presence of the header is not enough to validate the https status,
//set the required value to compare against here

$config['loginproviders'] = "kajona"; //A chain of login-providers, each implementing a single usersource. The providers
//are queried in the order of appearance. The list is comma-separated, no blanks allowed.

$config['password_validator'] = [
    'minlength' => [6], //Minimum length of the provided password
    'complexity' => [1, 0, 1, 0], //Password must contain the following char types (alpha-lower, alpha-upper, digit, special)
    'blacklist' => [], //Blacklist of specific words which are forbidden in the password
];

$config['header_cors_origin'] = "*"; //Specifies an origin which is allowed to request the xml.php endpoint. You can use either "*" to allow
//every origin or you can also specify a specific domain. By default this is disabled

//--caching ---------------------------------------------------------------------------------------------

$config['textcachetime'] = 1; //Number of seconds language-files are cached. Cached entries are shared between sessions. Reduce this amount during
//development (probably changing the lang-files a lot) and set it to a high value as soon as the website is in
//production. Attention: 0 = infinite!

$config['templatecachetime'] = 1; //Number of seconds templates are cached. Cached entries are shared between sessions. Reduce this amount during
//development (probably changing the templates a lot) and set it to a high value as soon as the website is in
//production. Attention: 0 = infinite!

$config['bootstrapcache_pharsums'] = true; //Enables the detection of phar-changes in order to redeploy the static contents. Should be disabled for non-phar installations only.

$config['bootstrapcache_pharcontent'] = true; //Enables the caching of phar-contents. Should be enabled by default.

$config['bootstrapcache_objects'] = true; //Caches the mapping of systemid to class-names. Should be enabled by default.

$config['bootstrapcache_foldercontent'] = true; //Caches the merge of the core- and project folders. Should be enabled on production systems but disabled on development systems.

$config['bootstrapcache_reflection'] = true; //Caches all static analysis by the reflection API, e.g. parsing of annotations. Should be enabled on production systems but disabled on development systems.

$config['bootstrapcache_lang'] = true; //Caches all locations of language files. Should be enabled on production systems but disabled on development systems.

$config['bootstrapcache_modules'] = true; //Caches the list of locally installed modules. Should be enabled on production systems but disabled on development systems.

$config['bootstrapcache_pharmodules'] = true; //Caches the list of modules deployed as phars. Should be enabled on production systems but disabled on development systems.

$config['bootstrapcache_classes'] = true; //Caches the locations of class-definitions collected by the classloader. Should be enabled on production systems but disabled on development systems.

$config['bootstrapcache_templates'] = true; //Caches the locations of templates fetched by the template-engine. Should be enabled on production systems but disabled on development systems.

$config['bootstrapcache_services'] = true; //Caches the class and file name of every service provider. Only these providers are called on startup

$config['bootstrapcache_moduleids'] = true; //Caches all module id constants

$config['bootstrapcache_requirejs'] = true; //Caches the requirejs config

//--debugging -------------------------------------------------------------------------------------------

$debug['time'] = false; //Calculates the time needed to create the requested page
$debug['dbnumber'] = false; //Counts the number of queries passed to the db / retrieved from the cache
$debug['memory'] = false; //Displays the memory used by Kajona to generate the current page

$debug['dblog'] = false; //Logs all queries sent to the db into a logfile. If set to true, the
//debuglogging has to be set to 3, since queries are leveled as information

$debug['debuglevel'] = 0; //Current level of debugging. There are several states:
// 0: fatal errors will be displayed
// 1: fatal and regular errors will be displayed
$debug['debuglogging'] = 2; //Configures the logging-engine:
// 0: Nothing is logged to file
// 1: Errors are logged
// 2: Errors and warning
// 3: Errors, warnings and information

//$debug['debuglogging_overwrite']['mail.log']          = 3;         //Overwrite the log level for some logfiles

//--services --------------------------------------------------------------------------------------------

//Example how to override a specific service implementation in the project config. This can be used to change the
//the behaviour of a service according to the needs of a customer. Because you use a different FQCN for the service
//you may also extend the standard service and change only specific methods. This helps to reuse existing code and
//simplifies updating project specific code
$config["service_provider"] = [
    /*
\AGP\Contracts\System\ServiceProvider::STR_DEPLOY_KEY_FINDER => function($c){
return new MyCustomImplementation();
}
 */
];
