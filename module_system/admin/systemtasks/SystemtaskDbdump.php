<?php
/*"******************************************************************************************************
*   (c) 2004-2006 by MulchProductions, www.mulchprod.de                                                 *
*   (c) 2007-2016 by Kajona, www.kajona.de                                                              *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
*-------------------------------------------------------------------------------------------------------*
*   $Id$                                        *
********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\Admin\Systemtasks;

use Kajona\System\Admin\AdminFormgenerator;
use Kajona\System\Admin\Formentries\FormentryCheckbox;
use Kajona\System\Admin\Formentries\FormentryCheckboxarray;
use Kajona\System\System\Carrier;
use Kajona\System\System\Filesystem;
use Kajona\System\System\Link;
use Kajona\System\System\StringUtil;
use Kajona\System\System\SystemModule;

/**
 * Dumps the database to the filesystem using the current db-driver
 *
 * @package module_system
 */
class SystemtaskDbdump extends SystemtaskBase implements AdminSystemtaskInterface, ApiSystemTaskInterface
{

    /**
     * @inheritdoc
     */
    public function getGroupIdentifier()
    {
        return "database";
    }

    /**
     * @inheritdoc
     */
    public function getStrInternalTaskName()
    {
        return "dbdump";
    }

    /**
     * @inheritdoc
     */
    public function getStrTaskName()
    {
        return $this->getLang("systemtask_dbexport_name");
    }

    /**
     * @inheritdoc
     */
    public function executeTask()
    {

        if (!SystemModule::getModuleByName("system")->rightRight2()) {
            return $this->getLang("commons_error_permissions");
        }

        if ($this->getParam("filenametodownload") != "" && Carrier::getInstance()->getObjSession()->isSuperAdmin()) {
            $objFilesytem = new Filesystem();
            $objFilesytem->streamFile("/project/dbdumps/".$this->getParam("filenametodownload"));
            die();
        }

        $arrToExclude = explode(",", $this->getParam("excludedtables"));

        $strDumpName = "";
        $strRedirect = "";
        if (Carrier::getInstance()->getObjDB()->dumpDb($arrToExclude, false, $strDumpName)) {
            if ($this->getParam("streamfile") != "") {
                $strRedirect = Link::clientRedirectHref("system", "systemTasks", ["task" => $this->getStrInternalTaskName(), "execute" => "true", "executedirectly" => "true", "filenametodownload" => $strDumpName, "contentFill" => "1"], false, false);
            }

            return $this->objToolkit->getTextRow($this->getLang("systemtask_dbexport_success").$strRedirect);
        } else {
            return $this->objToolkit->getTextRow($this->getLang("systemtask_dbexport_error"));
        }
    }

    /**
     * @inheritDoc
     */
    public function execute($body)
    {
        if (isset($body["excludedtables"]) && is_array($body["excludedtables"])) {
            $excludeTables = $body["excludedtables"];
        } else {
            $excludeTables = [];
        }

        $connection = Carrier::getInstance()->getObjDB();
        $connection->dumpDb($excludeTables);

        return $this->getLang("systemtask_dbexport_success");
    }

    /**
     * @inheritdoc
     */
    public function getAdminForm()
    {
        $objForm = new AdminFormgenerator("", null);
        $arrTables = [];
        foreach (Carrier::getInstance()->getObjDB()->getTables() as $strTable) {
            if (StringUtil::indexOf($strTable, "messages") !== false || StringUtil::indexOf($strTable, "search_ix") !== false || StringUtil::indexOf($strTable, "cache") !== false || StringUtil::indexOf($strTable, "changelog") !== false) {
                $arrTables[$strTable] = $strTable;
            }
        }
        $objForm->addField(new FormentryCheckboxarray("", "excludedtables"))->setStrLabel($this->getLang("systemtask_dbexport_excludetitle"))->setArrKeyValues($arrTables);
        if (Carrier::getInstance()->getObjSession()->isSuperAdmin()) {
            $objForm->addField(new FormentryCheckbox("", "streamfile"))->setStrLabel($this->getLang("systemtask_dbexport_stream"));
        }
        return $objForm;
    }

    /**
     * @inheritdoc
     */
    public function getSubmitParams()
    {
        return "&excludedtables=".(is_array($this->getParam("excludedtables")) ? implode(",", array_keys($this->getParam("excludedtables"))) : "")."&streamfile=".$this->getParam("streamfile");
    }
}
