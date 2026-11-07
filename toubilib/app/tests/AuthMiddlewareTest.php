<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Firebase\JWT\JWT;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use toubilib\adapters\middlewares\AuthMiddleware;

final class AuthMiddlewareTest extends TestCase
{
    private const SECRET = 'secret-de-test-assez-long-pour-hs256!';

    private function handler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public ?array $auth = null;
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                $this->auth = $request->getAttribute('auth');
                return new Response(200);
            }
        };
    }

    private function jeton(int $exp, string $secret = self::SECRET): string
    {
        return JWT::encode(['sub' => 'u1', 'role' => 'patient', 'profil_id' => 'pat-1', 'exp' => $exp], $secret, 'HS256');
    }

    private function appel(string $chemin, ?string $authorization, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = (new ServerRequestFactory())->createServerRequest('GET', $chemin);
        if ($authorization !== null) {
            $request = $request->withHeader('Authorization', $authorization);
        }

        return (new AuthMiddleware(self::SECRET))->process($request, $handler);
    }

    public function testRefuseSansJeton(): void
    {
        self::assertSame(401, $this->appel('/praticiens', null, $this->handler())->getStatusCode());
    }

    public function testRefuseUnJetonExpireOuMalSigne(): void
    {
        self::assertSame(401, $this->appel('/praticiens', 'Bearer ' . $this->jeton(time() - 10), $this->handler())->getStatusCode());
        self::assertSame(401, $this->appel('/praticiens', 'Bearer ' . $this->jeton(time() + 60, 'un-autre-secret-de-32-caracteres!!'), $this->handler())->getStatusCode());
    }

    public function testAccepteUnJetonValideEtExposeLIdentite(): void
    {
        $handler = $this->handler();

        $response = $this->appel('/praticiens', 'Bearer ' . $this->jeton(time() + 60), $handler);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('pat-1', $handler->auth['profil_id']);
        self::assertSame('patient', $handler->auth['role']);
    }

    public function testLoginResteAccessibleSansJeton(): void
    {
        self::assertSame(200, $this->appel('/auth/login', null, $this->handler())->getStatusCode());
    }
}
