<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\System\System;

use Psr\Log\LoggerInterface;

/**
 * Default implementation of a userlog cleaner
 */
class LoginProtocolCleaner implements LoginProtocolCleanerInterface
{
    /**
     * @var int
     */
    private $thresholdDays;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * UserLogCleaner constructor.
     * @param int $thresholdDays
     * @param LoggerInterface $logger
     */
    public function __construct(int $thresholdDays, LoggerInterface $logger)
    {
        $this->thresholdDays = $thresholdDays;
        $this->logger = $logger;
    }


    public function cleanUserlog()
    {
        $date = new Date();
        $helper = new DateHelper();
        $date = $helper->calcDateRelativeFormatString($date, "-{$this->thresholdDays}days");
        $date->setBeginningOfDay();
        $userLog = new UserLog();
        $affected = $userLog->cleanLog($date);
        $this->logger->warning(sprintf('Login protocol cleanup, removed %d entries older then %s', $affected, dateToString($date, false)));
    }

}
