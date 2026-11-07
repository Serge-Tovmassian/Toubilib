<?php

declare(strict_types=1);

namespace toubilib\adapters\actions;

use JsonException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\domain\exceptions\TransitionEtatRendezVousException;

final class ChangerEtatRendezVousAction
{
    public function __construct(
        private readonly ServiceRendezVousInterface $service,
        private readonly bool $honorer,
    ) {
    }

    /** @param array{id: string} $args @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        try {
            $rdv = $this->honorer ? $this->service->honorer($args['id']) : $this->service->ignorer($args['id']);
        } catch (RendezVousNotFoundException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        } catch (TransitionEtatRendezVousException $e) {
            return $this->json(['erreur' => $e->getMessage()], 409);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }

        return $this->json([
            'type' => 'ressource',
            'rendez_vous' => $rdv,
            'links' => [
                'self' => ['href' => '/rdvs/' . $rdv->id],
                'praticien' => ['href' => '/praticiens/' . $rdv->praticienId],
                'patient' => ['href' => '/patients/' . $rdv->patientId],
            ],
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
