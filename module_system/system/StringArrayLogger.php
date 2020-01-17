<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Psr\Log\LoggerInterface;

/**
 *
 * A simple logger capturing lines as an array of log-results
 * @author stefan.idler@artemeon.de
 */
class StringArrayLogger implements LoggerInterface
{

    /**
     * @var StringArrayLogEntry[]
     */
    private $logRows = [];


    public function emergency($message, array $context = array())
    {
        $this->log('EMERGENCY', $message);
    }

    public function alert($message, array $context = array())
    {
        $this->log('ALERT', $message);
    }

    public function critical($message, array $context = array())
    {
        $this->log('CRITICAL', $message);
    }

    public function error($message, array $context = array())
    {
        $this->log('ERROR', $message);
    }

    public function warning($message, array $context = array())
    {
        $this->log('WARNING', $message);
    }

    public function notice($message, array $context = array())
    {
        $this->log('NOTICE', $message);
    }

    public function info($message, array $context = array())
    {
        $this->log('INFO', $message);
    }

    public function debug($message, array $context = array())
    {
        $this->log('DEBUG', $message);
    }

    public function log($level, $message, array $context = array())
    {
        $this->logRows[] = new StringArrayLogEntry(new Date(), $level, $message);
    }

    /**
     * @return StringArrayLogEntry[]
     */
    public function getLogRows(): array
    {
        return $this->logRows;
    }


}

