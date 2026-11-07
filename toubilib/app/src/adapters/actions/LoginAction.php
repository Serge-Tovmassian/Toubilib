<?php

declare(strict_types=1);

namespace toubilib\adapters\actions;

use JsonException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\application\exceptions\IdentifiantsInvalidesException;
use toubilib\application\ports\api\ServiceAuthInterface;
use toubilib\application\usecases\ServiceAuth;

final class LoginAction
{
    public function __construct(private readonly ServiceAuthInterface $service)
    {
    }

    /** @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || !is_string($data['email'] ?? null) || !is_string($data['password'] ?? null)) {
            return $this->json(['erreur' => 'Champs requis : email et password.'], 400);
        }

        try {
            $token = $this->service->connecter($data['email'], $data['password']);
        } catch (IdentifiantsInvalidesException $e) {
            return $this->json(['erreur' => $e->getMessage()], 401);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }

        return $this->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => ServiceAuth::DUREE_JETON,
        ], 200);
    }

    /** @throws JsonException */
    private function json(mixed $data, int $status): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
