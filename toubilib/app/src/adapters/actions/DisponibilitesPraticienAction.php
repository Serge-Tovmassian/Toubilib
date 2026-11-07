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
use toubilib\application\ports\api\ServicePraticienInterface;
use toubilib\domain\exceptions\MotifRendezVousInvalideException;

final class DisponibilitesPraticienAction
{
    public function __construct(private readonly ServicePraticienInterface $service)
    {
    }

    /** @param array{id: string} $args @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $query = $request->getQueryParams();
        $motif = trim((string) ($query['motif'] ?? ''));
        $date = (string) ($query['date'] ?? '');
        if ($motif === '' || preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) !== 1) {
            return $this->json(['erreur' => 'Paramètres requis : date (YYYY-MM-DD) et motif.'], 400);
        }

        try {
            $jour = new DateTimeImmutable($date);
            $creneaux = $this->service->disponibilites($args['id'], $jour, $motif);
        } catch (Exception $e) {
            return match (true) {
                $e instanceof PraticienNotFoundException => $this->json(['erreur' => $e->getMessage()], 404),
                $e instanceof MotifRendezVousInvalideException => $this->json(['erreur' => $e->getMessage()], 422),
                default => $this->json(['erreur' => 'Date invalide.'], 400),
            };
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }

        return $this->json([
            'type' => 'collection',
            'date' => $date,
            'motif' => $motif,
            'count' => count($creneaux),
            'creneaux_disponibles' => $creneaux,
            'links' => [
                'self' => ['href' => '/praticiens/' . $args['id'] . '/disponibilites?' . http_build_query(['date' => $date, 'motif' => $motif])],
                'praticien' => ['href' => '/praticiens/' . $args['id']],
                'creer_rdv' => ['href' => '/rdvs'],
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
