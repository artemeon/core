<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Devops\System;

use Kajona\System\System\Carrier;
use Kajona\System\System\Config;
use Kajona\System\System\Date;
use Kajona\System\System\DateHelper;
use Kajona\System\System\DatePeriodEnum;
use Kajona\System\System\DateRange;
use Kajona\System\System\GraphFactory;
use Kajona\System\System\SysteminfoInterface;
use Kajona\System\System\UserGroup;
use Kajona\System\System\UserLog;
use Kajona\System\System\UserUser;

/**
 * A plugin collecting the lastest entries from the logfiles
 *
 * @package AGP\Devops\System
 * @author stefan.idler@artemeon.de
 * @since 6.1
 */
class DevopsPluginUsermanagement implements SysteminfoInterface
{
    public static function getExtensionName()
    {
        return SysteminfoInterface::STR_EXTENSION_POINT;
    }

    /**
     * @inheritDoc
     */
    public function getStrTitle()
    {
        return Carrier::getInstance()->getObjLang()->getLang("plugin_dbdumps_userinfo", "devops");
    }

    /**
     * @inheritDoc
     */
    public function getArrContent($mediaType = self::TYPE_HTML)
    {
        $return = [];
        $return[] = ["Number of users", UserUser::getObjectCountFiltered()];
        $return[] = ["Number of groups", UserGroup::getObjectCountFiltered()];
        $return[] = ["Active loginproviders", Config::getInstance()->getConfig("loginproviders")];


        //generate a login count chart for the last 14 days
        $objDateHelper = new DateHelper();
        $objEndDate = new Date();
        $objEndDate->setEndOfDay();

        $objStartDate = $objDateHelper->calcDateRelativeFormatString($objEndDate, "-2 weeks");
        $arrDates = DateRange::getDateRange($objStartDate, $objEndDate, DatePeriodEnum::DAY());

        $arrChartData = array();
        $objLog = new UserLog();
        foreach ($arrDates as $arrOneSlot) {
            $intCount = $objLog->getLoginLogsCount($arrOneSlot[0], $arrOneSlot[1]);
            $arrChartData[dateToString($arrOneSlot[0], false)] = $intCount;
        }

        $objChart = GraphFactory::getGraphInstance();
        $objChart->setArrXAxisTickLabels(array_keys($arrChartData));
        $objChart->addBarChartSet(array_values($arrChartData), "Logins / Day");
        $objChart->setIntHeight(300);

        if ($mediaType === self::TYPE_HTML) {
            $chart = $objChart->renderGraph();
        } elseif ($mediaType === self::TYPE_JSON) {
            $chart = $objChart;
        } else {
            $chart = null;
        }

        $return[] = ["Logins", $chart];
        return $return;
    }
}