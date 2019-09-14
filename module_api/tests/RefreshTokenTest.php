<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Api\Tests;

use Firebase\JWT\JWT;
use Kajona\Api\System\JWTManager;
use Kajona\Api\System\ProjectSecret;
use Kajona\Api\System\ServiceProvider;
use Kajona\System\System\Carrier;
use Kajona\System\System\Database;
use Kajona\System\System\Objectfactory;
use Kajona\System\System\UserUser;

/**
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
class RefreshTokenTest extends ApiTestCase
{
    /**
     * @var JWTManager
     */
    private $jwtManager;

    /**
     * @var ProjectSecret
     */
    private $projectSecret;

    protected function setUp(): void
    {
        parent::setUp();

        $this->jwtManager = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::JWT_MANAGER);
        $this->projectSecret = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::PROJECT_SECRET);
    }

    public function testRefresh(): void
    {
        $user = $this->getRandomUser();
        $token = $this->jwtManager->generate($user);

        // set new access token to user
        $this->updateTokenForUser($user, $token);

        $response = $this->send('POST', '/v1/authorization/refresh', ['Content-Type' => 'application/json'], json_encode(['token' => $token]));

        $this->assertEquals(200, $response->getStatusCode());

        $data = \json_decode((string) $response->getBody(), true);

        $this->assertNotEmpty($data['access_token']);
        $this->assertNotEquals($token, $data['access_token']);

        $claims = JWT::decode($data['access_token'], $this->projectSecret->getToken(), ['HS256']);

        $this->assertEquals($user->getSystemid(), $claims->sub);
        $this->assertEquals($user->getStrUsername(), $claims->name);
    }

    public function testRefreshNoToken(): void
    {
        $response = $this->send('POST', '/v1/authorization/refresh');

        $expect = <<<JSON
{
  "error": "No token provided"
}
JSON;

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($expect, (string) $response->getBody());
    }

    public function testRefreshInvalidToken(): void
    {
        $response = $this->send('POST', '/v1/authorization/refresh', ['Content-Type' => 'application/json'], json_encode(['token' => 'foobar']));

        $expect = <<<JSON
{
  "error": "Could not determine user for token"
}
JSON;

        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString($expect, (string) $response->getBody());
    }

    private function getRandomUser(): UserUser
    {
        $row = Database::getInstance()->getPRow('SELECT user_id FROM agp_user', []);
        return Objectfactory::getInstance()->getObject($row['user_id']);
    }

    private function updateTokenForUser(UserUser $user, string $token): void
    {
        Database::getInstance()->update('agp_user', [
            'user_accesstoken' => $token
        ], [
            'user_id' => $user->getSystemid()
        ]);
    }
}
