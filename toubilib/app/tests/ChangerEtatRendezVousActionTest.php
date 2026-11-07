<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use PHPUnit\Framework\TestCase;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Response;
use toubilib\adapters\actions\ChangerEtatRendezVousAction;
use toubilib\application\dto\CreateRdvDTO;
use toubilib\application\dto\RendezVousDTO;
use toubilib\application\exceptions\RendezVousNotFoundException;
use toubilib\application\ports\api\ServiceRendezVousInterface;
use toubilib\domain\exceptions\TransitionEtatRendezVousException;

final class ChangerEtatRendezVousActionTest extends TestCase
{
    private function service(?Throwable $erreur = null): ServiceRendezVousInterface
    {
        return new class($erreur) implements ServiceRendezVousInterface {
            public function __construct(private readonly ?Throwable $erreur) {}
            private function dto(string $etat): RendezVousDTO
            {
                if ($this->erreur !== null) {
                    throw $this->erreur;
                }
                return new RendezVousDTO('rdv-1', 'p1', 'pat1', '2030-01-01T10:00:00+01:00', null, 30, $etat, 'CI');
            }
            public function creer(CreateRdvDTO $dto): RendezVousDTO { throw new LogicException(); }
            public function historique(string $patientId): array { return []; }
            public function agenda(string $praticienId, DateTimeImmutable $debut, DateTimeImmutable $fin): array { return []; }
            public function obtenirParId(string $id): RendezVousDTO { throw new LogicException(); }
            public function annuler(string $id): RendezVousDTO { throw new LogicException(); }
            public function honorer(string $id): RendezVousDTO { return $this->dto('HONORE'); }
            public function ignorer(string $id): RendezVousDTO { return $this->dto('NON_HONORE'); }
            public function declarerIndisponibilite(string $praticienId, DateTimeImmutable $jour, string $periode): RendezVousDTO { throw new LogicException(); }
        };
    }

    private function appeler(ServiceRendezVousInterface $service, bool $honorer): Response
    {
        $request = (new ServerRequestFactory())->createServerRequest('PATCH', '/rdvs/rdv-1/x');
        return (new ChangerEtatRendezVousAction($service, $honorer))($request, new Response(), ['id' => 'rdv-1']);
    }

    public function testHonore(): void
    {
        $r = $this->appeler($this->service(), true);
        self::assertSame(200, $r->getStatusCode());
        self::assertSame('HONORE', json_decode((string) $r->getBody(), true)['rendez_vous']['etat']);
    }

    public function testIgnore(): void
    {
        $r = $this->appeler($this->service(), false);
        self::assertSame('NON_HONORE', json_decode((string) $r->getBody(), true)['rendez_vous']['etat']);
    }

    public function testRetourne404(): void
    {
        self::assertSame(404, $this->appeler($this->service(new RendezVousNotFoundException('x')), true)->getStatusCode());
    }

    public function testRetourne409(): void
    {
        self::assertSame(409, $this->appeler($this->service(new TransitionEtatRendezVousException('non')), false)->getStatusCode());
    }
}
