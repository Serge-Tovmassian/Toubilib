<?php

declare(strict_types=1);

namespace toubilib\adapters\middlewares;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Throwable;

final class AuthMiddleware implements MiddlewareInterface
{
    private const ROUTES_PUBLIQUES = ['/auth/login'];

    public function __construct(private readonly string $secret)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (in_array($request->getUri()->getPath(), self::ROUTES_PUBLIQUES, true)) {
            return $handler->handle($request);
        }

        if (!preg_match('/^Bearer (\S+)$/', $request->getHeaderLine('Authorization'), $match)) {
            return $this->nonAutorise('Jeton manquant.');
        }

        try {
            $claims = JWT::decode($match[1], new Key($this->secret, 'HS256'));
        } catch (Throwable) {
            return $this->nonAutorise('Jeton invalide ou expiré.');
        }

        return $handler->handle($request->withAttribute('auth', [
            'id' => $claims->sub,
            'role' => $claims->role,
            'profil_id' => $claims->profil_id,
        ]));
    }

    private function nonAutorise(string $message): ResponseInterface
    {
        $response = new Response(401);
        $response->getBody()->write(json_encode(['erreur' => $message], JSON_THROW_ON_ERROR));

        return $response
            ->withHeader('Content-Type', 'application/json; charset=utf-8')
            ->withHeader('WWW-Authenticate', 'Bearer');
    }
}
