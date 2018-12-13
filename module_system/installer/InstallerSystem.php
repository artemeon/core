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
use Kajona\System\System\Rights;
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
use Kajona\System\System\Workflows\WorkflowMessageQueue;
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
        $this->strContentLanguage = $this->objSession->getAdminLanguage(true, true);
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

        //TODO: remove system deleted index
        if(!$this->objDB->createTable("agp_system", $arrFields, array("system_id"), array("system_prev_id", "system_module_nr", "system_sort", "system_owner", "system_create_date", "system_status", "system_lm_time", "system_lock_time", "system_class")))
            $strReturn .= "An error occurred! ...\n";


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
        $arrFields["user_log_ip"] = array("char20", true);
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
        $this->registerConstant("_system_graph_type_", "jqplot", SystemSetting::$int_TYPE_STRING, _system_modul_id_);
        //3.4: Enabling or disabling the internal changehistory
        $this->registerConstant("_system_changehistory_enabled_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        $this->registerConstant("_system_timezone_", "", SystemSetting::$int_TYPE_STRING, _system_modul_id_);
        $this->registerConstant("_system_session_ipfixation_", "true", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);


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

        if($this->strContentLanguage == "de")
            $objLanguage->setStrName("de");
        else
            $objLanguage->setStrName("en");

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
        $arrFields["change_oldvalue"]       = array(DbDatatypes::STR_TYPE_LONGTEXT, true);
        $arrFields["change_newvalue"]       = array(DbDatatypes::STR_TYPE_LONGTEXT, true);


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
        if($arrModule["module_version"] == "6.2") {
            $strReturn .= $this->update_62_621();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.2.1") {
            $strReturn .= $this->update_621_622();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.2.2") {
            $strReturn .= $this->update_622_623();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.2.3") {
            $strReturn .= $this->update_623_624();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.2.4") {
            $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.2.5");
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.2.5") {
            $strReturn .= $this->update_625_65();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.5") {
            $strReturn .= $this->update_65_651();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.5.1") {
            $strReturn .= $this->update_651_652();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.5.2") {
            $strReturn .= $this->update_652_653();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.5.3" || $arrModule["module_version"] == "6.5.4") {
            $strReturn .= "Updating to 6.6...\n";
            $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.6");
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.6") {
            $strReturn .= $this->update_66_661();
        }

        $arrModule = SystemModule::getPlainModuleData($this->objMetadata->getStrTitle(), false);
        if($arrModule["module_version"] == "6.6.1") {
            $strReturn .= $this->update_661_70();
        }

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
        if($arrModule["module_version"] == "7.0.3") {
            $strReturn .= $this->update_703_71();
        }

        return $strReturn."\n\n";
    }

    private function update_62_621()
    {
        $strReturn = "Updating 6.2 to 6.2.1...\n";

        $strReturn .= "Adding cookie setting\n";
        $this->registerConstant("_cookies_only_https_", "false", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.2.1");
        return $strReturn;
    }


    private function update_621_622()
    {
        $strReturn = "Updating 6.2.1 to 6.2.2...\n";


        $strReturn .= "Removing system_comment column...\n";
        $this->objDB->removeColumn("agp_system", "system_comment");

        Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_DBQUERIES | Carrier::INT_CACHE_TYPE_DBSTATEMENTS);

        $strReturn .= "Registering the id generator\n";
        // install idgenerator table
        $objSchemamanager = new OrmSchemamanager();
        $objSchemamanager->createTable(IdGenerator::class);

        $strReturn .= "Altering group table...\n";
        $this->objDB->addColumn("agp_user_group", "group_short_id", DbDatatypes::STR_TYPE_INT);

        $strReturn .= "Adding ids to each group\n";
        $strQuery = "SELECT group_id FROM agp_user_group WHERE group_short_id < 1 OR group_short_id IS NULL";
        foreach($this->objDB->getPArray($strQuery, array()) as $arrOneRow) {
            $strQuery = "UPDATE agp_user_group set group_short_id = ? WHERE group_id = ?";
            $this->objDB->_pQuery($strQuery, array(IdGenerator::generateNextId(UserGroup::INT_SHORTID_IDENTIFIER), $arrOneRow["group_id"]));
        }

        $strReturn .= $this->migrateUserData(2500);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.2.2");
        return $strReturn;
    }

    private function update_622_623()
    {
        $strReturn = "Updating 6.2.2 to 6.2.3...\n";

        $strReturn .= "Adding permission columns to system table";
        $this->objDB->addColumn("agp_system", "right_inherit", DbDatatypes::STR_TYPE_INT);
        $this->objDB->addColumn("agp_system", "right_view", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_edit", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_delete", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_right", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_right1", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_right2", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_right3", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_right4", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_right5", DbDatatypes::STR_TYPE_TEXT);
        $this->objDB->addColumn("agp_system", "right_changelog", DbDatatypes::STR_TYPE_TEXT);


        $strReturn .= "Moving data...\n";

        foreach ($this->objDB->getGenerator("SELECT * FROM agp_system_right ORDER BY right_id", []) as $arrResultSet) {
            foreach ($arrResultSet as $arrRow) {
                $strQuery = "UPDATE agp_system 
                            SET right_inherit = ?, right_view = ?, right_edit = ?, right_delete = ?, right_right = ?, right_right1 = ?, 
                                right_right2 = ?, right_right3 = ?, right_right4 = ?, right_right5 = ?, right_changelog = ? 
                          WHERE system_id = ?";

                $this->objDB->_pQuery($strQuery,
                    [
                        $arrRow["right_inherit"],
                        $arrRow["right_view"],
                        $arrRow["right_edit"],
                        $arrRow["right_delete"],
                        $arrRow["right_right"],
                        $arrRow["right_right1"],
                        $arrRow["right_right2"],
                        $arrRow["right_right3"],
                        $arrRow["right_right4"],
                        $arrRow["right_right5"],
                        $arrRow["right_changelog"],
                        $arrRow["right_id"]
                    ]
                );
            }
        }


        Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_DBQUERIES | Carrier::INT_CACHE_TYPE_DBSTATEMENTS);


        $strReturn .= "Dropping old permissions table...\n";
        $this->objDB->_pQuery("DROP TABLE agp_system_right", array());


        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.2.3");
        return $strReturn;
    }

    private function update_623_624()
    {
        $strReturn = "Updating 6.2.3 to 6.2.4...\n";
        $strReturn .= "Shifting settings to 'real' objects\n";

        $arrSystemModule = $this->objDB->getPRow("SELECT module_id FROM agp_system_module WHERE module_name = 'system'", []);

        $strQuery = "SELECT system_config_id FROM agp_system_config";
        foreach ($this->objDB->getPArray($strQuery, []) as $arrOneRow) {

            if($this->objDB->getPRow("SELECT COUNT(*) as anz FROM agp_system WHERE system_id = ?", array($arrOneRow["system_config_id"]))["anz"] > 0) {
                continue;
            }

            $strQuery = "INSERT INTO agp_system 
                (system_id, system_prev_id, system_module_nr, system_sort, system_status, system_class, system_deleted, right_inherit) values 
                (?, ?, ?, ?, ?, ?, ?, ?)";
            $this->objDB->_pQuery($strQuery, [
                $arrOneRow["system_config_id"],
                $arrSystemModule["module_id"],
                _system_modul_id_,
                -1,
                1,
                SystemSetting::class,
                0,
                1
            ]);
        }

        Carrier::getInstance()->flushCache(Carrier::INT_CACHE_TYPE_DBQUERIES | Carrier::INT_CACHE_TYPE_DBSTATEMENTS | Carrier::INT_CACHE_TYPE_ORMCACHE | Carrier::INT_CACHE_TYPE_OBJECTFACTORY);

        Rights::getInstance()->rebuildRightsStructure($arrSystemModule["module_id"]);


        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.2.4");
        return $strReturn;
    }


    private function update_625_65()
    {
        $strReturn = "Updating 6.2.4 to 6.5...\n";
        $strReturn .= "Adding alert table\n";

        $objManager = new OrmSchemamanager();
        $objManager->createTable(MessagingAlert::class);

        $strReturn .= "Adding user group flag\n";
        $this->objDB->addColumn("agp_user_group", "group_system_group", DbDatatypes::STR_TYPE_INT);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.5");
        return $strReturn;
    }

    private function update_65_651()
    {
        $strReturn = "Updating 6.5 to 6.5.1...\n";
        $strReturn .= "Adding session setting\n";

        $this->registerConstant("_system_session_ipfixation_", "true", SystemSetting::$int_TYPE_BOOL, _system_modul_id_);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.5.1");
        return $strReturn;
    }


    private function update_651_652()
    {
        $strReturn = "Updating 6.5.1 to 6.5.2...\n";
        $strReturn .= "Install message queue\n";

        $objManager = new OrmSchemamanager();
        $objManager->createTable(MessagingQueue::class);

        // add workflow
        $strReturn .= "Registering message queue workflow...\n";
        if (SystemModule::getModuleByName("workflows") !== null) {
            if (WorkflowsWorkflow::getWorkflowsForClassCount(WorkflowMessageQueue::class, false) == 0) {
                $objWorkflow = new WorkflowsWorkflow();
                $objWorkflow->setStrClass(WorkflowMessageQueue::class);
                ServiceLifeCycleFactory::getLifeCycle(get_class($objWorkflow))->update($objWorkflow);
            }
        }

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.5.2");
        return $strReturn;
    }

    private function update_652_653()
    {
        $strReturn = "Updating 6.5.2 to 6.5.3...\n";
        $strReturn .= "Upgrade message queue\n";

        if (!$this->objDB->hasColumn("agp_messages_alert", "alert_priority")) {
            $this->objDB->addColumn("agp_messages_alert", "alert_priority", DbDatatypes::STR_TYPE_INT);
        }

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.5.3");
        return $strReturn;
    }

    private function update_66_661()
    {
        $strReturn = "Updating 6.6 to 6.6.1...\n";

        // password history
        $strReturn .= "Installing password history...\n";
        $objManager = new OrmSchemamanager();
        $objManager->createTable(SystemPwHistory::class);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "6.6.1");
        return $strReturn;
    }


    private function update_661_70()
    {
        $strReturn = "Updating 6.6.1 to 7.0...\n";

        // password history
        $strReturn .= "Updating session table...\n";
        $this->objDB->addColumn("agp_session", "session_resetuser", DbDatatypes::STR_TYPE_INT);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.0");
        return $strReturn;
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

        $strReturn .= "Migrating oldvalue and newvalue columns of change tables to longtext".PHP_EOL;

        $arrTables = array("agp_changelog");
        $arrProvider = SystemChangelog::getAdditionalProviders();
        foreach($arrProvider as $objOneProvider) {
            $arrTables[] = $objOneProvider->getTargetTable();
        }

        foreach($arrTables as $strOneTable) {

            if (Config::getInstance()->getConfig("dbdriver") == "mysqli") {
                //direct change on the table
                Database::getInstance()->changeColumn($strOneTable, "change_oldvalue", "change_oldvalue", DbDatatypes::STR_TYPE_LONGTEXT);
                Database::getInstance()->changeColumn($strOneTable, "change_newvalue", "change_newvalue", DbDatatypes::STR_TYPE_LONGTEXT);

            } else {
                //Need to do it this way since under oracle converting from varchar2 to clob is not possible
                Database::getInstance()->addColumn($strOneTable, "temp_change_oldvalue", DbDatatypes::STR_TYPE_LONGTEXT);
                Database::getInstance()->_pQuery("UPDATE $strOneTable SET temp_change_oldvalue=change_oldvalue", []);
                Database::getInstance()->removeColumn($strOneTable, "change_oldvalue");
                Database::getInstance()->changeColumn($strOneTable, "temp_change_oldvalue", "change_oldvalue", DbDatatypes::STR_TYPE_LONGTEXT);

                Database::getInstance()->addColumn($strOneTable, "temp_change_newvalue", DbDatatypes::STR_TYPE_LONGTEXT);
                Database::getInstance()->_pQuery("UPDATE $strOneTable SET temp_change_newvalue=change_newvalue", []);
                Database::getInstance()->removeColumn($strOneTable, "change_newvalue");
                Database::getInstance()->changeColumn($strOneTable, "temp_change_newvalue", "change_newvalue", DbDatatypes::STR_TYPE_LONGTEXT);
            }
        }

        $strReturn .= "Upating module version".PHP_EOL;
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.0.3");

        return $strReturn;
    }

    private function update_703_71()
    {
        $strReturn = "Updating 7.0.3 to 7.1...\n";

        $strReturn .= "Removing languageset table".PHP_EOL;
        $this->objDB->_pQuery("DROP TABLE agp_languages_languageset", []);

        $strReturn .= "Removing cache table".PHP_EOL;
        $this->objDB->_pQuery("DROP TABLE agp_cache", []);

        $strReturn .= "Updating module-versions...\n";
        $this->updateModuleVersion($this->objMetadata->getStrTitle(), "7.1");

        if (Config::getInstance()->getConfig("dbdriver") == "mysqli") {
            // flush cache to exclude deleted tables
            Database::getInstance()->flushTablesCache();

            $strReturn .= "Updating myisam tables".PHP_EOL;

            foreach (Database::getInstance()->getTables() as $table) {
                $create = StringUtil::toLowerCase(Database::getInstance()->getPRow("show create table {$table}", [])["Create Table"]);

                if (StringUtil::indexOf($create, "engine=myisam") !== false) {
                    $strReturn .= "Updating engine of {$table}".PHP_EOL;
                    Database::getInstance()->_pQuery("ALTER TABLE {$table} ENGINE = InnoDB", []);
                }


            }
        }
        return $strReturn;
    }



    /**
     * Helper to migrate the system-id based permission table to an int based one
     *
     * @param null|int $intPagesize
     * @param bool $bitEchodata
     * @return string
     */
    public function migrateUserData($intPagesize = null, $bitEchodata = false) {

        $strRun = "Migrating old permissions table to new table data...\n";

        $arrIdToInt = array();
        foreach ($this->objDB->getPArray("SELECT group_id, group_short_id FROM agp_user_group ORDER BY group_id DESC", array()) as $arrOneRow) {
            $arrIdToInt[$arrOneRow["group_id"]] = $arrOneRow["group_short_id"];
        }

        $objGenerator = $this->objDB->getGenerator("SELECT * FROM agp_system_right ORDER BY right_id DESC", [], $intPagesize);
        foreach ($objGenerator as $arrResultSet) {
            foreach ($arrResultSet as $arrSingleRow) {
                $arrParams = array();

                foreach (["right_changelog", "right_delete", "right_edit", "right_right", "right_right1", "right_right2", "right_right3", "right_right4", "right_right5", "right_view"] as $strOneCol) {
                    $strNewString = ",";
                    foreach (explode(",", $arrSingleRow[$strOneCol]) as $strOneGroup) {
                        if (!empty($strOneGroup) && isset($arrIdToInt[$strOneGroup])) {
                            $strNewString .= $arrIdToInt[$strOneGroup].",";
                        } elseif (validateSystemid($strOneGroup)) {
                            //do nothing, seems to be an old id
                        } else {
                            //keep migrated ones
                            $strNewString .= $strOneGroup.",";
                        }
                    }
                    $arrParams[] = $strNewString;
                }

                $strQuery = "UPDATE agp_system_right SET right_changelog = ?,right_delete = ?,right_edit = ?,right_right = ?,right_right1 = ?,right_right2 = ?,right_right3 = ?,right_right4 = ?,right_right5 = ?,right_view =? WHERE right_id = ?";
                $arrParams[] = $arrSingleRow["right_id"];

                $this->objDB->_pQuery($strQuery, $arrParams);
            }

            $strLoop = "Converted ".count($arrResultSet)." source rows ".PHP_EOL;

            if ($bitEchodata) {
                echo $strLoop;
                flush();
                ob_flush();
            }

            $strRun .= $strLoop;
        }

        return $strRun;
    }
}
