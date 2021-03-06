<?php
/*"******************************************************************************************************
*   (c) 20014-2016 by Kajona, www.kajona.de                                                             *
*       Published under the GNU LGPL v2.1, see /system/licence_lgpl.txt                                 *
********************************************************************************************************/

namespace Kajona\System\System;

use DateTime;


/**
 * A general helper in order to calculate special dates, like easter or s.th. else
 *
 * @package module_system
 * @author sidler@mulchprod.de
 * @since 4.6
 */
class DateHelper
{

    private $strParseFormat = "YmdHis";

    /**
     * Validates if the passed day is a easter bank holiday.
     * This includes: friday before easter, easter saturday and sunday, easter monday
     *
     * @param Date $objDate
     *
     * @return bool
     */
    public function isEasterHoliday(Date $objDate)
    {
        $objEasterSunday = $this->calcEasterSunday($objDate->getIntYear());
        $objEasterSaturday = clone $objEasterSunday;
        $objEasterSaturday->setPreviousDay();
        $objEasterFriday = clone $objEasterSaturday;
        $objEasterFriday->setPreviousDay();
        $objEasterMonday = clone $objEasterSunday;
        $objEasterMonday->setNextDay();

        return $objDate->isSameDay($objEasterFriday)
        || $objDate->isSameDay($objEasterSaturday)
        || $objDate->isSameDay($objEasterSunday)
        || $objDate->isSameDay($objEasterMonday);

    }


    /**
     * Target 2 is the european payments network.
     * Payments are possible for regular workdays (monday till friday)
     * except for some bank holidays. Those include:
     *  New Year:        1. January
     *  Karfreitag
     *  Ostermontag
     *  Labor Day        1. May
     *  1st Xmas Day    25. December
     *  2nd Xmas Day    26. December
     * TARGET = Trans-European Automated Real-Time Gross Settlement Express Transfer
     *
     * @param Date $objDate
     *
     * @return bool
     */
    public function isValidTarget2Day(Date $objDate)
    {
        if ($objDate->getIntDayOfWeek() == Date::INT_DAY_SATURDAY) {
            return false;
        }

        if ($objDate->getIntDayOfWeek() == Date::INT_DAY_SUNDAY) {
            return false;
        }

        $objCompare = clone $objDate;

        //1st of january
        if ($objDate->isSameDay($objCompare->setIntDay(1)->setIntMonth(1))) {
            return false;
        }

        //1st of may
        if ($objDate->isSameDay($objCompare->setIntDay(1)->setIntMonth(5))) {
            return false;
        }

        //25th of december
        if ($objDate->isSameDay($objCompare->setIntDay(25)->setIntMonth(12))) {
            return false;
        }

        //26th of december
        if ($objDate->isSameDay($objCompare->setIntDay(26)->setIntMonth(12))) {
            return false;
        }

        //easter
        if ($this->isEasterHoliday($objDate)) {
            return false;
        }

        return true;
    }

    /**
     * Calculates the date of easter-sunday for the passed year.
     * In best cases, this may be done by easter_date, but since this requires a special extension,
     * we calc the date on our own just to be sure.
     *
     * @param $intYear
     *
     * @return Date
     * @see http://php.net/manual/de/function.easter-date.php#68874
     */
    private function calcEasterSunday($intYear)
    {

        $intMarch21DayOffset = date('z', mktime(0, 0, 0, 3, 21, $intYear));

        $intDaysAfterMarch = ((15 + $intYear / 100 - $intYear / 400 - (8 * $intYear / 100 + 13) / 25) % 30 + 19 * ($intYear % 19)) % 30;

        if ($intDaysAfterMarch == 29) {
            $intTargetDay = 28;
        } elseif ($intDaysAfterMarch == 28 && ($intYear % 17) >= 11) {
            $intTargetDay = 27;
        } else {
            $intTargetDay = $intDaysAfterMarch;
        }

        $intOffset = (2 * ($intYear % 4) + 4 * ($intYear % 7) + 6 * $intTargetDay + (6 + $intYear / 100 - $intYear / 400 - 2) % 7) % 7;

        $intEasterSundayYearOffset = $intOffset + $intTargetDay + 1 + $intMarch21DayOffset;

        if ($this->isLeapYear($intYear)) {
            $intEasterSundayYearOffset -= 1;
        }

        //offset per year, so calc back to the current year
        $objDateTime = DateTime::createFromFormat('z Y', strval($intEasterSundayYearOffset) . ' ' . strval($intYear));
        $objDate = new Date($objDateTime->getTimestamp());
        return $objDate->setIntHour(0)->setIntMin(0)->setIntSec(0);
    }

    /**
     * Checks if a year is a leap year.
     *
     * @param $intYear
     *
     * @return bool
     * @see http://davidwalsh.name/checking-for-leap-year-using-php
     */
    public function isLeapYear($intYear)
    {
        return ((($intYear % 4) == 0) && ((($intYear % 100) != 0) || (($intYear % 400) == 0)));
    }


    /**
     * Gets the working days for a given month and year.
     * Working days are all TAGERT2-Days.
     *
     * @param $intMonth
     * @param $intYear
     *
     * @return array of Date objects
     */
    public function getWorkingDays($intMonth, $intYear)
    {
        $arrWorkingDays = array();

        $objDate = new Date();
        $objDate->setIntYear($intYear)->setIntMonth($intMonth)->setIntDay(1)->setIntHour(0)->setIntMin(0)->setIntSec(0);

        while ($objDate->getIntMonth() == $intMonth) {
            if ($this->isValidTarget2Day($objDate)) {
                $arrWorkingDays[] = clone $objDate;
            }
            $objDate->setNextDay();
        }

        return $arrWorkingDays;
    }


    /**
     * Calculates the number of days between the given dates.
     * The end date is not included in the count.
     *
     * @param Date $objDateFrom
     * @param Date $objDateTo
     * @param bool $bitIncludeEnddate
     *
     * @return int
     */
    public function calcNumberOfDaysBetween(Date $objDateFrom, Date $objDateTo, $bitIncludeEnddate = false)
    {
        $intNumberOfDays = 0;
        if ($objDateFrom->getLongTimestamp() > $objDateTo->getLongTimestamp()) {
            return $intNumberOfDays;
        }
        if ($objDateFrom->isSameDay($objDateTo)) {
            return $intNumberOfDays;
        }

        $objDateCompare = clone $objDateFrom;

        $intNumberOfDays++;
        while ($objDateCompare = $objDateCompare->setNextDay()) {
            if ($objDateCompare->isSameDay($objDateTo)) {
                if ($bitIncludeEnddate) {
                    $intNumberOfDays++;
                }

                break;
            }

            $intNumberOfDays++;
        }

        return $intNumberOfDays;
    }

    /**
     * Calculates the number of working days between the given dates.
     * The end date are included in the count.
     *
     * @param Date $objDateFrom
     * @param Date $objDateTo
     * @param bool $bitIncludeEnddate
     *
     * @return int
     */
    public function calcNumberOfWorkingDaysBetween(Date $objDateFrom, Date $objDateTo, $bitIncludeEnddate = false)
    {
        $objDateFrom = clone $objDateFrom;
        $objDateFrom->setBeginningOfDay();
        $objDateTo = clone $objDateTo;
        $objDateTo->setBeginningOfDay();


        $intNumberOfWorkingDays = 0;
        if ($objDateFrom->getLongTimestamp() > $objDateTo->getLongTimestamp()) {
            return $intNumberOfWorkingDays;
        }

        if ($objDateFrom->isSameDay($objDateTo)) {
            return $intNumberOfWorkingDays;
        }

        $objDateCompare = clone $objDateFrom;
        if ($this->isValidTarget2Day($objDateCompare)) {
            $intNumberOfWorkingDays++;
        }
        while ($objDateCompare = $this->calcNextWorkingDay($objDateCompare)) {
            if ($objDateCompare->getLongTimestamp() > $objDateTo->getLongTimestamp()) {
                break;
            }

            if ($objDateCompare->isSameDay($objDateTo)) {
                if ($bitIncludeEnddate) {
                    $intNumberOfWorkingDays++;
                }
                break;
            }

            $intNumberOfWorkingDays++;
        }

        return $intNumberOfWorkingDays;

    }


    /**
     * Calculates a date depending on the given date which is used as a base for the calculation of relative dates.
     *
     * @param Date $objDate - The date which is used as a base for the calculation of relative dates.
     * @param string $strRelativeFormatString - Relative date format @see http://php.net/manual/en/datetime.formats.relative.php
     *
     * @return Date
     */
    public function calcDateRelativeFormatString(Date $objDate, $strRelativeFormatString)
    {
        $objNewDate = clone $objDate;
        $strNewDate = date($this->strParseFormat, strtotime($strRelativeFormatString, $objNewDate->getTimeInOldStyle()));
        $objNewDate->setLongTimestamp($strNewDate);

        return $objNewDate;
    }


    /**
     * Calculates the next TARGET2 working day. Optional the amount of working days can be provided
     *
     * @param Date $objDate
     * @param integer $intDays
     *
     * @return Date
     */
    public function calcNextWorkingDay(Date $objDate, $intDays = 1)
    {
        $objNewDate = clone $objDate;

        $intCount = 0;
        while ($intCount < $intDays) {
            $objNewDate->setNextDay();
            while (!$this->isValidTarget2Day($objNewDate)) {
                $objNewDate->setNextDay();
            }
            $intCount++;
        }

        return $objNewDate;
    }

    /**
     * Calculates the last TARGET2 working day. Optional the amount of working days can be provided
     *
     * @param Date $objDate
     * @param integer $intDays
     *
     * @return Date
     */
    public function calcLastWorkingDay(Date $objDate, $intDays = 1)
    {
        $objNewDate = clone $objDate;

        //find last working day
        $intCount = 0;
        while ($intCount < $intDays) {
            $objNewDate->setPreviousDay();
            while (!$this->isValidTarget2Day($objNewDate)) {
                $objNewDate->setPreviousDay();
            }
            $intCount++;
        }

        return $objNewDate;
    }

    /**
     * Calculates the first day of the last given period depending on the given date.
     *
     * For period Weeks: First day of a week is always monday
     *
     * @param DatePeriodEnum $objPeriod
     * @param Date $objDate
     *
     * @return Date
     */
    public function firstDayOfLast(DatePeriodEnum $objPeriod, Date $objDate)
    {
        $strRelativeString = "";

        if ($objPeriod->equals(DatePeriodEnum::YEAR3())) {
            $strRelativeString = "-3 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR2())) {
            $strRelativeString = "-2 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR())) {
            $strRelativeString = "-1 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::HALFYEAR())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 7) {
                $strRelativeString = "-1 year first day of july";
            } else {
                $strRelativeString = "first day of january";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::QUARTER())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 4) {
                $strRelativeString = "-1 year first day of october";
            } elseif ($intMonth > 3 && $intMonth < 7) {
                $strRelativeString = "first day of january";
            } elseif ($intMonth > 6 && $intMonth < 10) {
                $strRelativeString = "first day of april";
            } elseif ($intMonth > 9) {
                $strRelativeString = "first day of july";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::MONTH())) {
            $strRelativeString = "first day of last month";
        } elseif ($objPeriod->equals(DatePeriodEnum::WEEK())) {
            if ($objDate->getIntDayOfWeek() == 1) {
                $strRelativeString = "-1 week";
            } else {
                $strRelativeString = "-1 week last monday";
            }
        }

        $objNewDate = self::calcDateRelativeFormatString($objDate, $strRelativeString);
        $objNewDate->setIntHour($objDate->getIntHour());
        $objNewDate->setIntMin($objDate->getIntMin());
        $objNewDate->setIntSec($objDate->getIntSec());

        return $objNewDate;

    }

    /**
     * Calculates the last day of the last given period depending on the given date.
     *
     * For period Weeks: Last day of a week is always sunday
     *
     * @param DatePeriodEnum $objPeriod
     * @param Date $objDate
     *
     * @return Date
     */
    public function lastDayOfLast(DatePeriodEnum $objPeriod, Date $objDate)
    {
        $strRelativeString = "";

        if ($objPeriod->equals(DatePeriodEnum::YEAR3())) {
            $strRelativeString = "-3 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR2())) {
            $strRelativeString = "-2 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR())) {
            $strRelativeString = "-1 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::HALFYEAR())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 7) {
                $strRelativeString = "-1 year last day of december";
            } else {
                $strRelativeString = "last day of june";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::QUARTER())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 4) {
                $strRelativeString = "-1 year last day of december";
            } elseif ($intMonth > 3 && $intMonth < 7) {
                $strRelativeString = "last day of march";
            } elseif ($intMonth > 6 && $intMonth < 10) {
                $strRelativeString = "last day of june";
            } elseif ($intMonth > 9) {
                $strRelativeString = "last day of september";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::MONTH())) {
            $strRelativeString = "last day of last month";
        } elseif ($objPeriod->equals(DatePeriodEnum::WEEK())) {
            if ($objDate->getIntDayOfWeek() == 0) {
                $strRelativeString = "-1 week";
            } else {
                $strRelativeString = "-1 week next sunday";
            }
        }

        $objNewDate = self::calcDateRelativeFormatString($objDate, $strRelativeString);
        $objNewDate->setIntHour($objDate->getIntHour());
        $objNewDate->setIntMin($objDate->getIntMin());
        $objNewDate->setIntSec($objDate->getIntSec());

        return $objNewDate;
    }

    /**
     * Calculates the first day of the next given period depending on the given date.
     *
     * For period Weeks: First day of a week is always monday
     *
     * @param DatePeriodEnum $objPeriod
     * @param Date $objDate
     *
     * @return Date
     */
    public function firstDayOfNext(DatePeriodEnum $objPeriod, Date $objDate)
    {
        $strRelativeString = "";

        if ($objPeriod->equals(DatePeriodEnum::YEAR3())) {
            $strRelativeString = "+3 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR2())) {
            $strRelativeString = "+2 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR())) {
            $strRelativeString = "+1 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::HALFYEAR())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 7) {
                $strRelativeString = "first day of july";
            } else {
                $strRelativeString = "+1 year first day of january";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::QUARTER())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 4) {
                $strRelativeString = "first day of april";
            } elseif ($intMonth > 3 && $intMonth < 7) {
                $strRelativeString = "first day of july";
            } elseif ($intMonth > 6 && $intMonth < 10) {
                $strRelativeString = "first day of october";
            } elseif ($intMonth > 9) {
                $strRelativeString = "+1 year first day of january";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::MONTH())) {
            $strRelativeString = "first day of next month";
        } elseif ($objPeriod->equals(DatePeriodEnum::WEEK())) {
            if ($objDate->getIntDayOfWeek() == 1) {
                $strRelativeString = "+1 week";
            } else {
                $strRelativeString = "next monday";
            }
        }

        $objNewDate = self::calcDateRelativeFormatString($objDate, $strRelativeString);
        $objNewDate->setIntHour($objDate->getIntHour());
        $objNewDate->setIntMin($objDate->getIntMin());
        $objNewDate->setIntSec($objDate->getIntSec());

        return $objNewDate;
    }

    /**
     * Calculates the last day of the next given period depending on the given date.
     *
     * For period Weeks: Last day of a week is always sunday
     *
     * @param DatePeriodEnum $objPeriod
     * @param Date $objDate
     *
     * @return Date
     */
    public function lastDayOfNext(DatePeriodEnum $objPeriod, Date $objDate)
    {
        $strRelativeString = "";

        if ($objPeriod->equals(DatePeriodEnum::YEAR3())) {
            $strRelativeString = "+3 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR2())) {
            $strRelativeString = "+2 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR())) {
            $strRelativeString = "+1 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::HALFYEAR())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 7) {
                $strRelativeString = "last day of december";
            } else {
                $strRelativeString = "+1 year last day of june";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::QUARTER())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 4) {
                $strRelativeString = "last day of june";
            } elseif ($intMonth > 3 && $intMonth < 7) {
                $strRelativeString = "last day of september";
            } elseif ($intMonth > 6 && $intMonth < 10) {
                $strRelativeString = "last day of december";
            } elseif ($intMonth > 9) {
                $strRelativeString = "+1 year last day of march";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::MONTH())) {
            $strRelativeString = "last day of next month";
        } elseif ($objPeriod->equals(DatePeriodEnum::WEEK())) {
            if ($objDate->getIntDayOfWeek() == 0) {
                $strRelativeString = "+1 week";
            } else {
                $strRelativeString = "+1 week next sunday";
            }
        }

        $objNewDate = self::calcDateRelativeFormatString($objDate, $strRelativeString);
        $objNewDate->setIntHour($objDate->getIntHour());
        $objNewDate->setIntMin($objDate->getIntMin());
        $objNewDate->setIntSec($objDate->getIntSec());

        return $objNewDate;
    }

    /**
     * Calculates the first day of the given period depending on the given date.
     *
     * For period Weeks: First day of a week is always monday
     *
     * @param DatePeriodEnum $objPeriod
     * @param Date $objDate
     *
     * @return Date
     */
    public function firstDayOfThis(DatePeriodEnum $objPeriod, Date $objDate)
    {
        $strRelativeString = "";

        if ($objPeriod->equals(DatePeriodEnum::YEAR3())) {
            $strRelativeString = "+2 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR2())) {
            $strRelativeString = "+1 year first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR())) {
            $strRelativeString = "first day of january";
        } elseif ($objPeriod->equals(DatePeriodEnum::HALFYEAR())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 7) {
                $strRelativeString = "first day of january";
            } else {
                $strRelativeString = "first day of july";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::QUARTER())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 4) {
                $strRelativeString = "first day of january";
            } elseif ($intMonth > 3 && $intMonth < 7) {
                $strRelativeString = "first day of april";
            } elseif ($intMonth > 6 && $intMonth < 10) {
                $strRelativeString = "first day of july";
            } elseif ($intMonth > 9) {
                $strRelativeString = "first day of october";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::MONTH())) {
            $strRelativeString = "first day of this month";
        } elseif ($objPeriod->equals(DatePeriodEnum::WEEK())) {

            $strRelativeString = "monday this week";

            if ($objDate->getIntDayOfWeek() == 0) {

                //php 7.0.0 - 7.0.7
                if (version_compare(PHP_VERSION, "7", ">=") && version_compare(PHP_VERSION, "7.0.8", "<")) {
                    $strRelativeString = "monday last week";
                } //php < 5.6.23
                else {
                    if (version_compare(PHP_VERSION, "5.6.23", "<")) {
                        $strRelativeString = "monday last week";
                    }
                }

            }
        }

        $objNewDate = self::calcDateRelativeFormatString($objDate, $strRelativeString);
        $objNewDate->setIntHour($objDate->getIntHour());
        $objNewDate->setIntMin($objDate->getIntMin());
        $objNewDate->setIntSec($objDate->getIntSec());

        return $objNewDate;
    }


    /**
     * Calculates the first day of the given period depending on the given date.
     *
     * For period Weeks: Last day of a week is always sunday
     *
     * @param DatePeriodEnum $objPeriod
     * @param Date $objDate
     *
     * @return Date
     */
    public function lastDayOfThis(DatePeriodEnum $objPeriod, Date $objDate)
    {
        $strRelativeString = "";

        if ($objPeriod->equals(DatePeriodEnum::YEAR3())) {
            $strRelativeString = "+2 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR2())) {
            $strRelativeString = "+1 year last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR())) {
            $strRelativeString = "last day of december";
        } elseif ($objPeriod->equals(DatePeriodEnum::HALFYEAR())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 7) {
                $strRelativeString = "last day of june";
            } else {
                $strRelativeString = "last day of december";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::QUARTER())) {
            $intMonth = $objDate->getIntMonth();

            if ($intMonth < 4) {
                $strRelativeString = "last day of march";
            } elseif ($intMonth > 3 && $intMonth < 7) {
                $strRelativeString = "last day of june";
            } elseif ($intMonth > 6 && $intMonth < 10) {
                $strRelativeString = "last day of september";
            } elseif ($intMonth > 9) {
                $strRelativeString = "last day of december";
            }
        } elseif ($objPeriod->equals(DatePeriodEnum::MONTH())) {
            $strRelativeString = "last day of this month";
        } elseif ($objPeriod->equals(DatePeriodEnum::WEEK())) {
            if ($objDate->getIntDayOfWeek() == 0) {
                $strRelativeString = "now";
            } else {
                //check if correct?
                $strRelativeString = "sunday this week";
            }
        }

        $objNewDate = self::calcDateRelativeFormatString($objDate, $strRelativeString);
        $objNewDate->setIntHour($objDate->getIntHour());
        $objNewDate->setIntMin($objDate->getIntMin());
        $objNewDate->setIntSec($objDate->getIntSec());

        return $objNewDate;
    }

    /**
     * Adds the Period to the date object and returns a new date object
     *
     * @param DatePeriodEnum $objPeriod
     * @param Date $objDate
     * @return Date
     */
    public function addPeriodToDate(DatePeriodEnum $objPeriod, Date $objDate)
    {
        $strRelativeString = "";

        if ($objPeriod->equals(DatePeriodEnum::YEAR3())) {
            $strRelativeString = "+3 year";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR2())) {
            $strRelativeString = "+2 year";
        } elseif ($objPeriod->equals(DatePeriodEnum::YEAR())) {
            $strRelativeString = "+1 year";
        } elseif ($objPeriod->equals(DatePeriodEnum::HALFYEAR())) {
            $strRelativeString = "+6 month";
        } elseif ($objPeriod->equals(DatePeriodEnum::QUARTER())) {
            $strRelativeString = "+3 month";
        } elseif ($objPeriod->equals(DatePeriodEnum::MONTH())) {
            $strRelativeString = "+1 month";
        } elseif ($objPeriod->equals(DatePeriodEnum::WEEK())) {
            $strRelativeString = "+1 week";
        }

        $objNewDate = self::calcDateRelativeFormatString($objDate, $strRelativeString);

        return $objNewDate;
    }


}

