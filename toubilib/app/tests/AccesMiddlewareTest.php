<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Factory\AppFactory;
use Slim\Psr7\Factory\ServerRequestFactory;
use toubilib\adapters\middlewares\ProfilMiddleware;
use toubilib\adapters\middlewares\RendezVousMiddleware;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\RendezVousDTO;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;

final class AccesMiddlewareTest extends TestCase
{
    private function appeler(MiddlewareInterface $mw, ?string $role, ?string $profil, string $id): int
    {
        $app = AppFactory::create();
        $app->addRoutingMiddleware();
        $app->get('/x/{id}', static fn ($rq, $rs) => $rs)
            ->add($mw)
            ->add(static fn ($rq, $h) => $h->handle($role === null ? $rq : $rq->withAttribute('auth', ['role' => $role, 'profil_id' => $profil])));

        return $app->handle((new ServerRequestFactory())->createServerRequest('GET', '/x/' . $id))->getStatusCode();
    }

    private function service(bool $existe = true): ServiceRendezVousInterface
    {
        return new class($existe) implements ServiceRendezVousInterface {
            public function __construct(private readonly bool $existe) {}
            public function obtenirParId(string $id): RendezVousDTO
            {
                $this->existe || throw new RendezVousNotFoundException($id);
                return new RendezVousDTO($id, 'prat-1', 'pat-1', '2030-01-01T10:00:00+00:00', null, 30, 'PLANIFIE', 'CI');
            }
            public function creer(CreateRdvDTO $dto): RendezVousDTO { throw new LogicException(); }
            public function annuler(string $id): RendezVousDTO { throw new LogicException(); }
            public function honorer(string $id): RendezVousDTO { throw new LogicException(); }
            public function ignorer(string $id): RendezVousDTO { throw new LogicException(); }
            public function declarerIndisponibilite(string $praticienId, DateTimeImmutable $jour, string $periode): RendezVousDTO { throw new LogicException(); }
            public function historique(string $patientId): array { return []; }
            public function agenda(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array { return []; }
        };
    }

    public function testProfilAutoriseSonPropreIdentifiantSeulement(): void
    {
        $mw = new ProfilMiddleware('praticien');

        self::assertSame(200, $this->appeler($mw, 'praticien', 'prat-1', 'prat-1'));
        self::assertSame(403, $this->appeler($mw, 'praticien', 'prat-1', 'prat-2'));
        self::assertSame(403, $this->appeler($mw, 'patient', 'prat-1', 'prat-1'));
    }

    public function testRendezVousOuvertAuPatientEtAuPraticienConcernes(): void
    {
        $mw = new RendezVousMiddleware($this->service(), true);

        self::assertSame(200, $this->appeler($mw, 'patient', 'pat-1', 'rdv-1'));
        self::assertSame(200, $this->appeler($mw, 'praticien', 'prat-1', 'rdv-1'));
        self::assertSame(403, $this->appeler($mw, 'patient', 'pat-2', 'rdv-1'));
        self::assertSame(403, $this->appeler($mw, 'praticien', 'prat-2', 'rdv-1'));
    }

    public function testRendezVousReserveAuPraticienSiPatientNonAutorise(): void
    {
        $mw = new RendezVousMiddleware($this->service(), false);

        self::assertSame(403, $this->appeler($mw, 'patient', 'pat-1', 'rdv-1'));
        self::assertSame(200, $this->appeler($mw, 'praticien', 'prat-1', 'rdv-1'));
    }

    public function testRendezVousInconnuLaisseL_ActionRepondre404(): void
    {
        $mw = new RendezVousMiddleware($this->service(false), true);

        self::assertSame(200, $this->appeler($mw, 'patient', 'pat-1', 'absent'));
    }
}
