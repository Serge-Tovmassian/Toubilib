<?php

declare(strict_types=1);

namespace toubilib\adapters\actions;

use DateTimeImmutable;
use Exception;
use JsonException;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Psr7\Response;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\exceptions\PatientNotFoundException;
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\domain\exceptions\CreneauRendezVousIndisponibleException;
use toubilib\domain\exceptions\DateRendezVousInvalideException;
use toubilib\domain\exceptions\MotifRendezVousInvalideException;

final class CreerRendezVousAction
{
    public function __construct(private readonly ServiceRendezVousInterface $service)
    {
    }

    /** @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data)) {
            return $this->json(['erreur' => 'Corps JSON invalide.'], 400);
        }

        foreach (['praticien_id', 'patient_id', 'date_heure_debut', 'motif_visite'] as $champ) {
            if (!isset($data[$champ]) || !is_string($data[$champ]) || trim($data[$champ]) === '') {
                return $this->json(['erreur' => "Champ requis : {$champ}."], 400);
            }
        }

        try {
            $debut = new DateTimeImmutable($data['date_heure_debut']);
        } catch (Exception) {
            return $this->json(['erreur' => 'date_heure_debut invalide (format ISO 8601).'], 400);
        }

        try {
            $rdv = $this->service->creer(new CreateRdvDTO(
                $data['praticien_id'],
                $data['patient_id'],
                $debut,
                $data['motif_visite'],
            ));
        } catch (PraticienNotFoundException | PatientNotFoundException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        } catch (CreneauRendezVousIndisponibleException $e) {
            return $this->json(['erreur' => $e->getMessage()], 409);
        } catch (DateRendezVousInvalideException | MotifRendezVousInvalideException $e) {
            return $this->json(['erreur' => $e->getMessage()], 422);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }

        $href = '/rdvs/' . $rdv->id;
        return $this->json([
            'type' => 'ressource',
            'rendez_vous' => $rdv,
            'links' => [
                'self' => ['href' => $href],
                'praticien' => ['href' => '/praticiens/' . $rdv->praticienId],
                'patient' => ['href' => '/patients/' . $rdv->patientId],
                'annuler' => ['href' => $href . '/annuler'],
            ],
        ], 201)->withHeader('Location', $href);
    }

    /** @throws JsonException */
    private function json(mixed $data, int $status): ResponseInterface
    {
        $response = new Response($status);
        $response->getBody()->write(json_encode($data, JSON_THROW_ON_ERROR));
        return $response->withHeader('Content-Type', 'application/json; charset=utf-8');
    }
}
