<?php

declare(strict_types=1);

namespace Kajona\System\Admin\Reports;

use AGP\Auswertung\Admin\Reports\AuswertungReportInterface;
use AGP\Contracts\Admin\Reports\AuswertungReportExportBase;
use AGP\Phpexcel\System\PhpspreadsheetDataTableExporter;
use AGP\Prozessverwaltung\System\ProzessverwaltungProzGroupAssignment;
use Kajona\System\System\Exception;
use Kajona\System\System\FilterBase;
use Kajona\System\System\Filters\UserGroupFilter;
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
class AdminreportsReportUserroles extends AuswertungReportExportBase implements AuswertungReportInterface
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
            if (!$this->shouldExcludeUserGroupId($userGroup->getStrSystemid())) {
                $userGroups[] = $userGroup;
            }
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
        $this->generalUserGroupId = UserGroup::getGroupByName('GENERAL')->getSystemid();
        $this->adminUserGroupId = SystemSetting::getConfigValue('admins_group_id');

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

                    $moduleName = $target->getArrModule('module');
                    $languageString = 'form_' . $moduleName . '_transparentgroups_' . $assignment->getStrType();
                    $groupName = $this->objLang->getLang($languageString, $moduleName);
                    if ($groupName === '!' . $languageString . '!') {

var_dump($groupName, $moduleName);exit();
                        $groupName = 'n.a.';
                    }

                    $data[] = [
                        $user->getStrUsername(),
                        '',
                        $groupName,
                        $target->getStrDisplayName(),
                        $moduleName,
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
