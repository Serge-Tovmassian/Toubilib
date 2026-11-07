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
use toubilib\application\exceptions\PraticienNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\domain\exceptions\CreneauRendezVousIndisponibleException;
use toubilib\domain\exceptions\DateRendezVousInvalideException;

final class DeclarerIndisponibiliteAction
{
    public function __construct(private readonly ServiceRendezVousInterface $service)
    {
    }

    /** @param array<string, string> $args */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $data = json_decode((string) $request->getBody(), true);
        if (!is_array($data) || !is_string($data['date'] ?? null) || !is_string($data['periode'] ?? null)) {
            return $this->json(['erreur' => 'Champs requis : date (YYYY-MM-DD) et periode (matin|apres-midi).'], 400);
        }

        try {
            $jour = new DateTimeImmutable($data['date']);
        } catch (Exception) {
            return $this->json(['erreur' => 'date invalide (format YYYY-MM-DD).'], 400);
        }

        try {
            $rdv = $this->service->declarerIndisponibilite($args['id'], $jour, $data['periode']);
        } catch (PraticienNotFoundException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        } catch (CreneauRendezVousIndisponibleException $e) {
            return $this->json(['erreur' => $e->getMessage()], 409);
        } catch (DateRendezVousInvalideException $e) {
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
