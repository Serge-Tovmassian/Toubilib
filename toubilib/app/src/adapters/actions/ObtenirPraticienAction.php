<?php

declare(strict_types=1);

namespace toubilib\adapters\actions;

use JsonException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\ports\api\ServicePraticienInterface;

final class ObtenirPraticienAction
{
    public function __construct(private readonly ServicePraticienInterface $service)
    {
    }

    /** @param array{id: string} $args @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        if (($args['id'] ?? '') === '') {
            return $this->json(['erreur' => 'Identifiant de praticien invalide.'], 400);
        }

        try {
            return $this->json($this->service->obtenirParId($args['id']), 200);
        } catch (PraticienNotFoundException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }
    }

    /** @throws JsonException */
    private function json(mixed $data, int $status): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
