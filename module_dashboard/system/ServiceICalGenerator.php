<?php

namespace Kajona\Dashboard\System;

use Kajona\System\System\AuthenticationException;
use Kajona\System\System\Carrier;
use Kajona\System\System\Exception;
use Kajona\System\System\Exceptions\EntityNotFoundException;
use Kajona\System\System\Exceptions\WrongSystemIdException;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\UserUser;

/**
 * ServiceICalGenerator
 *
 * @package Kajona\System\System
 * @author andrii.konoval@artemeon.de
 * @since 7.1
 */
class ServiceICalGenerator
{
    /**
     * @param string $token
     * @return string
     * @throws \Kajona\System\System\Exception
     */
    public function generate(string $token): string
    {

        if (!validateSystemid($token)) {
            throw new WrongSystemIdException('Wrong token!', Exception::$level_ERROR);
        }

        $iCal = Objectfactory::getInstance()->getObject($token);
        if (!$iCal instanceof ICalendar) {
            throw new EntityNotFoundException('Failed loading instance of internet calendar ', Exception::$level_ERROR);
        }

        $userId = $iCal->getStrUserId();

        $user = Objectfactory::getInstance()->getObject($userId);

        if (!$user instanceof UserUser) {
            throw new AuthenticationException('Internet calendar object contains broken user object', Exception::$level_FATALERROR);
        }

        Carrier::getInstance()->getObjSession()->loginUser($user);
        $calDavCalendar = $iCal->getICalendar();
        Carrier::getInstance()->getObjSession()->logout();

        return $calDavCalendar;
    }

}
