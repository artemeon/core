<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Dashboard\System\Filter;

use Kajona\System\System\FilterBase;

/**
 * Filter for a dashboard iCalendar
 *
 * @module dashboard
 * @moduleId _dashboard_module_id_
 */
class DashboardICalendarFilter extends FilterBase
{
    /**
     * @var string
     * @tableColumn agp_dashboard_ical.user_systemid
     * @tableColumnDatatype char20
     */
    private $strUserSystemId = "";

    /**
     * @return string
     */
    public function getStrUserSystemId(): string
    {
        return $this->strUserSystemId;
    }

    /**
     * @param string $userSystemId
     */
    public function setStrUserSystemId(string $userSystemId): void
    {
        $this->strUserSystemId = $userSystemId;
    }

}
