<?php

declare(strict_types=1);

namespace toubilib\adapters\middlewares;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Slim\Routing\RouteContext;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;

/** Réserve une route /rdvs/{id}/… au praticien du rendez-vous, et au patient si $patientAutorise. */
final class RendezVousMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ServiceRendezVousInterface $service,
        private readonly bool $patientAutorise,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $auth = $request->getAttribute('auth');
        $id = (string) RouteContext::fromRequest($request)->getRoute()?->getArgument('id');

        try {
            $rdv = $this->service->obtenirParId($id);
        } catch (RendezVousNotFoundException) {
            return $handler->handle($request); // l'action répondra 404
        }

        $profil = $auth['profil_id'] ?? null;
        $autorise = match ($auth['role'] ?? null) {
            'praticien' => $profil === $rdv->praticienId,
            'patient' => $this->patientAutorise && $profil === $rdv->patientId,
            default => false,
        };

        return $autorise ? $handler->handle($request) : ProfilMiddleware::interdit();
    }
}
