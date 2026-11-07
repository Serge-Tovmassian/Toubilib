<?php

declare(strict_types=1);

namespace toubilib\adapters\actions;

use JsonException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\application\exceptions\PatientNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;

final class HistoriquePatientAction
{
    public function __construct(private readonly ServiceRendezVousInterface $service)
    {
    }

    /** @param array{id: string} $args @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $rdvs = $this->service->historique($args['id']);
        } catch (PatientNotFoundException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }

        return $this->json([
            'type' => 'collection',
            'count' => count($rdvs),
            'rendez_vous' => array_map(
                fn ($rdv) => ['rendez_vous' => $rdv, 'links' => ['self' => ['href' => '/rdvs/' . $rdv->id]]],
                $rdvs,
            ),
            'links' => ['self' => ['href' => '/patients/' . $args['id'] . '/rdvs']],
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
