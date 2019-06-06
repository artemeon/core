<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\Admin\Reports;

use AGP\Auswertung\Admin\Reports\AuswertungReportBase;
use AGP\Auswertung\Admin\Reports\AuswertungReportInterface;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\ArraySectionIterator;
use Kajona\System\System\Csv;
use Kajona\System\System\Link;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\ResponseObject;
use Kajona\System\System\SystemModule;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;

/**
 * Generates an overview of assigned groups per user
 */
class AdminreportsReportUserrights extends AuswertungReportBase implements AuswertungReportInterface
{
    public function getBitShowInNavigation()
    {
        return false;
    }


    /**
     * @return string
     */
    public function getReportTitle()
    {
        return $this->getLang("report_userrights");
    }

    /**
     * @return string
     */
    public function getReportTextBase()
    {
        return "user";
    }

    /**
     * @return string
     */
    public function getInternalTitle()
    {
        return "userrights";
    }

    /**
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    public function getReport()
    {
        $strReturn = "";

        $linkHref = Link::getLinkAdminXml("auswertung", "showDirect", "&report=".$this->getInternalTitle()."&getCsv=true");
        $strLink = Link::getLinkAdminManual(
            "href='#' onclick=\"DownloadIndicator.triggerDownload('{$linkHref}');return false;\"",
            AdminskinHelper::getAdminImage("icon_text")." ".$this->getLang("report_evaluations_csv_export", "slareporting"),
            "",
            "",
            "",
            "",
            false
        );

        $strReturn .= $this->objToolkit->addToContentToolbar($strLink);


        $strReturn .= $this->objToolkit->warningBox($this->getLang("report_hinweis_parametrisierung"));

        if (SystemModule::getModuleByName("auswertung")->rightView() && SystemModule::getModuleByName("user")->rightView()) {
            if ($this->getParam("getCsv") != "") {
                ResponseObject::getInstance()->handleProgressCookie();
                $arrUsers = UserUser::getObjectListFiltered();
                $arrUserRows = $this->getUserData($arrUsers);

                $objCSV = new Csv();

                $arrMapping = array(
                    "name"       => "LastName_Value",
                    "forename"   => "FirstName_Value",
                    "username"   => "AccountName_Value",
                    "cc"         => "CostCentre_Value",
                    "systemname" => "SystemName_Value",
                    "groups"     => "SystemAuthorityName_Value",
                    "groupdesc"  => "SystemAuthorityDescription",
                    "status"     => "AccountActive"
                );

                $objCSV->setArrMapping($arrMapping);
                $objCSV->setStrFilename("user.csv");
                $objCSV->setStrDelimiter(";");
                $objCSV->setArrData($arrUserRows);
                $objCSV->writeArrayToFile(true, false);
            } else {
                $objIterator = $this->getSectionIterator();
                $arrUserRows = $this->getUserData($objIterator);

                $arrHeader = array();
                $arrHeader[] = $this->getLang("form_user_name", "user");
                $arrHeader[] = $this->getLang("form_user_forename", "user");
                $arrHeader[] = $this->getLang("user_username", "user");
                $arrHeader[] = $this->getLang("user_zugehoerigkeit", "user");
                $arrHeader[] = "";
                $arrHeader[] = "";
                $arrHeader[] = "";
                $arrHeader[] = $this->getLang("user_active");

                $arrParams = ["report" => "userrights"];
                $strReturn .= $this->objToolkit->dataTable($arrHeader, $arrUserRows);
                $strReturn .= $this->objToolkit->getPageview($objIterator, "auswertung", "show", http_build_query($arrParams));
            }
        } else {
            $strReturn = $this->getLang("commons_error_permissions");
        }

        return $strReturn ; 
    }

    /**
     * Fetches the data per user
     * @param Iterable $arrResult
     * @return array
     */
    private function getUserData(Iterable $arrResult): array
    {
        $arrUserRows = array();
        $intI = 0;
        /** @var UserUser $objOneUser */
        foreach ($arrResult as $objOneUser) {
            $arrGroupIdsOfUser = $objOneUser->getArrGroupIds();
            if (!$arrGroupIdsOfUser) {
                $arrUserRows[$intI]["name"] = $objOneUser->getStrName();
                $arrUserRows[$intI]["forename"] = $objOneUser->getStrForename();
                $arrUserRows[$intI]["username"] = $objOneUser->getStrUsername();
                $arrUserRows[$intI]["groups"] = "_LEER_";
                $arrUserRows[$intI]["systemname"] = "AGP";
                $arrUserRows[$intI]["groupdesc"] = "";
                $arrUserRows[$intI]["cc"] = "";
                $arrUserRows[$intI]["status"] = $objOneUser->getIntRecordStatus() ? $this->getLang("user_active") : $this->getLang("user_inactive");
                $intI++;

            } else {
                foreach ($arrGroupIdsOfUser as $strOneGroupId) {
                    /** @var UserGroup $objGroup */
                    $objGroup = Objectfactory::getInstance()->getObject($strOneGroupId);
                    if ($objGroup === null || $objGroup->getIntSystemGroup() == 1) {
                        continue;
                    }

                    $arrUserRows[$intI]["name"] = $objOneUser->getStrName();
                    $arrUserRows[$intI]["forename"] = $objOneUser->getStrForename();
                    $arrUserRows[$intI]["username"] = $objOneUser->getStrUsername();
                    $arrUserRows[$intI]["groups"] = str_replace(";", ",", html_entity_decode($objGroup->getStrName()));
                    $arrUserRows[$intI]["systemname"] = "AGP";

                    if (method_exists($objGroup->getObjSourceGroup(), "getStrDesc")) {
                        $arrUserRows[$intI]["groupdesc"] = $objGroup->getObjSourceGroup()->getStrDesc();
                    } else {
                        $arrUserRows[$intI]["groupdesc"] = "";
                    }

                    $arrUserRows[$intI]["cc"] = "";
                    $arrUserRows[$intI]["status"] = $objOneUser->getIntRecordStatus() ? $this->getLang("user_active") : $this->getLang("user_inactive");
                    $intI++;
                }
            }
        }

        return $arrUserRows;
    }

    /**
     * Returns the section iterator for the list
     *
     * @return ArraySectionIterator
     */
    protected function getSectionIterator()
    {
        $objArraySectionIterator = new ArraySectionIterator(UserUser::getObjectCountFiltered());
        $objArraySectionIterator->setPageNumber((int)($this->getParam("pv") != "" ? $this->getParam("pv") : 1));
        $objArraySectionIterator->setArraySection(UserUser::getObjectListFiltered(null, "", $objArraySectionIterator->calculateStartPos(), $objArraySectionIterator->calculateEndPos()));

        return $objArraySectionIterator;
    }
}
