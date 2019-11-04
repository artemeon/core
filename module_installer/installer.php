<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/


namespace Kajona\Installer;

use Kajona\Installer\System\SamplecontentInstallerHelper;
use Kajona\Packagemanager\System\PackagemanagerManager;
use Kajona\Packagemanager\System\PackagemanagerMetadata;
use Kajona\System\System\Carrier;
use Kajona\System\System\Classloader;
use Kajona\System\System\Cookie;
use Kajona\System\System\CoreEventdispatcher;
use Kajona\System\System\DbConnectionParams;
use Kajona\System\System\Exception;
use Kajona\System\System\HttpResponsetypes;
use Kajona\System\System\Lang;
use Kajona\System\System\RequestEntrypointEnum;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\ScriptletHelper;
use Kajona\System\System\ServiceProvider;
use Kajona\System\System\Session;
use Kajona\System\System\SystemEventidentifier;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\Template;

/**
 * Class representing a graphical installer.
 * Loads all sub-installers
 *
 * @author sidler@mulchprod.de
 * @package module_system
 */
class Installer
{

    private $STR_PROJECT_CONFIG_FILE = "";

    /**
     * @var PackagemanagerMetadata[]
     */
    private $arrMetadata;
    private $strOutput = "";
    private $strLogfile = "";
    private $strForwardLink = "";
    private $strBackwardLink = "";

    private $strVersion = "V 7.1";
    private $strMinPhpVersion = "7.2";

    /**
     * Instance of template-engine
     *
     * @var Template
     */
    private $objTemplates;

    /**
     * text-object
     *
     * @var Lang
     */
    private $objLang;

    /**
     * session
     *
     * @var Session
     */
    private $objSession;


    public function __construct()
    {
        //start up system
        $this->objTemplates = Carrier::getInstance()->getObjTemplate();
        $this->objLang = Carrier::getInstance()->getObjLang();
        //init session-support
        $this->objSession = Carrier::getInstance()->getObjSession();

        //set a different language?
        if (issetGet("language")) {
            if (in_array(getGet("language"), explode(",", Carrier::getInstance()->getObjConfig()->getConfig("adminlangs")))) {
                $this->objLang->setStrTextLanguage(getGet("language"));
                //and save to a cookie
                $objCookie = new Cookie();
                $objCookie->setCookie("adminlanguage", getGet("language"));

            }
        }
        else {
            //init correct text-file handling as in admins
            $this->objLang->setStrTextLanguage($this->objSession->getAdminLanguage(true, true));
        }

        $this->STR_PROJECT_CONFIG_FILE = _realpath_."project/module_system/system/config/config.php";
    }


    /**
     * Action block to control the behaviour
     */
    public function action()
    {
        ResponseObject::getInstance()->setObjEntrypoint(RequestEntrypointEnum::INSTALLER());

        //fetch posts
        if (isset($_POST['step']) && $_POST["step"] == "getNextAutoInstall") {
            ResponseObject::getInstance()->setStrResponseType(HttpResponsetypes::STR_TYPE_JSON);
            ResponseObject::getInstance()->setStrContent($this->getNextAutoInstall());
            return;
        }

        if (isset($_POST['step']) && $_POST["step"] == "triggerNextAutoInstall") {
            ResponseObject::getInstance()->setStrResponseType(HttpResponsetypes::STR_TYPE_JSON);
            ResponseObject::getInstance()->setStrContent($this->triggerNextAutoInstall());
            return;

        }

        if (isset($_POST['step']) && $_POST["step"] == "getNextAutoSamplecontent") {
            ResponseObject::getInstance()->setStrResponseType(HttpResponsetypes::STR_TYPE_JSON);
            ResponseObject::getInstance()->setStrContent($this->getNextAutoSameplecontent());
            return;
        }

        if (isset($_POST['step']) && $_POST["step"] == "triggerNextAutoSamplecontent") {
            ResponseObject::getInstance()->setStrResponseType(HttpResponsetypes::STR_TYPE_JSON);
            ResponseObject::getInstance()->setStrContent($this->triggerNextAutoSamplecontent());
            return;
        }

        //check if needed values are given
        if (!$this->checkDefaultValues()) {
            $this->configWizard();
        }

        //load a list of available installers
        $this->loadInstaller();

        //step one: needed php-values
        if (!isset($_GET["step"])) {
            $this->checkPHPSetting();
        }

        elseif ($_GET["step"] == "config" || !$this->checkDefaultValues()) {
            $this->configWizard();
        }

        elseif ($_GET["step"] == "loginData") {
            $this->adminLoginData();
        }

        elseif ($_GET["step"] == "autoInstall") {
            $this->autoInstall();
        }

        elseif ($_GET["step"] == "install") {
            $this->createModuleInstalls();
        }

        elseif ($_GET["step"] == "samplecontent") {
            $this->installSamplecontent();
        }

        elseif ($_GET["step"] == "finish") {
            $this->finish();
        }

        $strContent = $this->strOutput;
        if ($this->strOutput != "") {
            $strContent = $this->renderOutput();
        }
        ResponseObject::getInstance()->setStrContent($strContent);
    }

    /**
     * Makes a few checks on files and settings for a correct webserver
     */
    private function checkPHPSetting()
    {
        $strReturn = "";


        $arrFilesAndFolders = array(
            "/project/module_system/system/config",
            "/project/dbdumps",
            "/project/log",
            "/project/temp",
            "/files/cache",
            "/files/images",
            "/files/downloads",
            "/files/temp",
        );


        $arrModules = array(
            "curl",
            "exif",
            "fileinfo",
            "gd",
            "iconv",
            "json",
            "ldap",
            "libxml",
            "mbstring",
            "openssl",
            "zend opcache",
            "pcre",
            "phar",
            "reflection",
            "session",
            "simplexml",
            "sockets",
            "spl",
            "xml",
            "xmlreader",
            "xmlwriter",
            "xsl",
            "zip"
        );

        $arrChecksLanguages = [];
        //link to different languages
        $arrLangs = array("de", "en");
        foreach ($arrLangs as $strOneLang) {
            $arrChecksLanguages[] = "<a href=\""._webpath_."/installer.php?language=".$strOneLang."\">".Carrier::getInstance()->getObjLang()->getLang("lang_".$strOneLang, "user")."</a>";
        }

        if (version_compare(phpversion(), $this->strMinPhpVersion, "<")) {
            $minPhpVersion = "<span class=\"label label-danger label-as-badge\">&lt; ".$this->strMinPhpVersion."</span>";
        }
        else {
            $minPhpVersion = "<span class=\"label label-success label-as-badge\">".phpversion()."</span>";
        }

        $arrChecksFolder = [];
        foreach ($arrFilesAndFolders as $strOneFile) {
            if (is_writable(_realpath_.$strOneFile)) {
                $arrChecksFolder[$strOneFile] = true;
            }
            else {
                $arrChecksFolder[$strOneFile] = false;
            }
        }

        $arrChecksModules = [];
        foreach ($arrModules as $strOneModule) {
            $extensions = array_map(function(string $val) {
                return strtolower($val);
            }, get_loaded_extensions());
            if (in_array($strOneModule, $extensions)) {
                $arrChecksModules[$strOneModule] = true;
            }
            else {
                $arrChecksModules[$strOneModule] = false;
            }
        }

        $this->strForwardLink = $this->getForwardLink(_webpath_."/installer.php?step=config");
        $this->strBackwardLink = "";

        /** @var \Twig_Environment $twig */
        $twig = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_TEMPLATE_ENGINE);

        $strReturn = $twig->render("core/module_installer/templates/phpsettings.twig" , [
            "phpcheck_languages" => implode("|", $arrChecksLanguages),
            "fileChecksFolder" => $arrChecksFolder,
            "fileChecksModules" => $arrChecksModules,
            "minPhpVersion" => $minPhpVersion
        ]);

        $this->strOutput = $strReturn;
    }

    /**
     * Shows a form to write the values to the config files
     */
    private function configWizard()
    {
        $strReturn = "";

        if ($this->checkDefaultValues()) {
            ResponseObject::getInstance()->setStrRedirectUrl(_webpath_."/installer.php?step=loginData");
            return;
        }

        $bitCxCheck = true;

        if (isset($_POST["write"]) && $_POST["write"] == "true") {

            //try to validate the data passed
            $bitCxCheck = Carrier::getInstance()->getObjDB()->validateDbCxData($_POST["driver"], new DbConnectionParams($_POST["hostname"], $_POST["username"], $_POST["password"], $_POST["dbname"], $_POST["port"]));

            if ($bitCxCheck) {
                $strFileContent = "<?php\n";
                $strFileContent .= "/*\n Kajona V7 config-file.\n If you want to overwrite additional settings, copy them from /core/module_system/system/config/config.php into this file.\n*/";
                $strFileContent .= "\n\n\n";
                $strFileContent .= "  \$config['dbhost']               = '".$_POST["hostname"]."';                   //Server name \n";
                $strFileContent .= "  \$config['dbusername']           = '".$_POST["username"]."';                   //Username \n";
                $strFileContent .= "  \$config['dbpassword']           = '".$_POST["password"]."';                   //Password \n";
                $strFileContent .= "  \$config['dbname']               = '".$_POST["dbname"]."';                     //Database name \n";
                $strFileContent .= "  \$config['dbdriver']             = '".$_POST["driver"]."';                     //DB-Driver \n";
                $strFileContent .= "  \$config['dbport']               = '".$_POST["port"]."';                       //Database port \n";

                $strFileContent .= "\n";
                //and save to file
                file_put_contents($this->STR_PROJECT_CONFIG_FILE, $strFileContent);

                // flush cache after config was written
                Classloader::getInstance()->flushCache();

                // and reload
                ResponseObject::getInstance()->setStrRedirectUrl(_webpath_."/installer.php?step=loginData");
                $this->strOutput = "";
                return;
            }
        }

        //check for available modules
        $strMysqliInfo = "";
        $strSqlite3Info = "";
        $strSqlsrvInfo = "";
        $strPostgresInfo = "";
        $strOci8Info = "";
        if (!in_array("mysqli", get_loaded_extensions())) {
            $strMysqliInfo = "<div class=\"alert alert-danger\">".$this->getLang("installer_dbdriver_na")." mysqli</div>";
        }
        if (!in_array("pgsql", get_loaded_extensions())) {
            $strPostgresInfo = "<div class=\"alert alert-danger\">".$this->getLang("installer_dbdriver_na")." postgres</div>";
        }
        if (!in_array("sqlsrv", get_loaded_extensions())) {
            $strSqlsrvInfo = "<div class=\"alert alert-danger\">".$this->getLang("installer_dbdriver_na")." postgres</div>";
        }
        if (in_array("sqlite3", get_loaded_extensions())) {
            $strSqlite3Info = "<div class=\"alert alert-info\">".$this->getLang("installer_dbdriver_sqlite3")."</div>";
        }
        else {
            $strSqlite3Info = "<div class=\"alert alert-danger\">".$this->getLang("installer_dbdriver_na")." sqlite3</div>";
        }
        if (in_array("oci8", get_loaded_extensions())) {
            $strOci8Info = "<div class=\"alert alert-info\">".$this->getLang("installer_dbdriver_oci8")."</div>";
        }
        else {
            $strOci8Info = "<div class=\"alert alert-danger\">".$this->getLang("installer_dbdriver_na")." oci8</div>";
        }

        $strCxWarning = "";
        if (!$bitCxCheck) {
            $strCxWarning = "<div class=\"alert alert-danger\">".$this->getLang("installer_dbcx_error")."</div>";
        }

        /** @var \Twig_Environment $twig */
        $twig = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_TEMPLATE_ENGINE);
        $strReturn .= $twig->render("core/module_installer/templates/dbsettings.twig" , array(
            "mysqliInfo"   => $strMysqliInfo,
            "sqlite3Info"  => $strSqlite3Info,
            "postgresInfo" => $strPostgresInfo,
            "sqlsrvInfo"   => $strSqlsrvInfo,
            "oci8Info"     => $strOci8Info,
            "cxWarning"    => $strCxWarning,
            "postHostname" => isset($_POST["hostname"]) ? $_POST["hostname"] : "",
            "postUsername" => isset($_POST["username"]) ? $_POST["username"] : "",
            "postDbname"   => isset($_POST["dbname"]) ? $_POST["dbname"] : "",
            "postDbport"   => isset($_POST["port"]) ? $_POST["port"] : "",
            "postDbdriver" => isset($_POST["driver"]) ? $_POST["driver"] : ""
        ));

        $this->strBackwardLink = $this->getBackwardLink(_webpath_."/installer.php");

        $this->strOutput = $strReturn;
    }

    /**
     * Collects the data required to create a valid admin-login
     */
    private function adminLoginData()
    {
        $bitShowForm = true;
        $this->strOutput .= $this->getLang("installer_login_intro");

        $objManager = new PackagemanagerManager();
        if ($objManager->getPackage("agp_commons") !== null) {
            ResponseObject::getInstance()->setStrRedirectUrl(_webpath_."/installer.php?step=autoInstall");
        }

        if ($this->isInstalled()) {
            $bitShowForm = false;
            $this->strOutput .= "<div class=\"alert alert-success\">".$this->getLang("installer_login_installed")."</div>";
        }
        if (isset($_POST["write"]) && $_POST["write"] == "true") {
            $strUsername = $_POST["username"];
            $strPassword = $_POST["password"];
            $strEmail = $_POST["email"];
            //save to session
            if ($strUsername != "" && $strPassword != "" && checkEmailaddress($strEmail)) {
                $this->objSession->setSession("install_username", $strUsername);
                $this->objSession->setSession("install_password", $strPassword);
                $this->objSession->setSession("install_email", $strEmail);
                $this->strOutput = "";
                ResponseObject::getInstance()->setStrRedirectUrl(_webpath_."/installer.php?step=autoInstall");
                return;
            }
        }

        if ($bitShowForm) {
            /** @var \Twig_Environment $twig */
            $twig = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_TEMPLATE_ENGINE);
            $this->strOutput .= $twig->render("core/module_installer/templates/adminlogin.twig" , array());
        }

        $this->strBackwardLink = $this->getBackwardLink(_webpath_."/installer.php");
        if ($this->isInstalled()) {
            $this->strForwardLink = $this->getForwardLink(_webpath_."/installer.php?step=autoInstall");
        }
    }

    /**
     * The form to select the installer mode - everything automatically or a manual selection
     */
    private function autoInstall()
    {

        if ($this->isInstalled()) {
            ResponseObject::getInstance()->setStrRedirectUrl(_webpath_."/installer.php?step=install");
            return;
        }

        //fetch the relevant installers
        $objManager = new PackagemanagerManager();
        $arrPackageMetadata = $objManager->getAvailablePackages();

        $strPackagetable = "";
        $tmpArrayPackages = [];
        $tmpCounter = 0;
        foreach ($arrPackageMetadata as $objOnePackage) {
            $strSamplecontent = "";
            $strHint = "";

            $objScInstaller = SamplecontentInstallerHelper::getSamplecontentInstallerForPackage($objOnePackage);
            if ($objScInstaller !== null) {
                $strSamplecontent = '<i class="fa fa-hourglass-o"></i>';

                if (SystemModule::getModuleByName($objOnePackage->getStrTitle()) != null) {

                    if ($objScInstaller->isInstalled()) {
                        $strSamplecontent = '<i class="fa fa-check"></i>';
                    }
                }
            }

            $strModuleInstaller = '<i class="fa fa-check"></i>';
            if ($objOnePackage->getBitProvidesInstaller()) {
                $strModuleInstaller = '<i class="fa fa-hourglass-o"></i>';
                if (SystemModule::getModuleByName($objOnePackage->getStrTitle()) !== null) {
                    $strModuleInstaller = '<i class="fa fa-check"></i>';
                }
            }
            else {
                $strHint = $this->getLang("installer_package_hint_noinstaller");
            }

            // fill (temp) array with package-data
            $tmpArrayPackages[$tmpCounter]['packagename']           = $objOnePackage->getStrTitle();
            $tmpArrayPackages[$tmpCounter]['packagestatus']         = $objOnePackage->getStrTitle();
            $tmpArrayPackages[$tmpCounter]['packageuiname']         = $objOnePackage->getStrTitle();
            $tmpArrayPackages[$tmpCounter]['packageversion']        = $objOnePackage->getStrVersion();
            $tmpArrayPackages[$tmpCounter]['packagesamplecontent']  = $strSamplecontent;
            $tmpArrayPackages[$tmpCounter]['packageinstaller']      = $strModuleInstaller;
            $tmpArrayPackages[$tmpCounter]['packagehint']           = $strHint;
            $tmpCounter++;
        }

        /** @var \Twig_Environment $twig */
        $twig = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_TEMPLATE_ENGINE);
        $this->strOutput .= $twig->render("core/module_installer/templates/installpackages.twig" , array(
            "packages"              => $tmpArrayPackages,
            "link_autoinstall"      => _webpath_."/installer.php?step=finish&autoInstall=true",
            "link_manualinstall"    => _webpath_."/installer.php?step=install"
        ));
        unset($tmpArrayPackages);

        $this->strBackwardLink = $this->getBackwardLink(_webpath_."/installer.php?step=loginData");
    }

    /**
     * Loads all installers available to this->arrInstaller
     */
    private function loadInstaller()
    {

        $objManager = new PackagemanagerManager();
        $arrModules = $objManager->getAvailablePackages();

        $this->arrMetadata = array();
        foreach ($arrModules as $objOneModule) {
            if ($objOneModule->getBitProvidesInstaller()) {
                $this->arrMetadata[] = $objOneModule;
            }
        }

        $this->arrMetadata = $objManager->sortPackages($this->arrMetadata, true);

    }

    /**
     * Loads all installers and requests a install / update link, if available
     */
    private function createModuleInstalls()
    {
        $strReturn = "";
        $strInstallLog = "";

        $objManager = new PackagemanagerManager();

        //module-installs to loop?
        if (isset($_POST["moduleInstallBox"]) && is_array($_POST["moduleInstallBox"])) {
            $arrModulesToInstall = $_POST["moduleInstallBox"];
            foreach ($arrModulesToInstall as $strOneModule => $strValue) {

                //search the matching modules
                foreach ($this->arrMetadata as $objOneMetadata) {
                    if ($strOneModule == "installer_".$objOneMetadata->getStrTitle()) {
                        $objHandler = $objManager->getPackageManagerForPath($objOneMetadata->getStrPath());
                        $strInstallLog .= $objHandler->installOrUpdate();
                    }
                }

            }

        }

        Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_DBQUERIES | Carrier::INT_CACHE_TYPE_ORMCACHE | Carrier::INT_CACHE_TYPE_OBJECTFACTORY | Carrier::INT_CACHE_TYPE_MODULES);
        $this->loadInstaller();

        $this->strLogfile = $strInstallLog;
        $strReturn .= $this->getLang("installer_modules_found");

        $tmpCounter = 0;
        $tmpArray = [];
        //Loading each installer
        foreach ($this->arrMetadata as $objOneMetadata) {

            //skip samplecontent
            if ($objOneMetadata->getStrTitle() == "samplecontent") {
                continue;
            }

            $objHandler = $objManager->getPackageManagerForPath($objOneMetadata->getStrPath());

            $arrTemplate = array();
            $arrTemplate["module_name"] = $objHandler->getObjMetadata()->getStrTitle();
            $arrTemplate["module_nameShort"] = $objHandler->getObjMetadata()->getStrTitle();
            $arrTemplate["module_version"] = $objHandler->getObjMetadata()->getStrVersion();

            //generate the hint
            $arrTemplate["module_hint"] = "";

            if ($objHandler->getVersionInstalled() !== null) {
                $arrTemplate["module_hint"] .= $this->getLang("installer_versioninstalled").$objHandler->getVersionInstalled()."<br />";
            }

            //check missing modules
            $arrModules = $objHandler->getObjMetadata()->getArrRequiredModules();
            foreach ($arrModules as $strOneModule => $strVersion) {
                if (trim($strOneModule) != "" && SystemModule::getModuleByName(trim($strOneModule)) === null) {

                    //check if a corresponding module is available
                    $objPackagemanager = new PackagemanagerManager();
                    $objPackage = $objPackagemanager->getPackage($strOneModule);

                    if ($objPackage === null || $objPackage->getBitProvidesInstaller() || version_compare($strVersion, $objPackage->getStrVersion(), ">")) {
                        $arrTemplate["module_hint"] .= $this->getLang("installer_systemversion_needed").$strOneModule." >= ".$strVersion."<br />";
                    }
                }

                else if (version_compare($strVersion, SystemModule::getModuleByName(trim($strOneModule))->getStrVersion(), ">")) {
                    $arrTemplate["module_hint"] .= $this->getLang("installer_systemversion_needed").$strOneModule." >= ".$strVersion."<br />";
                }
            }

            if ($objHandler->isInstallable()) {
                $tmpArray[$tmpCounter]['section'] = 1;
            }
            else {
                $tmpArray[$tmpCounter]['section'] = 2;
            }
            $tmpArray[$tmpCounter]['module_name'] = $arrTemplate["module_name"];
            $tmpArray[$tmpCounter]['module_nameShort'] = $arrTemplate["module_nameShort"];
            $tmpArray[$tmpCounter]['module_version'] = $arrTemplate["module_version"];
            $tmpArray[$tmpCounter]['module_hint'] = $arrTemplate["module_hint"];

            $tmpCounter++;
        }

        //wrap in form
        /** @var \Twig_Environment $twig */
        $twig = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_TEMPLATE_ENGINE);
        $strReturn .= $twig->render("core/module_installer/templates/updatepackages.twig" , array(
            "section_array"     => $tmpArray
        ));

        $this->strOutput .= $strReturn;
        if ($this->isInstalled()) {
            $this->strBackwardLink = $this->getBackwardLink(_webpath_."/installer.php?step=loginData");
        }
        else {
            $this->strBackwardLink = $this->getBackwardLink(_webpath_."/installer.php?step=modeSelect");
        }
        $this->strForwardLink = $this->getForwardLink(_webpath_."/installer.php?step=finish");
    }


    /**
     * The last page of the installer, showing a few infos and links how to go on
     */
    private function finish()
    {
        $strReturn = "";


        $this->objSession->sessionUnset("install_username");
        $this->objSession->sessionUnset("install_password");

        $strReturn .= $this->getLang("installer_finish_intro");
        $strReturn .= $this->getLang("installer_finish_hints");

        $this->strOutput = $strReturn;
        $this->strBackwardLink = $this->getBackwardLink(_webpath_."/installer.php?step=autoInstall");
    }


    private function getNextAutoSameplecontent()
    {

        foreach (SamplecontentInstallerHelper::getSamplecontentInstallers() as $objOneInstaller) {

            $strModule = $objOneInstaller->getCorrespondingModule();
            $objModule = SystemModule::getModuleByName($strModule);

            if ($objModule == null) {
                // module not installed
                continue;
            }

            if (!$objOneInstaller->isInstalled()) {

                $objManager = new PackagemanagerManager();
                foreach ($objManager->getAvailablePackages() as $objOnePackage) {

                    $object = SamplecontentInstallerHelper::getSamplecontentInstallerForPackage($objOnePackage);
                    if ($object != null && $objOneInstaller != null && get_class($objOneInstaller) == get_class($object)) {
                        return json_encode(array("module" => $objOnePackage->getStrTitle()));
                    }
                }

            }
        }

        return json_encode("");

    }

    private function triggerNextAutoSamplecontent()
    {
        $objManager = new PackagemanagerManager();
        $arrPackageMetadata = $objManager->getAvailablePackages();
        foreach ($arrPackageMetadata as $objOneMetadata) {
            if ($objOneMetadata->getStrTitle() == $_POST["module"]) {

                $objSamplecontent = SamplecontentInstallerHelper::getSamplecontentInstallerForPackage($objOneMetadata);

                if ($objSamplecontent != null && !$objSamplecontent->isInstalled()) {
                    $strReturn = SamplecontentInstallerHelper::install($objSamplecontent);
                    return json_encode(array("module" => $_POST["module"], "status" => "success", "log" => $strReturn));
                }
            }
        }

        return json_encode(array("module" => $_POST["module"], "status" => "error"));

    }


    private function getNextAutoInstall()
    {

        $objManager = new PackagemanagerManager();
        $arrPackagesToInstall = $objManager->getAvailablePackages();

        foreach ($arrPackagesToInstall as $intKey => $objOneMetadata) {

            $objHandler = $objManager->getPackageManagerForPath($objOneMetadata->getStrPath());

            if (!$objOneMetadata->getBitProvidesInstaller() || !$objHandler->isInstallable()) {
                unset($arrPackagesToInstall[$intKey]);
                continue;
            }

            return json_encode($objOneMetadata->getStrTitle());
        }


        return json_encode("");
    }

    private function triggerNextAutoInstall()
    {

        $objManager = new PackagemanagerManager();
        $arrPackageMetadata = $objManager->getAvailablePackages();

        foreach ($arrPackageMetadata as $objOneMetadata) {
            if ($objOneMetadata->getStrTitle() == $_POST["module"]) {
                $objHandler = $objManager->getPackageManagerForPath($objOneMetadata->getStrPath());

                if ($objHandler->isInstallable()) {
                    $strReturn = $objHandler->installOrUpdate();
                    return json_encode(array("module" => $_POST["module"], "status" => "success", "log" => $strReturn));
                }
            }
        }

        return json_encode(array("module" => $_POST["module"], "status" => "error"));
    }

    /**
     * Generates the surrounding layout and embeds the installer-output
     *
     * @return string
     */
    private function renderOutput()
    {

        CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_ENDPROCESSING, array());

        //build the progress-entries
        $strCurrentCommand = (isset($_GET["step"]) ? $_GET["step"] : "");
        if ($strCurrentCommand == "") {
            $strCurrentCommand = "phpsettings";
        }

        $arrProgressEntries = array(
            "phpsettings"   => $this->getLang("installer_step_phpsettings"),
            "config"        => $this->getLang("installer_step_dbsettings"),
            "loginData"     => $this->getLang("installer_step_adminsettings"),
            "autoInstall"    => $this->getLang("installer_step_autoinstall"),
            "install"       => $this->getLang("installer_step_modules"),
            "finish"        => $this->getLang("installer_step_finish"),
        );

        if(!$this->isInstalled() && (!isset($_GET['step']) || $_GET["step"] != "finish")) {
            unset($arrProgressEntries["install"]);
        }

        $strProgress = "";



        $parts = [];
        foreach ([
                     'KAJONA_DEBUG' => 1,
                     'KAJONA_WEBPATH' => _webpath_,
                     'KAJONA_BROWSER_CACHEBUSTER' => SystemSetting::getConfigValue("_system_browser_cachebuster_"),
                     'KAJONA_LANGUAGE' => Carrier::getInstance()->getObjSession()->getAdminLanguage(),
                     'KAJONA_PHARMAP' => array_values(Classloader::getInstance()->getArrPharModules()),
                     'KAJONA_ACCESS_TOKEN' => null,
                 ] as $name => $value) {
            $parts[] = $name . ' = ' . json_encode($value) . ';';
        }

        $head = "<script type=\"text/javascript\">" . implode("\n", $parts) . "</script>";

        /** @var \Twig_Environment $twig */
        $twig = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::STR_TEMPLATE_ENGINE);
        $strReturn = $twig->render("core/module_installer/templates/base.twig" , array(
            'installer_sections'    => $arrProgressEntries,
            'currentCommand'        => $strCurrentCommand,
            'installer_progress'    => $strProgress,
            'installer_output'      => $this->strOutput,
            'installer_logfile'     => $this->strLogfile,
            'installer_backward'    => $this->strBackwardLink,
            'installer_forward'     => $this->strForwardLink,
            'installer_version'     => $this->strVersion,
            'log_content'           => $this->strLogfile,
            'systemlog'             => $this->getLang("installer_systemlog"),
            'logfile'               => $this->strLogfile,
            'head'                  => $head
        ));

        $strReturn = $this->callScriptlets($strReturn);
        return $strReturn;
    }


    /**
     * Calls the scriptlets in order to process additional tags and in order to enrich the content.
     *
     * @param $strContent
     *
     * @return string
     */
    private function callScriptlets($strContent)
    {
        $objHelper = new ScriptletHelper();
        return $objHelper->processString($strContent);
    }


    /**
     * Checks, if the config-file was filled with correct values
     *
     * @return bool
     */
    private function checkDefaultValues()
    {
        return is_file($this->STR_PROJECT_CONFIG_FILE);
    }

    /**
     * Creates a forward-link
     *
     * @param string $strHref
     *
     * @return string
     */
    private function getForwardLink($strHref)
    {
        return $link = [
            'href' => $strHref,
            'text' => $this->getLang("installer_next")
        ];
    }

    /**
     * Creates backward-link
     *
     * @param string $strHref
     *
     * @return string
     */
    private function getBackwardLink($strHref)
    {
        return $link = [
            'href' => $strHref,
            'text' => $this->getLang("installer_prev")
        ];
    }

    /**
     * Loads a text
     *
     * @param string $strKey
     * @param array $arrParameters
     *
     * @return string
     */
    private function getLang($strKey, $arrParameters = array())
    {
        return $this->objLang->getLang($strKey, "installer", $arrParameters);
    }

    private function isInstalled()
    {
        try {
            $objUser = SystemModule::getModuleByName("user");
            if ($objUser != null) {
                return true;
            }
        }
        catch (Exception $objE) {
        }

        return false;
    }
}

//Creating the Installer-Object
$objInstaller = new Installer();
$objInstaller->action();
CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_ENDPROCESSING, array());
ResponseObject::getInstance()->sendHeaders();
ResponseObject::getInstance()->sendContent();
CoreEventdispatcher::getInstance()->notifyGenericListeners(SystemEventidentifier::EVENT_SYSTEM_REQUEST_AFTERCONTENTSEND, array(RequestEntrypointEnum::INSTALLER()));

