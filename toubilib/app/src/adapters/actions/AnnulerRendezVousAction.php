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

final class AnnulerRendezVousAction
{
    public function __construct(private readonly ServiceRendezVousInterface $service)
    {
    }

    /**
     * @param array{id: string} $args
     * @throws JsonException
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $id = $args['id'];
        if ($id === '') {
            return $this->json(['erreur' => 'Identifiant de rendez-vous invalide.'], 400);
        }

        try {
            $rendezVous = $this->service->annuler($id);
            return $this->json([
                'type' => 'ressource',
                'rendez_vous' => $rendezVous,
                'links' => [
                    'self' => ['href' => '/rdvs/' . $rendezVous->id],
                    'praticien' => ['href' => '/praticiens/' . $rendezVous->praticienId],
                    'patient' => ['href' => '/patients/' . $rendezVous->patientId],
                ],
            ], 200);
        } catch (RendezVousNotFoundException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        } catch (TransitionEtatRendezVousException $e) {
            return $this->json(['erreur' => $e->getMessage()], 409);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }
    }

    /**
     * @throws JsonException
     */
    private function json(mixed $data, int $status): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
