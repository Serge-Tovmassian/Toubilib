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

final class ListerRendezVousPraticienAction
{
    public function __construct(
        private readonly ServiceRendezVousInterface $service,
        private readonly bool $creneauxSeulement,
    ) {
    }

    /** @param array{id: string} $args @throws JsonException */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, array $args): ResponseInterface
    {
        $query = $request->getQueryParams();
        try {
            $debut = new DateTimeImmutable((string) ($query['debut'] ?? ''));
            $fin = new DateTimeImmutable((string) ($query['fin'] ?? ''));
        } catch (Exception) {
            return $this->json(['erreur' => 'Paramètres debut et fin requis (format ISO 8601).'], 400);
        }
        if ($fin <= $debut) {
            return $this->json(['erreur' => 'fin doit être postérieure à debut.'], 400);
        }

        try {
            $rdvs = $this->service->agenda($args['id'], $debut, $fin);
        } catch (PraticienNotFoundException $e) {
            return $this->json(['erreur' => $e->getMessage()], 404);
        } catch (PDOException) {
            return $this->json(['erreur' => 'Erreur de persistance.'], 500);
        }

        $self = '/praticiens/' . $args['id'] . ($this->creneauxSeulement ? '/creneaux' : '/agenda');
        $items = array_map(
            fn ($rdv) => $this->creneauxSeulement
                ? [
                    'date_heure_debut' => $rdv->dateHeureDebut,
                    'date_heure_fin' => (new DateTimeImmutable($rdv->dateHeureDebut))
                        ->modify("+{$rdv->duree} minutes")->format(DATE_ATOM),
                ]
                : ['rendez_vous' => $rdv, 'links' => ['self' => ['href' => '/rdvs/' . $rdv->id]]],
            $rdvs,
        );

        return $this->json([
            'type' => 'collection',
            'count' => count($items),
            ($this->creneauxSeulement ? 'creneaux' : 'rendez_vous') => $items,
            'links' => [
                'self' => ['href' => $self . '?' . http_build_query(['debut' => $query['debut'], 'fin' => $query['fin']])],
                'praticien' => ['href' => '/praticiens/' . $args['id']],
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
