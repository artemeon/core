<?php

declare(strict_types=1);

namespace Kajona\System\Admin\Reports;

use AGP\Auswertung\Admin\Reports\AuswertungReportBaseFilter;
use AGP\Auswertung\Admin\Reports\AuswertungReportInterface;
use AGP\Contracts\Admin\Reports\AuswertungReportExportBase;
use AGP\Phpexcel\System\PhpspreadsheetDataTableExporter;
use AGP\Prozessverwaltung\System\ProzessverwaltungProzGroupAssignment;
use AGP\Prozessverwaltung\System\ProzessverwaltungProzOe;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Exception;
use Kajona\System\System\FilterBase;
use Kajona\System\System\Filters\UserGroupFilter;
use Kajona\System\System\Link;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Root;
use Kajona\System\System\SystemModule;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserUser;
use Kajona\System\View\Components\Dtable\Model\DTable;

/**
 * @since 7.1
 */
class AdminreportsReportUserroles extends AuswertungReportBaseFilter implements AuswertungReportInterface
{
    /** @var Objectfactory */
    private $objectFactory;

    /** @var string */
    private $generalUserGroupId;

    /** @var string */
    private $adminUserGroupId;

    public function __construct($systemId = '')
    {
        parent::__construct($systemId);
        $this->objectFactory = Objectfactory::getInstance();
    }

    /**
     * @inheritDoc
     */
    public function getInternalTitle(): string
    {
        return 'userroles';
    }

    /**
     * @inheritDoc
     */
    public function getBitShowInNavigation(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getBitRenderMetaData(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getReportTextBase(): string
    {
        return 'user';
    }

    /**
     * @inheritDoc
     */
    public function getReportTitle(): string
    {
        return $this->objLang->getLang('report_userroles', 'user');
    }

    /**
     * @inheritDoc
     */
    protected function getObjFilter(): ?FilterBase
    {
        return null;
    }

    /**
     * Renders the export button for the report
     *
     * @return string
     */
    protected function renderExportButton($arrAdditionalFilters = [])
    {
        $strReturn = "";

        $arrParams = [
            'report' => $this->getInternalTitle(),
            'export' => 'xls',
        ];
        if (!empty($arrAdditionalFilters)) {
            $arrParams = array_merge($arrParams, $arrAdditionalFilters);
        }

        $strXlsExportHref = Link::getLinkAdminXml("auswertung", "showDirect", $arrParams);
        $strExportLink = Link::getLinkAdminManual(
            "href='#' onclick=\"DownloadIndicator.triggerDownload('{$strXlsExportHref}');return false;\"",
            AdminskinHelper::getAdminImage("icon_excel") . " " . $this->getLang("change_export_excel", "system"),
            "",
            "",
            "",
            "",
            false
        );

        $strReturn .= $this->objToolkit->addToContentToolbar($strExportLink);
        return $strReturn;
    }

    /**
     * {@inheritDoc}
     * @throws Exception
     */
    public function getReport(): string
    {
        if ($this->getParam('export') === 'xls' && SystemModule::getModuleByName('auswertung')->rightView() && SystemModule::getModuleByName('user')->rightRight1()) {
            $this->generateExcelExport();

            return '';
        }

        $reportContent = '';

        $reportContent .= $this->renderExportButton();

        $reportContent .= $this->objToolkit->warningBox($this->objLang->getLang('report_hinweis_parametrisierung', 'user'));
        $reportContent .= $this->objToolkit->warningBox($this->objLang->getLang('report_hinweis_runtime', 'user'), 'alert-info');

        return $reportContent;
    }

    /**
     * @return UserGroup[]
     */
    private function getGeneralUserGroups(): array
    {
        $userGroups = [];

        $userGroupFilter = new UserGroupFilter();
        $userGroupFilter->setIntSystemFilter(1);
        foreach (UserGroup::getObjectListFiltered($userGroupFilter) as $userGroup) {
            $userGroups[] = $userGroup;
        }

        return $userGroups;
    }

    /**
     * @param UserGroup[] $userGroups
     * @return UserUser[]
     */
    private function getUsersInUserGroups(array $userGroups): array
    {
        $userIds = [];
        foreach ($userGroups as $userGroup) {
            $userIds[] = $userGroup->getObjSourceGroup()->getUserIdsForGroup();
        }

        return \array_map(
            function (string $userId): UserUser {
                /** @noinspection PhpIncompatibleReturnTypeInspection */
                return $this->objectFactory->getObject($userId);
            },
            \array_unique(\array_merge([], ...$userIds))
        );
    }

    private function shouldExcludeUserGroupId(string $userGroupId): bool
    {
        return \in_array($userGroupId, [$this->generalUserGroupId, $this->adminUserGroupId], true);
    }

    private function generateExcelExport(): void
    {
        $this->generalUserGroupId = UserGroup::getGroupByName('GENERAL') !== null ? UserGroup::getGroupByName('GENERAL')->getSystemid() : null;
        $this->adminUserGroupId = SystemSetting::getConfigValue('_admins_group_id_');

        $generalUserGroups = $this->getGeneralUserGroups();

        $data = [];
        foreach ($this->getUsersInUserGroups($generalUserGroups) as $user) {

            foreach ($user->getArrGroupIds() as $singleGroupAssignment) {
                if ($this->shouldExcludeUserGroupId($singleGroupAssignment)) {
                    continue;
                }

                foreach ($generalUserGroups as $generalUserGroup) {
                    if ($singleGroupAssignment === $generalUserGroup->getStrSystemid()) {
                        $data[] = [
                            $user->getStrUsername(),
                            $generalUserGroup->getStrDisplayName(),
                            '',
                            '',
                            '',
                        ];
                        continue 2;
                    }
                }

                foreach (ProzessverwaltungProzGroupAssignment::getAllByGroupId($singleGroupAssignment) as $assignment) {
                    $target = $this->objectFactory->getObject($assignment->getStrProcessId());
                    if (!($target instanceof Root)) {
                        continue;
                    }

                    if ($target instanceof ProzessverwaltungProzOe && in_array($assignment->getStrType(), [ProzessverwaltungProzOe::GROUP_HEAD, ProzessverwaltungProzOe::GROUP_DEPUTY, 'oe_users'])) {
                        continue;
                    }

                    $moduleName = $target->getArrModule('module');
                    $languageString = 'form_' . $moduleName . '_transparentgroups_' . $assignment->getStrType();
                    $groupName = $this->objLang->getLang($languageString, $moduleName);
                    if ($groupName === '!' . $languageString . '!') {
                        $groupName = 'n.a.';
                    }

                    $data[] = [
                        $user->getStrUsername(),
                        '',
                        $groupName,
                        $target->getStrDisplayName(),
                        $this->getLang('modul_titel', $moduleName),
                    ];
                }
            }
        }

        (new PhpspreadsheetDataTableExporter())->exportDTableToExcel(
            new DTable(
                [
                    [
                        $this->objLang->getLang('report_userroles_column_user', 'user'),
                        $this->objLang->getLang('report_userroles_column_group_common', 'user'),
                        $this->objLang->getLang('report_userroles_column_group_process', 'user'),
                        $this->objLang->getLang('report_userroles_column_process', 'user'),
                        $this->objLang->getLang('report_userroles_column_module', 'user'),
                    ],
                ],
                $data
            )
        );
    }
}
