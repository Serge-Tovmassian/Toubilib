<?php

declare(strict_types=1);

namespace toubilib\adapters\actions;

use JsonException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\application\ports\api\ServicePraticienInterface;

final class ListerPraticiensAction
{
    public function __construct(private readonly ServicePraticienInterface $service)
    {
    }

    /** @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        try {
            return $this->json($this->service->lister($this->param($request, 'specialite'), $this->param($request, 'ville')), 200);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }
    }

    private function param(ServerRequestInterface $request, string $nom): ?string
    {
        $valeur = trim((string) ($request->getQueryParams()[$nom] ?? ''));

        return $valeur === '' ? null : $valeur;
    }

    /** @throws JsonException */
    private function json(mixed $data, int $status): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
