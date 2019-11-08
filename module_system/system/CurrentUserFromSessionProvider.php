<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\System\System;

use Kajona\System\System\Exceptions\UnableToLoadCurrentUserException;

final class CurrentUserFromSessionProvider implements CurrentUserProviderInterface
{
    /**
     * @var Session
     */
    private $session;

    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    public function load(): UserUser
    {
        try {
            $currentUser = $this->session->getUser();
        } catch (Exception $exception) {
            throw new UnableToLoadCurrentUserException($exception);
        }

        if (!($currentUser instanceof UserUser)) {
            throw new UnableToLoadCurrentUserException();
        }

        return $currentUser;
    }
}
