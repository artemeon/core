<?php

declare(strict_types=1);

namespace Kajona\System\Admin\Reports;

use AGP\Auswertung\Admin\Reports\AuswertungReportInterface;
use AGP\Contracts\Admin\Reports\AuswertungReportExportBase;
use AGP\Contracts\System\ContractsContractAbstract;
use AGP\Gdpr\System\Models\GdprProcedure;
use AGP\Phpexcel\System\PhpspreadsheetDataTableExporter;
use AGP\Prozessverwaltung\System\ProzessverwaltungProzGroupAssignment;
use AGP\Prozessverwaltung\System\ProzessverwaltungProzGroupBase;
use AGP\Prozessverwaltung\System\ProzessverwaltungProzOe;
use Kajona\System\System\AdminskinHelper;
use Kajona\System\System\Exception;
use Kajona\System\System\FilterBase;
use Kajona\System\System\Filters\UserGroupFilter;
use Kajona\System\System\Link;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Rights;
use Kajona\System\System\SystemModule;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserSourcefactory;
use Kajona\System\System\UserUser;
use Kajona\System\View\Components\Dtable\DTableComponent;
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
        if ($this->getParam('export') === 'xls' && SystemModule::getModuleByName('auswertung')->rightView() && SystemModule::getModuleByName('user')->rightView()) {
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
        $this->adminUserGroupId = UserGroup::getGroupByName('Admins')->getSystemid();

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
                        ];
                        continue 2;
                    }
                }

                foreach (ProzessverwaltungProzGroupAssignment::getAllByGroupId($singleGroupAssignment) as $assignment) {
                    $target = $this->objectFactory->getObject($assignment->getStrProcessId());

                    if ($target instanceof ContractsContractAbstract) {
                        $groupName = $this->objLang->getLang('form_contracts_transparentgroups_' . $assignment->getStrType(), 'contracts');
                    } elseif ($target instanceof GdprProcedure) {
                        $groupName = $this->objLang->getLang('form_gdpr_transparentgroups_' . $assignment->getStrType(), 'gdpr');
                    } elseif ($target instanceof ProzessverwaltungProzGroupBase) {
                        $groupName = $this->objLang->getLang('form_prozessverwaltung_transparentgroups_' . $assignment->getStrType(), 'prozessverwaltung');
                    } else {
                        $groupName = 'n.a.';
                    }

                    $data[] = [
                        $user->getStrUsername(),
                        $groupName,
                        $target->getStrDisplayName(),
                    ];
                }
            }
        }

        (new PhpspreadsheetDataTableExporter())->exportDTableToExcel(
            new DTable([['Benutzer', 'Gruppe', 'Vorgang']], $data)
        );
    }
}
