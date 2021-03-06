<?php

namespace Kajona\System\System\Lifecycle;

use Kajona\System\System\Exception;

/**
 * ServiceLifeCycleModelException
 *
 * @package Kajona\System\System
 * @author christoph.kappestein@gmail.com
 * @since 7.0
 */
class ServiceLifeCycleModelException extends Exception
{
    protected $strSystemId;

    public function __construct($strError, $strSystemId, $intErrorlevel = null, Exception $objPrevious = null)
    {
        parent::__construct($strError, $intErrorlevel ?? self::$level_ERROR, $objPrevious);

        $this->strSystemId = $strSystemId;
    }

    /**
     * @return mixed
     */
    public function getStrSystemId()
    {
        return $this->strSystemId;
    }

    /**
     * @param mixed $strSystemId
     */
    public function setStrSystemId($strSystemId)
    {
        $this->strSystemId = $strSystemId;
    }
}
