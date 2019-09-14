<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

use Kajona\System\System\Database;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\UserUser;

/**
 * Service to extend an existing JWT
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class TokenRefresher
{
    /**
     * @var Database
     */
    private $connection;

    /**
     * @var JWTManager
     */
    private $jwtManager;

    /**
     * @var Objectfactory
     */
    private $objectFactory;

    public function __construct(Database $connection, JWTManager $jwtManager, Objectfactory $objectFactory)
    {
        $this->jwtManager = $jwtManager;
        $this->connection = $connection;
        $this->objectFactory = $objectFactory;
    }

    public function refresh(string $token)
    {
        $user = $this->getUser($token);

        if (!$user instanceof UserUser) {
            throw new \RuntimeException('Could not determine user for token');
        }

        if (!$this->jwtManager->validate($token, $user->getSystemid())) {
            throw new \RuntimeException('Could not validate token');
        }

        // generate new token
        $newToken = $this->jwtManager->generate($user);

        // update user token
        $this->connection->update('agp_user', ['user_accesstoken' => $newToken], ['user_id' => $user->getSystemid()]);

        return $newToken;
    }

    private function getUser(string $token): ?UserUser
    {
        $row = $this->connection->getPRow("SELECT user_id FROM agp_user WHERE user_accesstoken = ?", [$token]);
        $userId = $row["user_id"] ?? null;

        if (!validateSystemid($userId)) {
            return null;
        }

        $user = $this->objectFactory->getObject($userId);
        if (!$user instanceof UserUser) {
            return null;
        }

        if ($user->getIntRecordDeleted() == 1) {
            return null;
        }

        return $user;
    }
}
