<?php

/*"******************************************************************************************************
 *   (c) ARTEMEON Management Partner GmbH
 *       Published under the GNU LGPL v2.1
 ********************************************************************************************************/

declare(strict_types=1);

namespace Kajona\Api\Tests;

use Kajona\Api\System\AppBuilder;
use Kajona\Api\System\JWTManager;
use Kajona\Api\System\ServiceProvider;
use Kajona\System\System\Carrier;
use Kajona\System\System\Database;
use Kajona\System\System\Exception;
use Kajona\System\System\UserUser;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Slim\Exception\MethodNotAllowedException;
use Slim\Exception\NotFoundException;
use Slim\Http\Body;
use Slim\Http\Headers;
use Slim\Http\Request;
use Slim\Http\Response;
use Slim\Http\Uri;

/**
 * Abstract test case to test API endpoints
 *
 * @author christoph.kappestein@artemeon.de
 * @since 7.2
 */
abstract class ApiTestCase extends TestCase
{
    /**
     * @param string $method
     * @param string $path
     * @param array $headers
     * @param string|null $body
     * @return ResponseInterface
     * @throws Exception
     * @throws MethodNotAllowedException
     * @throws NotFoundException
     */
    protected function send(string $method, string $path, array $headers = [], ?string $body = null): ResponseInterface
    {
        $request = new Request(
            $method,
            Uri::createFromString('http://127.0.0.1' . $path),
            new Headers($headers),
            [],
            [],
            new Body($this->createStringStream($body))
        );

        $response = new Response();

        /** @var AppBuilder $appBuilder */
        $appBuilder = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::APP_BUILDER);

        return $appBuilder->build()->process($request, $response);
    }

    /**
     * Generates a new access token for the provided user
     *
     * @param string $userName
     * @return string
     */
    protected function generateAccessTokenForUser(string $userName)
    {
        $users = UserUser::getAllUsersByName($userName);
        $user = array_shift($users);

        if (!$user instanceof UserUser) {
            throw new \RuntimeException(sprintf('User %s does not exist', $userName));
        }

        /** @var JWTManager $tokenGenerator */
        $tokenGenerator = Carrier::getInstance()->getContainer()->offsetGet(ServiceProvider::JWT_MANAGER);
        $token = $tokenGenerator->generate($user);

        $connection = Database::getInstance();
        $connection->update('agp_user', [
            'user_accesstoken' => $token
        ], [
            'user_id' => $user->getSystemid()
        ]);

        return $token;
    }

    private function createStringStream(?string $body)
    {
        $handle = fopen('php://temp', 'r+');
        fwrite($handle, (string) $body);
        rewind($handle);

        return $handle;
    }
}
