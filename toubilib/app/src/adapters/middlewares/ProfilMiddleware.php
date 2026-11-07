<?php

declare(strict_types=1);

namespace toubilib\adapters\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Psr7\Response;
use Slim\Routing\RouteContext;

/** Réserve une route /…/{id}/… à l'utilisateur de rôle donné dont le profil est exactement {id}. */
final class ProfilMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly string $role)
    {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $id = RouteContext::fromRequest($request)->getRoute()?->getArgument('id');

        if (($auth['role'] ?? null) !== $this->role || ($auth['profil_id'] ?? null) !== $id) {
            return self::interdit();
        }

        return $handler->handle($request);
    }

    public static function interdit(): ResponseInterface
    {
        $response = new Response(403);
        $response->getBody()->write(json_encode(['erreur' => 'Accès interdit.'], JSON_THROW_ON_ERROR));

        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
