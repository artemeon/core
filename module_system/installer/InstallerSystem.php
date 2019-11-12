<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*	$Id$                                        *
********************************************************************************************************/

namespace Kajona\System\Installer;

use Kajona\Packagemanager\System\PackagemanagerManager;
use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\Database;
use Kajona\System\System\Date;
use Kajona\System\System\Db\Schema\TableIndex;
use Kajona\System\System\DbDatatypes;
use Kajona\System\System\IdGenerator;
use Kajona\System\System\InstallerBase;
use Kajona\System\System\InstallerInterface;
use Kajona\System\System\LanguagesLanguage;
use Kajona\System\System\Lifecycle\ServiceLifeCycleFactory;
use Kajona\System\System\MessagingAlert;
use Kajona\System\System\MessagingConfig;
use Kajona\System\System\MessagingMessage;
use Kajona\System\System\MessagingQueue;
use Kajona\System\System\OrmSchemamanager;
use Kajona\System\System\Resourceloader;
use Kajona\System\System\Session;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemAspect;
use Kajona\System\System\SystemChangelog;
use Kajona\System\System\SystemCommon;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemPwchangehistory;
use Kajona\System\System\SystemPwHistory;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;
use Kajona\Workflows\System\WorkflowsHandler;
use Kajona\Workflows\System\WorkflowsWorkflow;

/**
 * Installer for the system-module
 *
 * @package module_system
 * @moduleId _system_modul_id_
 */
class InstallerSystem extends InstallerBase implements InstallerInterface {

    private $strContentLanguage;

    /**
     * @var Session
     * @inject system_session
     */
    private $objSession;

    public function __construct() {
        parent::__construct();

        //set the correct language
        $this->strContentLanguage = "de";
    }

    public function install() {
        $strReturn = "";
        $objManager = new OrmSchemamanager();

        // System table ---------------------------------------------------------------------------------
        $strReturn .= "Installing table system...\n";

        $arrFields = array();
        $arrFields["system_id"] = array("char20", false);
        $arrFields["system_prev_id"] = array("char20", false);
        $arrFields["system_module_nr"] = array("int", false);
        $arrFields["system_sort"] = array("int", true);
        $arrFields["system_owner"] = array("char20", true);
        $arrFields["system_create_date"] = array("long", true);
        $arrFields["system_lm_user"] = array("char20", true);
        $arrFields["system_lm_time"] = array("int", true);
        $arrFields["system_lock_id"] = array("char20", true);
        $arrFields["system_lock_time"] = array("int", true);
        $arrFields["system_status"] = array("int", true);
        $arrFields["system_class"] = array("char254", true);
        $arrFields["system_deleted"] = array("int", true);

        $arrFields["right_inherit"] = array("int", true);
        $arrFields["right_view"] = array("text", true);
        $arrFields["right_edit"] = array("text", true);
        $arrFields["right_delete"] = array("text", true);
        $arrFields["right_right"] = array("text", true);
        $arrFields["right_right1"] = array("text", true);
        $arrFields["right_right2"] = array("text", true);
        $arrFields["right_right3"] = array("text", true);
        $arrFields["right_right4"] = array("text", true);
        $arrFields["right_right5"] = array("text", true);
        $arrFields["right_changelog"] = array("text", true);

        if(!$this->objDB->createTable("agp_system", $arrFields, array("system_id"), array("system_prev_id", "system_module_nr", "system_sort", "system_owner", "system_create_date", "system_status", "system_lm_time", "system_lock_time", "system_class")))
            $strReturn .= "An error occurred! ...\n";


        $this->objDB->createTable(
            "agp_permissions_view",
            [
                "view_id" => [DbDatatypes::STR_TYPE_CHAR20, false],
                "view_shortgroup" => [DbDatatypes::STR_TYPE_LONG, false],
            ],
            ["view_id", "view_shortgroup"],
            ["view_id", "view_shortgroup"]
        );

        $this->objDB->createTable(
            "agp_permissions_right2",
            [
                "right2_id" => [DbDatatypes::STR_TYPE_CHAR20, false],
                "right2_shortgroup" => [DbDatatypes::STR_TYPE_LONG, false],
            ],
            ["right2_id", "right2_shortgroup"],
            ["right2_id", "right2_shortgroup"]
        );

        // Modul table ----------------------------------------------------------------------------------
        $strReturn .= "Installing table system_module...\n";
        $objManager->createTable(SystemModule::class);


        // Date table -----------------------------------------------------------------------------------
        $strReturn .= "Installing table system_date...\n";

        $arrFields = array();
        $arrFields["system_date_id"] = array("char20", false);
        $arrFields["system_date_start"] = array("long", true);
        $arrFields["system_date_end"] = array("long", true);
        $arrFields["system_date_special"] = array("long", true);

        if(!$this->objDB->createTable("agp_system_date", $arrFields, array("system_date_id"), array("system_date_start", "system_date_end", "system_date_special")))
            $strReturn .= "An error occurred! ...\n";

        // Config table ---------------------------------------------------------------------------------
        $strReturn .= "Installing table system_config...\n";
        $objManager->createTable(SystemSetting::class);

        // User table -----------------------------------------------------------------------------------
        $strReturn .= "Installing table user...\n";
        $objManager->createTable(UserUser::class);

        // User table kajona subsystem  -----------------------------------------------------------------
        $strReturn .= "Installing table user_kajona...\n";

        $arrFields = array();
        $arrFields["user_id"] = array("char20", false);
        $arrFields["user_pass"] = array("char254", true);
        $arrFields["user_salt"] = array("char20", true);
        $arrFields["user_email"] = array("char254", true);
        $arrFields["user_forename"] = array("char254", true);
        $arrFields["user_name"] = array("char254", true);
        $arrFields["user_street"] = array("char254", true);
        $arrFields["user_postal"] = array("char254", true);
        $arrFields["user_city"] = array("char254", true);
        $arrFields["user_tel"] = array("char254", true);
        $arrFields["user_mobile"] = array("char254", true);
        $arrFields["user_date"] = array("long", true);
        $arrFields["user_specialconfig"] = array("text", true);

        if(!$this->objDB->createTable("agp_user_kajona", $arrFields, array("user_id")))
            $strReturn .= "An error occurred! ...\n";

        // User group table -----------------------------------------------------------------------------
        $strReturn .= "Installing table user_group...\n";
        $objManager->createTable(UserGroup::class);

        $strReturn .= "Installing table user_group_kajona...\n";

        $arrFields = array();
        $arrFields["group_id"] = array("char20", false);
        $arrFields["group_desc"] = array("char254", true);


        if(!$this->objDB->createTable("agp_user_group_kajona", $arrFields, array("group_id")))
            $strReturn .= "An error occurred! ...\n";


        // User group_members table ---------------------------------------------------------------------
        $strReturn .= "Installing table user_kajona_members...\n";

        $arrFields = array();
        $arrFields["group_member_group_kajona_id"] = array("char20", false);
        $arrFields["group_member_user_kajona_id"] = array("char20", false);

        if(!$this->objDB->createTable("agp_user_kajona_members", $arrFields, array("group_member_group_kajona_id", "group_member_user_kajona_id")))
            $strReturn .= "An error occurred! ...\n";


        // User log table -------------------------------------------------------------------------------
        $strReturn .= "Installing table user_log...\n";

        $arrFields = array();
        $arrFields["user_log_id"] = array("char20", false);
        $arrFields["user_log_userid"] = array("char254", true);
        $arrFields["user_log_date"] = array("long", true);
        $arrFields["user_log_status"] = array("int", true);
        $arrFields["user_log_ip"] = array("char254", true);
        $arrFields["user_log_sessid"]  = array("char20", true);
        $arrFields["user_log_enddate"] = array("long", true);

        if(!$this->objDB->createTable("agp_user_log", $arrFields, array("user_log_id"), array("user_log_sessid")))
            $strReturn .= "An error occurred! ...\n";

        // Sessionmgtm ----------------------------------------------------------------------------------
        $strReturn .= "Installing table session...\n";

        $arrFields = array();
        $arrFields["session_id"] = array("char20", false);
        $arrFields["session_phpid"] = array("char254", true);
        $arrFields["session_releasetime"] = array("int", true);
        $arrFields["session_loginstatus"] = array("char254", true);
        $arrFields["session_loginprovider"] = array("char20", true);
        $arrFields["session_lasturl"] = array("text", true);
        $arrFields["session_userid"] = array("char20", true);
        $arrFields["session_resetuser"] = array("int", true);

        if(!$this->objDB->createTable("agp_session", $arrFields, array("session_id"), array("session_phpid", "session_releasetime")))
            $strReturn .= "An error occurred! ...\n";

        //languages -------------------------------------------------------------------------------------
        $strReturn .= "Installing table languages...\n";
        $objManager->createTable(LanguagesLanguage::class);

        //aspects --------------------------------------------------------------------------------------
        $strReturn .= "Installing table aspects...\n";
        $objManager->createTable(SystemAspect::class);

        //changelog -------------------------------------------------------------------------------------
        $strReturn .= "Installing table changelog...\n";
        $this->installChangeTables();

        //messages
        $strReturn .= "Installing table messages...\n";
        $objManager->createTable(MessagingMessage::class);
        $objManager->createTable(MessagingConfig::class);
        $objManager->createTable(MessagingAlert::class);
        $objManager->createTable(MessagingQueue::class);

        // password change history
        $strReturn .= "Installing password reset history...\n";
        $objManager->createTable(SystemPwchangehistory::class);

        // idgenerator
        $strReturn .= "Installing idgenerator table...\n";
        $objManager->createTable(IdGenerator::class);

        // password history
        $strReturn .= "Installing password history...\n";
        $objManager->createTable(SystemPwHistory::class);

        //Now we have to register module by module

        //The Systemkernel
        $this->registerModule("system", _system_modul_id_, "", "SystemAdmin.php", $this->objMetadata->getStrVersion());
        //The Rightsmodule
        $this->registerModule("right", _system_modul_id_, "", "RightAdmin.php", $this->objMetadata->getStrVersion(), false);
        //The Usermodule
        $this->registerModule("user", _user_modul_id_, "", "UserAdmin.php", $this->objMetadata->getStrVersion());
        //languages
        $this->registerModule("languages", _languages_modul_id_, "", "LanguagesAdmin.php", $this->objMetadata->getStrVersion());
        //messaging
        $this->registerModule("messaging", _messaging_module_id_, "MessagingPortal.php", "MessagingAdmin.php", $this->objMetadata->getStrVersion());


        //Registering a few constants
        $strReturn .= "Registering system-constants...\n";

        //And the default skin
        $this->registerConstant("_admin_skin_default_", "kajona_v4", SystemSetting::$int_TYPE_STRING, _user_modul_id_);

        //and a few system-settings
        $this->registerConstant("_system_portal_disable_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);
        $this->registerConstant("_system_portal_disablepage_", "", SystemSetting::$int_TYPE_PAGE, _system_modul_id_);

        //New in 3.0: Number of db-dumps to hold
        $this->registerConstant("_system_dbdump_amount_", 15, SystemSetting::$int_TYPE_INT, _system_modul_id_);
        //new in 3.0: mod-rewrite on / off
        $this->registerConstant("_system_mod_rewrite_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);
        $this->registerConstant("_system_mod_rewrite_admin_only_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);
        
        
        //New Constant: Max time to lock records
        $this->registerConstant("_system_lock_maxtime_", 7200, SystemSetting::$int_TYPE_INT, _system_modul_id_);
        //Email to send error-reports
        $this->registerConstant("_system_admin_email_", $this->objSession->getSession("install_email"), SystemSetting::$int_TYPE_STRING, _system_modul_id_);

        $this->registerConstant("_system_email_defaultsender_", $this->objSession->getSession("install_email"), SystemSetting::$int_TYPE_STRING, _system_modul_id_);
        $this->registerConstant("_system_email_forcesender_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        //3.0.2: user are allowed to change their settings?
        $this->registerConstant("_user_selfedit_", "true", SystemSetting::$int_TYPE_BOOL, _user_modul_id_);

        //3.1: nr of rows in admin
        $this->registerConstant("_admin_nr_of_rows_", 15, SystemSetting::$int_TYPE_INT, _system_modul_id_);
        $this->registerConstant("_admin_only_https_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);
        $this->registerConstant("_cookies_only_https_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        //3.1: remoteloader max cachtime --> default 60 min
        $this->registerConstant("_remoteloader_max_cachetime_", 60 * 60, SystemSetting::$int_TYPE_INT, _system_modul_id_);

        //3.2: max session duration
        $this->registerConstant("_system_release_time_", 3600, SystemSetting::$int_TYPE_INT, _system_modul_id_);
        //3.4: cache buster to be able to flush the browsers cache (JS and CSS files)
        $this->registerConstant("_system_browser_cachebuster_", 0, SystemSetting::$int_TYPE_INT, _system_modul_id_);
        //3.4: Adding constant _system_graph_type_ indicating the chart-engine to use
        $this->registerConstant("_system_graph_type_", "chartjs", SystemSetting::$int_TYPE_STRING, _system_modul_id_);
        //3.4: Enabling or disabling the internal changehistory
        $this->registerConstant("_system_changehistory_enabled_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        $this->registerConstant("_system_timezone_", "", SystemSetting::$int_TYPE_STRING, _system_modul_id_);
        $this->registerConstant("_system_session_ipfixation_", "true", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        $this->registerConstant("_system_permission_assignment_threshold_", 500, SystemSetting::$int_TYPE_INT, _system_modul_id_);


        //Creating the admin GROUP
        $objAdminGroup = new UserGroup();
        $objAdminGroup->setStrName("Admins");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAdminGroup))->update($objAdminGroup);
        $strReturn .= "Registered Group Admins...\n";

        //Systemid of admin group
        $strAdminID = $objAdminGroup->getSystemid();
        $intAdminShortid = $objAdminGroup->getIntShortId();
        $this->registerConstant("_admins_group_id_", $strAdminID, SystemSetting::$int_TYPE_STRING, _user_modul_id_);

        //BUT: We have to modify the right-record of the root node, too
        $strGroupsAll = ",".$intAdminShortid.",";
        $strGroupsAdmin = ",".$intAdminShortid.",";

        //Create an root-record for the tree
        //So, lets generate the record
        $strQuery = "INSERT INTO agp_system
                     ( system_id, system_prev_id, system_module_nr, system_create_date, system_lm_time, system_status, system_sort, system_class,
                        right_inherit, right_view, right_edit, right_delete, right_right, right_right1, right_right2, right_right3, right_right4, right_right5, right_changelog
                     ) VALUES
                     (?, ?, ?, ?, ?, ?, ?, ?,
                     ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        //Send the query to the db
        $this->objDB->_pQuery(
            $strQuery,
            array(0, 0, _system_modul_id_, Date::getCurrentTimestamp(), time(), 1, 1, SystemCommon::class,
                0, $strGroupsAll, $strGroupsAdmin, $strGroupsAdmin, $strGroupsAdmin, $strGroupsAdmin, $strGroupsAdmin, $strGroupsAdmin, $strGroupsAdmin, $strGroupsAdmin, $strGroupsAdmin)
        );

        $this->objDB->flushQueryCache();

        $strReturn .= "Modified root-rights....\n";
        Carrier::getInstance()->getObjRights()->rebuildRightsStructure();
        $strReturn .= "Rebuilt rights structures...\n";

        //Creating an admin-user
        $strUsername = "admin";
        $strPassword = "kajona";
        $strEmail = "";
        //Login-Data given from installer?
        if($this->objSession->getSession("install_username") !== false && $this->objSession->getSession("install_username") != "" &&
            $this->objSession->getSession("install_password") !== false && $this->objSession->getSession("install_password") != ""
        ) {
            $strUsername = ($this->objSession->getSession("install_username"));
            $strPassword = ($this->objSession->getSession("install_password"));
            $strEmail = ($this->objSession->getSession("install_email"));
        }

        //create a default language
        $strReturn .= "Creating new default-language\n";
        $objLanguage = new LanguagesLanguage();
        $objLanguage->setStrName("de");

        $objLanguage->setBitDefault(true);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objLanguage))->update($objLanguage);
        $strReturn .= "ID of new language: ".$objLanguage->getSystemid()."\n";

        //the admin-language
        $strAdminLanguage = $this->strContentLanguage;

        //creating a new default-aspect
        $strReturn .= "Registering new default aspects...\n";
        $objAspect = new SystemAspect();
        $objAspect->setStrName("content");
        $objAspect->setBitDefault(true);
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);
        SystemAspect::setCurrentAspectId($objAspect->getSystemid());

        $objAspect = new SystemAspect();
        $objAspect->setStrName("management");
        ServiceLifeCycleFactory::getLifeCycle(get_class($objAspect))->update($objAspect);

        $objManager = new PackagemanagerManager();
        if ($objManager->getPackage("agp_commons") === null) {
            $objUser = new UserUser();
            $objUser->setStrUsername($strUsername);
            $objUser->setIntAdmin(1);
            $objUser->setStrAdminlanguage($strAdminLanguage);
            ServiceLifeCycleFactory::getLifeCycle(get_class($objUser))->update($objUser);
            $objUser->getObjSourceUser()->setStrPass($strPassword);
            $objUser->getObjSourceUser()->setStrEmail($strEmail);
            ServiceLifeCycleFactory::getLifeCycle(get_class($objUser->getObjSourceUser()))->update($objUser->getObjSourceUser());
            $strReturn .= "Created User Admin: <strong>Username: ".$strUsername.", Password: ***********</strong> ...\n";

            //The Admin should belong to the admin-Group
            $objAdminGroup->getObjSourceGroup()->addMember($objUser->getObjSourceUser());
            $strReturn .= "Registered Admin in Admin-Group...\n";
        }



        $strReturn .= "Assigning modules to default aspects...\n";
        $objModule = SystemModule::getModuleByName("system");
        $objModule->setStrAspect(SystemAspect::getAspectByName("management")->getSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objModule))->update($objModule);

        $objModule = SystemModule::getModuleByName("user");
        $objModule->setStrAspect(SystemAspect::getAspectByName("management")->getSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objModule))->update($objModule);

        $objModule = SystemModule::getModuleByName("languages");
        $objModule->setStrAspect(SystemAspect::getAspectByName("management")->getSystemid());
        ServiceLifeCycleFactory::getLifeCycle(get_class($objModule))->update($objModule);


        $strReturn .= "Trying to copy the *.root files to top-level...\n";
        $arrFiles = array(
            "index.php", "image.php", "xml.php", ".htaccess"
        );
        foreach($arrFiles as $strOneFile) {
            if(!file_exists(_realpath_.$strOneFile) && is_file(Resourceloader::getInstance()->getAbsolutePathForModule("module_system")."/".$strOneFile.".root")) {
                if(!copy(Resourceloader::getInstance()->getAbsolutePathForModule("module_system")."/".$strOneFile.".root", _realpath_.$strOneFile))
                    $strReturn .= "<b>Copying ".$strOneFile.".root to top level failed!!!</b>";
            }
        }



        $strReturn .= "Setting messaging to pos 1 in navigation.../n";
        $objModule = SystemModule::getModuleByName("messaging");
        $objModule->setAbsolutePosition(1);

        return $strReturn;
    }


    public function installChangeTables() {
        $strReturn = "";

        $arrFields = array();
        $arrFields["change_id"]             = array("char20", false);
        $arrFields["change_date"]           = array("long", true);
        $arrFields["change_user"]           = array("char20", true);
        $arrFields["change_systemid"]       = array("char20", true);
        $arrFields["change_system_previd"]  = array("char20", true);
        $arrFields["change_class"]          = array("char254", true);
        $arrFields["change_action"]         = array("char254", true);
        $arrFields["change_property"]       = array("char254", true);
        $arrFields["change_oldvalue"]       = array(DbDatatypes::STR_TYPE_TEXT, true);
        $arrFields["change_newvalue"]       = array(DbDatatypes::STR_TYPE_TEXT, true);


        $arrTables = array("agp_changelog");
        $arrProvider = SystemChangelog::getAdditionalProviders();
        foreach($arrProvider as $objOneProvider) {
            $arrTables[] = $objOneProvider->getTargetTable();
        }

        $arrDbTables = $this->objDB->getTables();
        foreach($arrTables as $strOneTable) {
            if(!in_array($strOneTable, $arrDbTables)) {
                if(!$this->objDB->createTable($strOneTable, $arrFields, array("change_id"), array("change_date", "change_user", "change_systemid", "change_property")))
                    $strReturn .= "An error occurred! ...\n";
            }
        }

        return $strReturn;

    }

    protected function updateModuleVersion($strModuleName, $strVersion) {
        parent::updateModuleVersion("system", $strVersion);
        parent::updateModuleVersion("right", $strVersion);
        parent::updateModuleVersion("user", $strVersion);
        parent::updateModuleVersion("languages", $strVersion);
        parent::updateModuleVersion("messaging", $strVersion);
    }

    public function update() {
        $strReturn = "";
        //check installed version and to which version we can update
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        $strReturn .= "Version found:\n\t Module: ".$arrModule["module_name"].", Version: ".$arrModule["module_version"]."\n\n";

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.0") {
            $strReturn .= $this->update_70_701();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.0.1") {
            $strReturn .= $this->update_701_702();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.0.2") {
            $strReturn .= $this->update_702_703();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.0.3" || $arrModule["module_version"] == "7.0.4"  || $arrModule["module_version"] == "7.0.5") {
            $strReturn .= $this->update_703_71();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1") {
            $strReturn .= $this->update_71_711();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1.1") {
            $strReturn .= $this->update_711_712();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1.2") {
            $strReturn .= $this->update_712_713();
        }
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1.3") {
            $strReturn .= $this->update_713_714();
        }
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1.4") {
            $strReturn .= $this->update_714_715();
        }
        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "7.1.5") {
            $strReturn .= $this->update_715_716();
        }

        return $strReturn."\n\n";
    }

    private function update_70_701()
    {
        $strReturn = "Updating 7.0 to 7.0.1...\n";

        // password history
        $strReturn .= "Updating system table...\n";
        $this->objDB->createIndex("agp_system", "system_class", ["system_class"]);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.0.1");
        return $strReturn;
    }

    private function update_701_702()
    {
        $strReturn = "Updating 7.0.1 to 7.0.2...\n";

        $strReturn .= "Adding list clickable setting".PHP_EOL;
        $this->registerConstant("_system_lists_clickable_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        $strReturn .= "Upating module version".PHP_EOL;
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.0.2");

        return $strReturn;
    }

    private function update_702_703()
    {
        $strReturn = "Updating 7.0.2 to 7.0.3...\n";

        $strReturn .= "Upating module version".PHP_EOL;
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.0.3");

        return $strReturn;
    }

    private function update_703_71()
    {
        $strReturn = "Updating 7.0.3 to 7.1...\n";

        $strReturn .= "Removing languageset table".PHP_EOL;
        $aExistingTables = $this->objDB->getTables();
        if(in_array("agp_languages_languageset", $aExistingTables)) {
            $this->objDB->_pQuery("DROP TABLE agp_languages_languageset", []);
        }

        $strReturn .= "Removing cache table".PHP_EOL;
        if(in_array("agp_cache", $aExistingTables)) {
            $this->objDB->_pQuery("DROP TABLE agp_cache", []);
        }

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1");

        return $strReturn;
    }

    private function update_71_711()
    {
        $strReturn = "Updating to 7.1.1...".PHP_EOL;
        $strReturn .= "Changing default charting engine".PHP_EOL;
        $cfg = SystemSetting::getConfigByName("_system_graph_type_");
        $cfg->setStrValue("chartjs");
        ServiceLifeCycleFactory::getLifeCycle(get_class($cfg))->update($cfg);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.1");

        return $strReturn;
    }

    private function update_711_712()
    {
        $strReturn = "Updating to 7.1.2...".PHP_EOL;
        $strReturn .= "Updating changelog column types".PHP_EOL;

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.2");

        return $strReturn;
    }

    private function update_712_713()
    {
        $strReturn = "Updating to 7.1.3...".PHP_EOL;
        $strReturn .= "Update messaging entity".PHP_EOL;

        $schema = new OrmSchemamanager();
        $schema->updateTable(MessagingMessage::class);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.3");

        return $strReturn;
    }

    private function update_713_714()
    {
        $strReturn = "Updating to 7.1.4...".PHP_EOL;
        $strReturn .= "Adding system setting".PHP_EOL;
        $this->registerConstant("_system_permission_assignment_threshold_", 500, SystemSetting::$int_TYPE_INT, _system_modul_id_);

        $strReturn .= "Updating user-goup table".PHP_EOL;
        $this->objDB->createIndex("agp_user_group", "ix_group_short_id", ["group_short_id"]);
        $this->objDB->createIndex("agp_user_group", "ix_group_system_group", ["group_system_group"]);

        $strReturn .= "Creating view-permissions table".PHP_EOL;
        $this->objDB->createTable(
            "agp_permissions_view",
            [
                "view_id" => [DbDatatypes::STR_TYPE_CHAR20, false],
                "view_shortgroup" => [DbDatatypes::STR_TYPE_LONG, false],
            ],
            ["view_id", "view_shortgroup"],
            ["view_id", "view_shortgroup"]
        );

        $this->objDB->createTable(
            "agp_permissions_right2",
            [
                "right2_id" => [DbDatatypes::STR_TYPE_CHAR20, false],
                "right2_shortgroup" => [DbDatatypes::STR_TYPE_LONG, false],
            ],
            ["right2_id", "right2_shortgroup"],
            ["right2_id", "right2_shortgroup"]
        );

        $this->objDB->_pQuery("DELETE FROM agp_permissions_view WHERE 1=1", []);
        $this->objDB->_pQuery("DELETE FROM agp_permissions_right2 WHERE 1=1", []);

        $strReturn .= "Creating group map".PHP_EOL;
        $groupMap = [];
        foreach ($this->objDB->getPArray("SELECT group_id, group_short_id FROM agp_user_group", []) as $row) {
            $groupMap[$row["group_short_id"]] = $row["group_id"];
        }

        $strReturn .= "View permissions".PHP_EOL;

        $i = 0;
        foreach ($this->objDB->getGenerator("SELECT system_id, right_view, right_right2 FROM agp_system ORDER BY system_id DESC", []) as $systemRecords) {
                $insertView = [];
                $insertRight2 = [];
                foreach ($systemRecords as  $row) {
                    $i++;
                    $groups = explode(",", trim($row["right_view"], ','));
                    foreach ($groups as $shortid) {
                        if (is_numeric($shortid) && array_key_exists($shortid, $groupMap)) {
                            $insertView[$row['system_id'].$shortid] = [$row['system_id'], $shortid];
                        }
                    }

                    $groups = explode(",", trim($row["right_right2"], ','));
                    foreach ($groups as $shortid) {
                        if (is_numeric($shortid) && array_key_exists($shortid, $groupMap)) {
                            $insertRight2[$row['system_id'].$shortid] = [$row['system_id'], $shortid];
                        }
                    }

                    if ($i % 500 == 0) {
                        if (!empty($insertView)) {
                            $this->objDB->multiInsert("agp_permissions_view", ["view_id", "view_shortgroup"], $insertView);
                            $insertView = [];
                        }

                        if (!empty($insertRight2)) {
                            $this->objDB->multiInsert("agp_permissions_right2", ["right2_id", "right2_shortgroup"], $insertRight2);
                            $insertRight2 = [];
                        }

                        $strReturn .= "Migrated {$i} records ".PHP_EOL;
                    }

                }

                if (!empty($insertView)) {
                    $this->objDB->multiInsert("agp_permissions_view", ["view_id", "view_shortgroup"], $insertView);
                }
                if (!empty($insertRight2)) {
                    $this->objDB->multiInsert("agp_permissions_right2", ["right2_id", "right2_shortgroup"], $insertRight2);
                }
                $strReturn .= "Migrated {$i} records ".PHP_EOL;

        }

        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.4");

        return $strReturn;
    }

    private function update_714_715()
    {
        $strReturn = "Updating to 7.1.5...".PHP_EOL;
        $this->objDB->changeColumn("agp_user_log", "user_log_ip", "user_log_ip", DbDatatypes::STR_TYPE_CHAR254);

        $strReturn .= "Updating messages schema".PHP_EOL;
        $this->objDB->changeColumn("agp_messages", "message_title", "message_title", DbDatatypes::STR_TYPE_CHAR500);

        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.5");

        return $strReturn;
    }

    private function update_715_716()
    {
        $strReturn = "Updating to 7.1.6...".PHP_EOL;
        $strReturn .= "Removing deprecated workflow".PHP_EOL;

        //break if workflows present and < 7.1.6
        $con = SystemModule::getModuleByName("workflows");
        if ($con !== null && version_compare($con->getStrVersion(), "7.1", "l")) {
            return "Update workflows to at least 7.1 before".PHP_EOL;
        }

        $wf = WorkflowsHandler::getHandlerByClass('AGP\Devops\System\Workflows\WorkflowDevopsSender');
        if ($wf !== null) {
            $wf->deleteObjectFromDatabase();
        }

        //TODO: geht erst wenn schema der wf durchmigriert ist, daher: dbupdate
        foreach(WorkflowsWorkflow::getWorkflowsForClass('AGP\Devops\System\Workflows\WorkflowDevopsSender') as $wf) {
            $wf->deleteObjectFromDatabase();
        }

        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1.6");
        return $strReturn;
    }



}
