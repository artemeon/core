<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System\Authorization;

use Kajona\Api\System\AuthorizationInterface;
use Kajona\Api\System\JWTManager;
use Kajona\System\System\Database;
use Kajona\System\System\Exception;
use Kajona\System\System\Lang;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\Session;
use Kajona\System\System\UserUser;
use Slim\Http\Request;

/**
 * Abstract authorization service which uses the access token from a user
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
abstract class UserTokenAbstract implements AuthorizationInterface
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
     * @var Session
     */
    private $session;
    /**
     * @var Lang
     */
    private $lang;

    /**
     * @param Database $connection
     * @param JWTManager $jwtManager
     * @param Session $session
     * @param Lang $lang
     * @throws Exception
     */
    public function __construct(Database $connection, JWTManager $jwtManager, Session $session, Lang $lang)
    {
        $this->connection = $connection;
        $this->jwtManager = $jwtManager;
        $this->session = $session;
        $this->lang = $lang;
    }

    /**
     * @inheritdoc
     */
    public function isAuthorized(Request $request): bool
    {
        $token = $this->getToken($request);
        $userId = $this->getUserIdForToken($token);
        if ($userId === null) {
            return false;
        }

        $user = Objectfactory::getInstance()->getObject($userId);
        if (!$user instanceof UserUser) {
            return false;
        }

        if ($user->getIntRecordStatus() !== 1) {
            return false;
        }

        $this->session->loginUserForRequest($user);
        $this->lang->setStrTextLanguage($this->session->getAdminLanguage(true));
        return true;
    }

    /**
     * Extracts the access token from the request
     *
     * @param Request $request
     * @return string|null
     */
    abstract protected function getToken(Request $request): ?string;

    private function getUserIdForToken(?string $token): ?string
    {
        if (empty($token)) {
            return null;
        }

        $row = $this->connection->getPRow("SELECT user_id FROM agp_user WHERE user_accesstoken = ?", [$token]);
        $userId = $row["user_id"] ?? null;

        if (!validateSystemid($userId)) {
            return null;
        }

        if (!$this->jwtManager->validate($token, $userId)) {
            return null;
        }

        return $userId;
    }
}
