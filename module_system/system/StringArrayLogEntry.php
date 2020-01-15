<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace AGP\System\System;

/**
 *
 * @author stefan.idler@artemeon.de
 */
class StringArrayLogEntry
{

    private $date;
    private $level;
    private $message;

    /**
     * StringArrayLogEntry constructor.
     * @param $date
     * @param $level
     * @param $message
     */
    public function __construct($date, $level, $message)
    {
        $this->date = $date;
        $this->level = $level;
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * @return mixed
     */
    public function getLevel()
    {
        return $this->level;
    }

    /**
     * @return mixed
     */
    public function getMessage()
    {
        return $this->message;
    }

}


