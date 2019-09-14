<?php
/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

namespace Kajona\Api\System;

use Firebase\JWT\JWT;
use Kajona\System\System\SystemSetting;
use Kajona\System\System\UserUser;
use UnexpectedValueException;

/**
 * Service which generates and validates JWTs. It uses the project secret token as key for the signature. Note the data
 * inside the token is not encrypted means every user can decode the token and see the payload, but we can validate on
 * the server side that the token was not modified through the signature. So please _DONT_ put any sensitive information
 * inside the token
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.1
 */
class JWTManager
{
    private const ALG = 'HS256';

    /**
     * @var ProjectSecret
     */
    private $projectSecret;

    /**
     * @param ProjectSecret $projectSecret
     */
    public function __construct(ProjectSecret $projectSecret)
    {
        $this->projectSecret = $projectSecret;
    }

    /**
     * @param UserUser $user
     * @param int|null $expirationTime
     * @return string
     */
    public function generate(UserUser $user, int $expirationTime = null): string
    {
        if ($expirationTime === null) {
            $expirationTime = time() + (int) SystemSetting::getConfigValue('_system_release_time_');
        }

        $payload = [
            'iss' => _webpath_,
            'sub' => $user->getSystemid(),
            'exp' => $expirationTime,
            'iat' => \time(),
            'name' => $user->getStrUsername(),
            'lastname' => $user->getStrName(),
            'forename' => $user->getStrForename(),
            'lang' => $user->getStrAdminlanguage(),
            'admin' => $user->getIntAdmin(),
            'nonce' => \bin2hex(\random_bytes(6)),
        ];

        return JWT::encode($payload, $this->projectSecret->getToken(), self::ALG);
    }

    /**
     * @param string $token
     * @param string $userId
     * @return bool
     */
    public function validate(string $token, string $userId): bool
    {
        $data = JWT::decode($token, $this->projectSecret->getToken(), [self::ALG]);

        if (!isset($data->sub) || $data->sub !== $userId) {
            throw new UnexpectedValueException('Token belongs to a different user');
        }

        if (!isset($data->iss) || $data->iss !== _webpath_) {
            throw new UnexpectedValueException('Token was issued from a different AGP instance');
        }

        return true;
    }
}
